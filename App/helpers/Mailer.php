<?php
require_once(dirname(__FILE__) ."/PHPMailer/src/PHPMailer.php");
require_once(dirname(__FILE__) ."/PHPMailer/src/SMTP.php");
require_once(dirname(__FILE__) ."/PHPMailer/src/Exception.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class Helpers_Mailer
{

    private $errors = array();

    private $recipients = array();

    private $replyToEmail = "";
    private $replyToName = "";

    private $ccRecipients = array();
    private $bccRecipients = array();

    private $stringAttachments = array();


    public function getErrors()
    {
        return $this->errors;
    }

    public function addRecipient($email)
    {
        if( trim($email) ) {
            $this->recipients[] = trim($email);
        }
    }


    public function setReplyTo($email, $name)
    {

        if( trim($email) ) {
            $this->replyToEmail = trim($email);
        }

        if( trim($name) ) {
            $this->replyToName = trim($name);
        }
    }


    public function addCC($email)
    {
        if( trim($email) ) {
            $this->ccRecipients[] = trim($email);
        }
    }

    public function addBCC($email)
    {
        if( trim($email) ) {
            $this->bccRecipients[] = trim($email);
        }
    }

    /**
     * Queue a string attachment (raw binary data).
     * Pass base64_decode($content) from the caller — not the base64 string itself.
     */
    public function addStringAttachment($data, $filename, $mimeType = 'application/octet-stream')
    {
        $this->stringAttachments[] = [
            'data'      => $data,
            'filename'  => $filename,
            'mime_type' => $mimeType ?: 'application/octet-stream',
        ];
    }


    public function sendMail($from, $toEmail, $subject, $body)
    {
        $sent = false;

        $mail = new PHPMailer(true);


        try {

            $this->addRecipient($toEmail);

            $appEnv = config('app.env');
            if( strtoupper($appEnv) != "LIVE" ) {
                
                // override debug email
                $this->recipients = [config('app.debug_email')];

            }

            
            $fromEmail = "";
            $fromName = "";

            if(preg_match('/([^<]+)<([^>]+)>/',$from,$matches)>0)
            {
                $fromEmail = trim($matches[2]);
                $fromName = trim($matches[1]);
            }
            else
            {
                $fromEmail = $from;
            }

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $smtpHost       = env('MAIL_HOST', 'localhost');
            $smtpPort       = (int) env('MAIL_PORT', 587);
            $smtpUsername   = env('MAIL_USERNAME', '');
            $smtpPassword   = env('MAIL_PASSWORD', '');
            $smtpEncryption = env('MAIL_ENCRYPTION', '');

            // Treat the literal string "null" (from .env) as empty
            if ($smtpUsername   === 'null') $smtpUsername   = '';
            if ($smtpPassword   === 'null') $smtpPassword   = '';
            if ($smtpEncryption === 'null') $smtpEncryption = '';

            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->Port       = $smtpPort;
            $mail->SMTPAuth   = !empty($smtpUsername);
            $mail->Username   = $smtpUsername;
            $mail->Password   = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption;

            //Recipients
            $mail->setFrom($fromEmail, $fromName);

            // add recipient
            foreach ($this->recipients as $recipient) {
                $mail->addAddress($recipient);
            }

            if( $this->replyToEmail ) {
                $mail->addReplyTo($this->replyToEmail, $this->replyToName);
            }

            foreach ($this->ccRecipients as $ccRecipient) {
                $mail->addCC($ccRecipient);
            }

            foreach ($this->bccRecipients as $bccRecipient) {
                $mail->addBCC($bccRecipient);
            }

            // File attachments
            foreach ($this->stringAttachments as $att) {
                $mail->addStringAttachment($att['data'], $att['filename'], PHPMailer::ENCODING_BASE64, $att['mime_type']);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = stripslashes($body);
            $mail->AltBody = stripslashes($body);

            $mail->send();

            $sent = true;

        } catch (\Exception $e) {

            $this->errors[] = $mail->ErrorInfo ?: $e->getMessage();
        }


        return $sent;

    }



}
?>
