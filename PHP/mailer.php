<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . "/../vendor/autoload.php";

function ateneaCreateMailer(string $to, string $toName, string $subject, string $htmlBody, string $textBody): PHPMailer
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = "UTF-8";

    $mail->setFrom(SMTP_USER, "Atenea");
    $mail->addAddress($to, $toName);
    $mail->addReplyTo(SMTP_USER, "Atenea");

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = $textBody;

    return $mail;
}

function ateneaSendMailDetailed(string $to, string $toName, string $subject, string $htmlBody, string $textBody): array
{
    try {
        $mail = ateneaCreateMailer($to, $toName, $subject, $htmlBody, $textBody);
        return [
            "ok" => $mail->send(),
            "error" => null,
        ];
    } catch (Exception $exception) {
        $message = isset($mail) && $mail instanceof PHPMailer ? $mail->ErrorInfo : $exception->getMessage();
        error_log("Atenea mailer error: " . $message);
        return [
            "ok" => false,
            "error" => $message,
        ];
    }
}

function ateneaSendMail(string $to, string $toName, string $subject, string $htmlBody, string $textBody): bool
{
    $result = ateneaSendMailDetailed($to, $toName, $subject, $htmlBody, $textBody);
    return (bool) $result["ok"];
}
