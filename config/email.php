<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function enviarEmail($destinatario, $assunto, $mensagem, $anexos = []) {
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dex.vanoni@gmail.com'; // Substitua pelo email do Gmail
        $mail->Password = 'dhekfxvqblpabpbe'; // Substitua pela senha de app do Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Destinatários
        $mail->setFrom('dex.vanoni@gmail.com', 'Sistema de Agendamento BANT');
        $mail->addAddress($destinatario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;

        // Anexos opcionais
        if (!empty($anexos) && is_array($anexos)) {
            foreach ($anexos as $anexo) {
                if (!isset($anexo['path'])) {
                    continue;
                }
                $path = $anexo['path'];
                $name = isset($anexo['name']) ? $anexo['name'] : '';
                if (is_readable($path)) {
                    $mail->addAttachment($path, $name);
                }
            }
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?> 