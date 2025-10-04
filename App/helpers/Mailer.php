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
    
    
    public function sendMail($from, $toEmail, $subject, $body)
    {
        $sent = false;
        
        $mail = new PHPMailer(true);
        
        
        try {
            
            $this->addRecipient($toEmail);
            
            $fromEmail = "";
            $fromName = "";
            
            if(preg_match('/([^<]+)<([^>]+)>/',$from,$matches)>0)
            {
                $fromEmail = trim($matches[1]);
                $fromName = trim($matches[2]);
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
            
            //Server settings
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host = SMTPHost;                    // Set the SMTP server to send through
            $mail->SMTPAuth = true;                                   // Enable SMTP authentication
            $mail->Username = SMTPUsername;                     // SMTP username
            $mail->Password = SMTPPassword;                               // SMTP password
            $mail->SMTPSecure = SMTPEncryption;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->Port = SMTPPort;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
            
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
            
            
            // Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            
            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = stripslashes($body);
            $mail->AltBody = stripslashes($body);
            
            $mail->send();
            
            $sent = true;
            
        } catch (Exception $e) {
                
            $this->errors[] = $mail->ErrorInfo;
        }
        
        
        return $sent;
        
    }
    
    
    
}
?>