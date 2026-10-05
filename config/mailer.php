<?php

namespace Idealo\Config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configurar();
    }

    private function configurar()
    {
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = SMTP_USER;
        $this->mail->Password   = SMTP_PASS;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;
        $this->mail->CharSet    = 'UTF-8';
        $this->mail->isHTML(true);
        $this->mail->setFrom(CORREO_ORIGEN, 'Idealo - Recuperación de Contraseña');
    }

    public function enviarCodigoRecuperacion($correoDestino, $codigo)
    {
        try {
            $this->mail->addAddress($correoDestino);
            $this->mail->Subject = 'Código de recuperación de contraseña - Idealo';
            $this->mail->Body = $this->plantillaCodigo($codigo);
            $this->mail->AltBody = "Tu código de verificación es: $codigo";
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Error al enviar correo: ' . $this->mail->ErrorInfo);
            return false;
        }
    }

    private function plantillaCodigo($codigo)
    {
        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Código de Recuperación</title>
            <style>
                body { font-family: "Plus Jakarta Sans", sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .code { background: #f1f5f9; border: 2px dashed #475569; border-radius: 8px; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #1e293b; margin: 20px 0; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Idéalo</h1>
                    <p>Recuperación de Contraseña</p>
                </div>
                <div class="content">
                    <h2>Hola,</h2>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña. Usa el siguiente código para continuar:</p>
                    <div class="code">' . htmlspecialchars($codigo) . '</div>
                    <p>Este código expira en 5 minutos.</p>
                    <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' Idéalo. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>';
    }
}