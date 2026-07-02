<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . "/../vendor/autoload.php";

function ateneaSendMail(string $to, string $toName, string $subject, string $htmlBody, string $textBody): bool
{
    $mail = new PHPMailer(true);

    try {
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

        return $mail->send();
    } catch (Exception $exception) {
        error_log("Atenea mailer error: " . $mail->ErrorInfo);
        return false;
    }
}
