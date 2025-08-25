<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



function enviar_correo(
    $to,
    string $subject,
    string $htmlBody,
    string $altBody = '',
    array $cc = [],
    array $bcc = [],
    array $attachments = []
): array {

    require_once __DIR__ . '/../vendor/autoload.php';

    
    $config = include __DIR__ . '/config_mail.php';

   
    if (function_exists('date_default_timezone_set')) {
        date_default_timezone_set('America/El_Salvador');
    }

    $mail = new PHPMailer(true);

  
    
    

    try {
       
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = trim($config['username']);
        $mail->Password   = trim($config['password']);
        $mail->CharSet    = $config['charset'] ?? 'UTF-8';

   
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

 

       
        $mail->setFrom($config['from_email'], $config['from_name']);
        if (!empty($config['reply_to']['email'])) {
            $mail->addReplyTo($config['reply_to']['email'], $config['reply_to']['name'] ?? '');
        }

      
        if (is_string($to)) {
            $mail->addAddress($to);
        } elseif (is_array($to)) {
            foreach ($to as $addr) {
          
                if (is_array($addr) && isset($addr['email'])) {
                    $mail->addAddress($addr['email'], $addr['name'] ?? '');
                } else {
                    $mail->addAddress($addr);
                }
            }
        }

    
        foreach ($cc as $c) {
            if (is_array($c) && isset($c['email'])) {
                $mail->addCC($c['email'], $c['name'] ?? '');
            } else {
                $mail->addCC($c);
            }
        }

     
        foreach ($bcc as $b) {
            if (is_array($b) && isset($b['email'])) {
                $mail->addBCC($b['email'], $b['name'] ?? '');
            } else {
                $mail->addBCC($b);
            }
        }

        
        foreach ($attachments as $file) {
            
            if (is_array($file) && isset($file['path'])) {
                if (is_readable($file['path'])) {
                    $mail->addAttachment($file['path'], $file['name'] ?? '');
                }
            } elseif (is_string($file) && is_readable($file)) {
                $mail->addAttachment($file);
            }
        }

        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

       
        $ok = $mail->send();
        return ['ok' => $ok, 'msg' => $ok ? 'Correo enviado' : $mail->ErrorInfo];
    } catch (Exception $e) {
        
        $err = $mail->ErrorInfo ?: $e->getMessage();
        return ['ok' => false, 'msg' => $err];
    }
}
