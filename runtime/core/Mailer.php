<?php
declare(strict_types=1);

namespace SiteRuntime;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Website email (enquiry notifications, password resets, test messages).
 * Uses SMTP when configured in the admin (Settings → Email), otherwise PHP mail().
 * Settings key "mail": notify_to, from_email, from_name, smtp {enabled, host, port, encryption, username, password (encrypted)}.
 */
final class Mailer
{
    public function __construct(private array $mail, private array $site, private Crypto $crypto, private string $host = '')
    {
    }

    public static function fromSettings(array $settings, Crypto $crypto, string $host = ''): self
    {
        return new self((array) ($settings['mail'] ?? []), (array) ($settings['site'] ?? []), $crypto, $host);
    }

    /** Addresses that receive enquiry notifications (defaults to the website email). */
    public function notifyTo(): array
    {
        $raw = (string) ($this->mail['notify_to'] ?? '') ?: (string) ($this->site['email'] ?? '');
        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    }

    public function configured(): bool
    {
        return $this->notifyTo() !== [];
    }

    /** @throws \RuntimeException with a readable reason when sending fails */
    public function send(array $to, string $subject, string $html, string $text, ?string $replyTo = null): void
    {
        if (!class_exists(PHPMailer::class)) {
            throw new \RuntimeException('The mail library is missing (vendor/phpmailer).');
        }
        $m = new PHPMailer(true);
        $m->CharSet = 'UTF-8';
        $smtp = (array) ($this->mail['smtp'] ?? []);
        if (!empty($smtp['enabled'])) {
            $m->isSMTP();
            $m->Host = (string) ($smtp['host'] ?? '');
            $m->Port = (int) ($smtp['port'] ?? 587);
            $enc = (string) ($smtp['encryption'] ?? 'tls');
            $m->SMTPSecure = $enc === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : ($enc === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : '');
            $m->SMTPAutoTLS = $enc !== 'none';
            if (($smtp['username'] ?? '') !== '') {
                $m->SMTPAuth = true;
                $m->Username = (string) $smtp['username'];
                $m->Password = (string) $this->crypto->decrypt((string) ($smtp['password'] ?? ''));
            }
            $m->Timeout = 15;
        } else {
            $m->isMail();
        }
        $from = (string) ($this->mail['from_email'] ?? '');
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $host = preg_replace('/^www\./', '', strtolower(explode(':', $this->host)[0])) ?: 'localhost';
            $from = 'no-reply@' . $host;
            // An IP address or "localhost" is not a valid sender domain: fall back to the website's own address.
            if (filter_var($host, FILTER_VALIDATE_IP) || !str_contains($host, '.') || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
                $from = $this->notifyTo()[0] ?? (filter_var($this->site['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $this->site['email'] : 'no-reply@localhost.localdomain');
            }
        }
        $m->setFrom($from, (string) (($this->mail['from_name'] ?? '') ?: ($this->site['name'] ?? 'Website')));
        foreach ($to as $addr) {
            $m->addAddress($addr);
        }
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $m->addReplyTo($replyTo);
        }
        $m->Subject = $subject;
        $m->isHTML(true);
        $m->Body = $html;
        $m->AltBody = $text;
        try {
            $m->send();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Email could not be sent: ' . ($m->ErrorInfo ?: $e->getMessage()));
        }
    }

    /** Simple branded HTML wrapper for system emails. */
    public function wrap(string $title, string $bodyHtml): string
    {
        $name = htmlspecialchars((string) ($this->site['name'] ?? 'Website'), ENT_QUOTES, 'UTF-8');
        return '<!doctype html><html><body style="margin:0;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden">'
            . '<tr><td style="background:#1f2937;color:#ffffff;padding:16px 24px;font-size:18px;font-weight:bold">' . $name . '</td></tr>'
            . '<tr><td style="padding:24px"><h1 style="font-size:20px;margin:0 0 16px">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>' . $bodyHtml . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
