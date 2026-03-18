<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $fromEmail = 'noreply@eventhub.local'
    ) {}

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function sendVerificationEmail(User $user): void
    {
        $token = $user->getVerificationToken();

        $verifyUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('✅ Vérifiez votre adresse email — EventHub')
            ->html($this->buildEmailHtml($user->getUsername(), $verifyUrl));

        $this->mailer->send($email);
    }

    private function buildEmailHtml(string $username, string $verifyUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'Inter', Arial, sans-serif; background: #f4f4f8; margin: 0; padding: 20px; }
    .container { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #6c63ff, #00d4ff); padding: 40px 30px; text-align: center; }
    .header h1 { color: #fff; font-size: 28px; margin: 0; font-weight: 800; letter-spacing: -0.5px; }
    .header p { color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px; }
    .body { padding: 40px 30px; }
    .body p { color: #444; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
    .btn { display: block; width: fit-content; margin: 28px auto; background: linear-gradient(135deg, #6c63ff, #00d4ff); color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 50px; font-size: 16px; font-weight: 700; text-align: center; }
    .warning { background: #fff8e1; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #92400e; margin-top: 24px; }
    .footer { background: #f8f9fa; padding: 20px 30px; text-align: center; font-size: 12px; color: #999; }
    .url-fallback { word-break: break-all; font-size: 12px; color: #6c63ff; margin-top: 16px; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚡ EventHub</h1>
        <p>Vérification de votre adresse email</p>
    </div>
    <div class="body">
        <p>Bonjour <strong>{$username}</strong>,</p>
        <p>Merci de vous être inscrit sur <strong>EventHub</strong> ! Pour activer votre compte et commencer à réserver des événements, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous.</p>
        <a href="{$verifyUrl}" class="btn">✅ Vérifier mon email</a>
        <div class="warning">
            ⏰ Ce lien est valable pendant <strong>24 heures</strong>. Passé ce délai, vous devrez demander un nouveau lien.
        </div>
        <p class="url-fallback">Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>{$verifyUrl}</p>
    </div>
    <div class="footer">
        © EventHub — ISSAT Sousse · Si vous n'avez pas créé ce compte, ignorez cet email.
    </div>
</div>
</body>
</html>
HTML;
    }
}
