<?php

require_once __DIR__ . "/mailer.php";

function ateneaMailTableExists(mysqli $conn, string $table): bool
{
    $table = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    return (bool) ($result && $result->num_rows > 0);
}

function ateneaMailColumnExists(mysqli $conn, string $table, string $column): bool
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return (bool) ($result && $result->num_rows > 0);
}

function ateneaEnsureMailCampaignSchema(mysqli $conn): void
{
    if (!ateneaMailTableExists($conn, "mail_campaigns")) {
        $conn->query("
            CREATE TABLE mail_campaigns (
                id int(11) NOT NULL AUTO_INCREMENT,
                admin_id int(11) NOT NULL,
                subject varchar(180) NOT NULL,
                recipient_scope enum('active','all','admins','specific') NOT NULL DEFAULT 'active',
                body_html mediumtext DEFAULT NULL,
                body_text mediumtext DEFAULT NULL,
                recipient_count int(11) NOT NULL DEFAULT 0,
                sent_count int(11) NOT NULL DEFAULT 0,
                failed_count int(11) NOT NULL DEFAULT 0,
                status enum('draft','queued','sending','completed','partial_error','failed','cancelled') NOT NULL DEFAULT 'queued',
                scheduled_at datetime DEFAULT NULL,
                channel varchar(20) NOT NULL DEFAULT 'email',
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                updated_at datetime DEFAULT NULL,
                completed_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY admin_id (admin_id),
                KEY status (status),
                CONSTRAINT mail_campaigns_ibfk_1 FOREIGN KEY (admin_id) REFERENCES usuarios (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    if (!ateneaMailTableExists($conn, "mail_campaign_recipients")) {
        $conn->query("
            CREATE TABLE mail_campaign_recipients (
                id int(11) NOT NULL AUTO_INCREMENT,
                campaign_id int(11) NOT NULL,
                usuario_id int(11) DEFAULT NULL,
                recipient_name varchar(100) DEFAULT NULL,
                recipient_email varchar(150) NOT NULL,
                status enum('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
                attempts int(11) NOT NULL DEFAULT 0,
                error_message text DEFAULT NULL,
                sent_at datetime DEFAULT NULL,
                last_attempt_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY unique_campaign_email (campaign_id, recipient_email),
                KEY campaign_id (campaign_id),
                KEY usuario_id (usuario_id),
                KEY status (status),
                CONSTRAINT mail_campaign_recipients_ibfk_1 FOREIGN KEY (campaign_id) REFERENCES mail_campaigns (id) ON DELETE CASCADE,
                CONSTRAINT mail_campaign_recipients_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
}

function ateneaMailAllowedScope(string $scope): string
{
    $allowed = ["active", "all", "admins", "specific"];
    return in_array($scope, $allowed, true) ? $scope : "active";
}

function ateneaMailCleanSubject(string $subject): string
{
    $subject = trim(preg_replace('/\s+/', ' ', strip_tags($subject)) ?? "");
    return function_exists("mb_substr") ? mb_substr($subject, 0, 180, "UTF-8") : substr($subject, 0, 180);
}

function ateneaMailNormalizeHtml(string $html): string
{
    $html = trim($html);
    $html = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $html) ?? "";
    $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? "";
    $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:.*?\2/is', '$1="#"', $html) ?? "";
    $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*data:.*?\2/is', '$1="#"', $html) ?? "";
    $html = preg_replace('/<\s*img\b(?![^>]*src\s*=\s*[\'"]https:\/\/)[^>]*>/is', '', $html) ?? "";
    $html = strip_tags($html, "<p><br><strong><b><em><i><u><h2><h3><ul><ol><li><a><img>");
    $html = ateneaMailStripUnsafeAttributes($html);

    if (function_exists("mb_substr")) {
        return mb_substr($html, 0, 12000, "UTF-8");
    }

    return substr($html, 0, 12000);
}

function ateneaMailStripUnsafeAttributes(string $html): string
{
    if (!class_exists("DOMDocument")) {
        return preg_replace('/\s(?!href=|src=|alt=|title=|target=|rel=)[a-z0-9:-]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? $html;
    }

    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument("1.0", "UTF-8");
    $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $allowedAttributes = [
        "a" => ["href", "title", "target", "rel"],
        "img" => ["src", "alt", "title"],
    ];

    foreach ($dom->getElementsByTagName("*") as $node) {
        $tag = strtolower($node->nodeName);
        $keep = $allowedAttributes[$tag] ?? [];

        if (!$node->hasAttributes()) {
            continue;
        }

        $remove = [];
        foreach ($node->attributes as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue);

            if (!in_array($name, $keep, true)) {
                $remove[] = $name;
                continue;
            }

            if (in_array($name, ["href", "src"], true) && !preg_match('/^(https?:\/\/|mailto:)/i', $value)) {
                $remove[] = $name;
            }
        }

        foreach ($remove as $name) {
            $node->removeAttribute($name);
        }

        if ($tag === "a" && $node->hasAttribute("href")) {
            $node->setAttribute("target", "_blank");
            $node->setAttribute("rel", "noopener noreferrer");
        }
    }

    $wrapper = $dom->getElementsByTagName("div")->item(0);
    $clean = "";
    if ($wrapper) {
        foreach ($wrapper->childNodes as $child) {
            $clean .= $dom->saveHTML($child);
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $clean;
}

function ateneaMailHtmlToText(string $html): string
{
    $withBreaks = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
    $withBreaks = preg_replace('/<\s*\/(p|h2|h3|li)\s*>/i', "\n", $withBreaks) ?? $withBreaks;
    $text = trim(html_entity_decode(strip_tags($withBreaks), ENT_QUOTES, "UTF-8"));
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
    return $text;
}

function ateneaMailTemplate(string $subject, string $bodyHtml): string
{
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, "UTF-8");

    return '<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>' . $safeSubject . '</title>
</head>
<body style="margin:0;padding:0;background:#eef3f9;font-family:Arial,Helvetica,sans-serif;color:#0d1724;">
  <div style="max-width:680px;margin:0 auto;padding:28px 16px;">
    <div style="background:#08111f;color:#ffffff;border-radius:18px 18px 0 0;padding:22px 24px;">
      <strong style="display:block;color:#00d9ff;font-size:13px;letter-spacing:.08em;text-transform:uppercase;">ATENEA</strong>
      <h1 style="margin:10px 0 0;font-size:26px;line-height:1.2;">' . $safeSubject . '</h1>
    </div>
    <div style="background:#ffffff;border:1px solid #dbe4ee;border-top:0;border-radius:0 0 18px 18px;padding:24px;line-height:1.6;">
      ' . $bodyHtml . '
      <hr style="border:0;border-top:1px solid #e5edf5;margin:26px 0;">
      <p style="margin:0;color:#607084;font-size:13px;">Este comunicado fue enviado por el equipo administrador de ATENEA.</p>
    </div>
  </div>
</body>
</html>';
}

function ateneaMailFetchAll(mysqli $conn, string $sql, string $types = "", array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    if ($types !== "" && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $stmt->close();
    return $rows;
}

function ateneaMailRecipients(mysqli $conn, string $scope, ?int $specificUserId = null): array
{
    $scope = ateneaMailAllowedScope($scope);
    $hasEstado = ateneaMailColumnExists($conn, "usuarios", "estado");

    if ($scope === "specific") {
        if (!$specificUserId || $specificUserId <= 0) {
            return [];
        }

        $rows = ateneaMailFetchAll(
            $conn,
            "SELECT id, nombre, correo FROM usuarios WHERE id = ? LIMIT 1",
            "i",
            [$specificUserId]
        );
    } elseif ($scope === "admins") {
        $where = $hasEstado ? "WHERE rol = 'admin' AND estado = 'activo'" : "WHERE rol = 'admin'";
        $rows = ateneaMailFetchAll($conn, "SELECT id, nombre, correo FROM usuarios {$where} ORDER BY nombre");
    } elseif ($scope === "all") {
        $rows = ateneaMailFetchAll($conn, "SELECT id, nombre, correo FROM usuarios ORDER BY nombre");
    } else {
        $where = $hasEstado ? "WHERE estado = 'activo'" : "";
        $rows = ateneaMailFetchAll($conn, "SELECT id, nombre, correo FROM usuarios {$where} ORDER BY nombre");
    }

    $recipients = [];
    foreach ($rows as $row) {
        $email = trim((string) ($row["correo"] ?? ""));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $recipients[] = [
            "usuario_id" => (int) $row["id"],
            "name" => trim((string) ($row["nombre"] ?? "")) ?: "Usuario Atenea",
            "email" => $email,
        ];
    }

    return $recipients;
}

function ateneaMailCreateCampaign(mysqli $conn, int $adminId, string $subject, string $scope, string $bodyHtml, ?int $specificUserId = null): array
{
    ateneaEnsureMailCampaignSchema($conn);

    $subject = ateneaMailCleanSubject($subject);
    $scope = ateneaMailAllowedScope($scope);
    $bodyHtml = ateneaMailNormalizeHtml($bodyHtml);
    $bodyText = ateneaMailHtmlToText($bodyHtml);
    $recipients = ateneaMailRecipients($conn, $scope, $specificUserId);

    if ($subject === "" || $bodyText === "") {
        return ["ok" => false, "error" => "El asunto y el contenido son obligatorios."];
    }

    if (count($recipients) === 0) {
        return ["ok" => false, "error" => "No hay destinatarios validos para este comunicado."];
    }

    $conn->begin_transaction();

    try {
        $recipientCount = count($recipients);
        $status = "queued";
        $channel = "email";
        $scheduledAt = date("Y-m-d H:i:s");

        $stmt = $conn->prepare("
            INSERT INTO mail_campaigns
            (
                admin_id,
                subject,
                recipient_scope,
                body_html,
                body_text,
                recipient_count,
                status,
                scheduled_at,
                channel,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
            throw new RuntimeException("No fue posible crear la campana.");
        }

        $stmt->bind_param("issssisss", $adminId, $subject, $scope, $bodyHtml, $bodyText, $recipientCount, $status, $scheduledAt, $channel);
        $stmt->execute();
        $campaignId = (int) $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT IGNORE INTO mail_campaign_recipients
            (
                campaign_id,
                usuario_id,
                recipient_name,
                recipient_email
            )
            VALUES (?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new RuntimeException("No fue posible crear la cola de destinatarios.");
        }

        foreach ($recipients as $recipient) {
            $userId = (int) $recipient["usuario_id"];
            $name = $recipient["name"];
            $email = $recipient["email"];
            $stmt->bind_param("iiss", $campaignId, $userId, $name, $email);
            $stmt->execute();
        }

        $stmt->close();
        $conn->commit();

        return [
            "ok" => true,
            "campaign_id" => $campaignId,
            "recipient_count" => $recipientCount,
            "subject" => $subject,
        ];
    } catch (Throwable $error) {
        $conn->rollback();
        return ["ok" => false, "error" => $error->getMessage()];
    }
}

function ateneaMailRefreshCampaignCounters(mysqli $conn, int $campaignId): void
{
    $stmt = $conn->prepare("
        UPDATE mail_campaigns c
        SET
            sent_count = (SELECT COUNT(*) FROM mail_campaign_recipients r WHERE r.campaign_id = c.id AND r.status = 'sent'),
            failed_count = (SELECT COUNT(*) FROM mail_campaign_recipients r WHERE r.campaign_id = c.id AND r.status = 'failed'),
            status = CASE
                WHEN c.status = 'cancelled' THEN 'cancelled'
                WHEN (SELECT COUNT(*) FROM mail_campaign_recipients r WHERE r.campaign_id = c.id AND r.status = 'pending') > 0 THEN 'sending'
                WHEN (SELECT COUNT(*) FROM mail_campaign_recipients r WHERE r.campaign_id = c.id AND r.status = 'failed') > 0
                    AND (SELECT COUNT(*) FROM mail_campaign_recipients r WHERE r.campaign_id = c.id AND r.status = 'sent') = 0 THEN 'failed'
                WHEN (SELECT COUNT(*) FROM mail_campaign_recipients r WHERE r.campaign_id = c.id AND r.status = 'failed') > 0 THEN 'partial_error'
                ELSE 'completed'
            END,
            completed_at = CASE
                WHEN (SELECT COUNT(*) FROM mail_campaign_recipients r WHERE r.campaign_id = c.id AND r.status = 'pending') = 0 THEN NOW()
                ELSE completed_at
            END,
            updated_at = NOW()
        WHERE c.id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param("i", $campaignId);
    $stmt->execute();
    $stmt->close();
}

function ateneaMailProcessBatch(mysqli $conn, int $campaignId, int $limit = 10): array
{
    ateneaEnsureMailCampaignSchema($conn);

    $limit = max(1, min(25, $limit));
    $campaignRows = ateneaMailFetchAll($conn, "SELECT * FROM mail_campaigns WHERE id = ? LIMIT 1", "i", [$campaignId]);
    $campaign = $campaignRows[0] ?? null;

    if (!$campaign || ($campaign["status"] ?? "") === "cancelled") {
        return ["ok" => false, "processed" => 0, "sent" => 0, "failed" => 0, "error" => "Campana no disponible."];
    }

    $recipients = ateneaMailFetchAll(
        $conn,
        "SELECT id, recipient_name, recipient_email FROM mail_campaign_recipients WHERE campaign_id = ? AND status = 'pending' ORDER BY id ASC LIMIT {$limit}",
        "i",
        [$campaignId]
    );

    if (count($recipients) === 0) {
        ateneaMailRefreshCampaignCounters($conn, $campaignId);
        return ["ok" => true, "processed" => 0, "sent" => 0, "failed" => 0];
    }

    $subject = (string) $campaign["subject"];
    $htmlBody = ateneaMailTemplate($subject, (string) $campaign["body_html"]);
    $textBody = (string) $campaign["body_text"];
    $sent = 0;
    $failed = 0;

    foreach ($recipients as $recipient) {
        $recipientId = (int) $recipient["id"];
        $email = trim((string) $recipient["recipient_email"]);
        $name = trim((string) $recipient["recipient_name"]) ?: "Usuario Atenea";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result = ["ok" => false, "error" => "Correo no valido."];
        } else {
            $result = ateneaSendMailDetailed($email, $name, $subject, $htmlBody, $textBody);
        }

        $status = $result["ok"] ? "sent" : "failed";
        $error = $result["ok"] ? null : (string) ($result["error"] ?? "Error desconocido");

        $stmt = $conn->prepare("
            UPDATE mail_campaign_recipients
            SET
                status = ?,
                attempts = attempts + 1,
                error_message = ?,
                sent_at = CASE WHEN ? = 'sent' THEN NOW() ELSE sent_at END,
                last_attempt_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("sssi", $status, $error, $status, $recipientId);
            $stmt->execute();
            $stmt->close();
        }

        if ($result["ok"]) {
            $sent++;
        } else {
            $failed++;
        }
    }

    ateneaMailRefreshCampaignCounters($conn, $campaignId);

    return [
        "ok" => true,
        "processed" => count($recipients),
        "sent" => $sent,
        "failed" => $failed,
    ];
}

function ateneaMailCampaigns(mysqli $conn, int $limit = 12): array
{
    ateneaEnsureMailCampaignSchema($conn);
    return ateneaMailFetchAll(
        $conn,
        "
        SELECT c.*, u.nombre AS admin_nombre
        FROM mail_campaigns c
        INNER JOIN usuarios u ON u.id = c.admin_id
        ORDER BY c.created_at DESC
        LIMIT ?
        ",
        "i",
        [$limit]
    );
}

function ateneaMailCampaignRecipients(mysqli $conn, int $campaignId, int $limit = 80): array
{
    ateneaEnsureMailCampaignSchema($conn);
    return ateneaMailFetchAll(
        $conn,
        "
        SELECT recipient_name, recipient_email, status, attempts, error_message, sent_at, last_attempt_at
        FROM mail_campaign_recipients
        WHERE campaign_id = ?
        ORDER BY id ASC
        LIMIT ?
        ",
        "ii",
        [$campaignId, $limit]
    );
}

function ateneaMailCancelCampaign(mysqli $conn, int $campaignId): array
{
    ateneaEnsureMailCampaignSchema($conn);

    $campaignRows = ateneaMailFetchAll($conn, "SELECT sent_count, status FROM mail_campaigns WHERE id = ? LIMIT 1", "i", [$campaignId]);
    $campaign = $campaignRows[0] ?? null;

    if (!$campaign) {
        return ["ok" => false, "error" => "Campana no encontrada."];
    }

    if ((int) $campaign["sent_count"] > 0 || in_array((string) $campaign["status"], ["completed", "partial_error"], true)) {
        return ["ok" => false, "error" => "No se puede cancelar una campana que ya envio correos."];
    }

    $stmt = $conn->prepare("UPDATE mail_campaigns SET status = 'cancelled', updated_at = NOW(), completed_at = NOW() WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return ["ok" => false, "error" => "No fue posible cancelar la campana."];
    }

    $stmt->bind_param("i", $campaignId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE mail_campaign_recipients SET status = 'skipped' WHERE campaign_id = ? AND status = 'pending'");
    if ($stmt) {
        $stmt->bind_param("i", $campaignId);
        $stmt->execute();
        $stmt->close();
    }

    return ["ok" => true];
}
