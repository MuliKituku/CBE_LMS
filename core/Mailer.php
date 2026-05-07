<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    /**
     * Send an HTML email via SMTP using PHPMailer.
     * All SMTP settings are loaded from the .env file.
     *
     * @param string $to      Recipient email address
     * @param string $toName  Recipient name
     * @param string $subject Email subject
     * @param string $body    HTML email body
     * @return bool           True on success, false on failure
     */
    public static function send($to, $toName, $subject, $body): bool
    {
        $mail = new PHPMailer(true);

        try {
            // SMTP configuration from environment
            $encryption = strtolower(Env::get('MAIL_ENCRYPTION', 'tls')) === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->isSMTP();
            $mail->Host       = Env::get('MAIL_HOST',     'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = Env::get('MAIL_USERNAME', '');
            $mail->Password   = Env::get('MAIL_PASSWORD', '');
            $mail->SMTPSecure = $encryption;
            $mail->Port       = (int) Env::get('MAIL_PORT', '587');

            // Sender and recipient
            $mail->setFrom(
                Env::get('MAIL_FROM_ADDRESS', Env::get('MAIL_USERNAME', '')),
                Env::get('MAIL_FROM_NAME',    'CBE LMS')
            );
            $mail->addAddress($to, $toName);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
