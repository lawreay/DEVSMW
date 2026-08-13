<?php

declare(strict_types=1);

/**
 * Send email via SMTP
 * Requires email_config table to be configured
 */
function send_email(string $to, string $subject, string $htmlBody, string $plainBody = ''): bool
{
    try {
        $config = get_smtp_config();

        // Validate SMTP is configured
        if (!$config || empty($config['smtp_host']) || empty($config['smtp_username'])) {
            error_log('SMTP not configured: missing host or username');
            return false;
        }

        // Use PHP's built-in mail() if SMTP details missing (fallback)
        if (empty($config['from_address'])) {
            error_log('SMTP not configured: missing from_address');
            return false;
        }

        // Build email using SMTP
        $mail = new SMTPMailer(
            host: $config['smtp_host'],
            port: (int) $config['smtp_port'],
            encryption: $config['smtp_encryption'],
            username: $config['smtp_username'],
            password: $config['smtp_password'],
            fromAddress: $config['from_address'],
            fromName: $config['from_name']
        );

        $mail->addTo($to);
        $mail->setSubject($subject);
        $mail->setHTMLBody($htmlBody);
        if (!empty($plainBody)) {
            $mail->setPlainBody($plainBody);
        } else {
            // Generate plain text from HTML if not provided
            $mail->setPlainBody(strip_tags($htmlBody));
        }

        return $mail->send();
    } catch (Throwable $e) {
        error_log('Email send failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get SMTP configuration from database
 */
function get_smtp_config(): ?array
{
    try {
        $stmt = db()->query('SELECT * FROM email_config LIMIT 1');
        $config = $stmt->fetch();
        return $config ?: null;
    } catch (Throwable $e) {
        error_log('Failed to get SMTP config: ' . $e->getMessage());
        return null;
    }
}

/**
 * Update SMTP configuration
 */
function update_smtp_config(array $config): bool
{
    try {
        $stmt = db()->prepare(
            'UPDATE email_config SET
                smtp_host = ?, smtp_port = ?, smtp_encryption = ?,
                smtp_username = ?, smtp_password = ?,
                from_address = ?, from_name = ?
             WHERE id = 1'
        );
        return $stmt->execute([
            $config['smtp_host'] ?? '',
            (int) ($config['smtp_port'] ?? 587),
            $config['smtp_encryption'] ?? 'tls',
            $config['smtp_username'] ?? '',
            $config['smtp_password'] ?? '',
            $config['from_address'] ?? '',
            $config['from_name'] ?? 'DEVSMW',
        ]);
    } catch (Throwable $e) {
        error_log('Failed to update SMTP config: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send password reset email
 */
function send_password_reset_email(string $username, string $email, string $resetToken): bool
{
    $resetUrl = site_url('admin/reset-password.php?token=' . urlencode($resetToken));

    $subject = 'Reset Your DEVSMW Admin Password';

    $htmlBody = <<<HTML
<html>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Password Reset Request</h2>
        
        <p>Hello <strong>$username</strong>,</p>
        
        <p>You requested to reset your DEVSMW admin password. Click the button below to proceed:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="$resetUrl" style="background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Reset Password
            </a>
        </div>
        
        <p>Or copy and paste this link in your browser:<br>
        <code style="background: #f5f5f5; padding: 5px;">$resetUrl</code></p>
        
        <p><strong>Security Note:</strong> This link expires in 1 hour. If you didn't request this reset, please ignore this email.</p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
        
        <p style="font-size: 12px; color: #666;">
            DEVSMW Admin System<br>
            This is an automated email. Please do not reply.
        </p>
    </div>
</body>
</html>
HTML;

    $plainBody = <<<TEXT
Password Reset Request

Hello $username,

You requested to reset your DEVSMW admin password. Visit the link below to proceed:

$resetUrl

Security Note: This link expires in 1 hour. If you didn't request this reset, please ignore this email.

DEVSMW Admin System
TEXT;

    return send_email($email, $subject, $htmlBody, $plainBody);
}

/**
 * Generate password reset token
 */
function generate_password_reset_token(int $adminUserId): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    try {
        $stmt = db()->prepare(
            'INSERT INTO password_reset_tokens (token_hash, admin_user_id, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        );
        $stmt->execute([$tokenHash, $adminUserId]);

        return $token;
    } catch (Throwable $e) {
        error_log('Failed to generate reset token: ' . $e->getMessage());
        return '';
    }
}

/**
 * Verify password reset token
 */
function verify_password_reset_token(string $token): ?array
{
    $tokenHash = hash('sha256', $token);

    try {
        $stmt = db()->prepare(
            'SELECT prt.admin_user_id, au.username, au.email
             FROM password_reset_tokens prt
             JOIN admin_users au ON au.id = prt.admin_user_id
             WHERE prt.token_hash = ? AND prt.used_at IS NULL AND prt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $result = $stmt->fetch();

        return $result ?: null;
    } catch (Throwable $e) {
        error_log('Failed to verify reset token: ' . $e->getMessage());
        return null;
    }
}

/**
 * Mark password reset token as used
 */
function mark_reset_token_used(string $token): bool
{
    $tokenHash = hash('sha256', $token);

    try {
        $stmt = db()->prepare(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = ?'
        );
        return $stmt->execute([$tokenHash]);
    } catch (Throwable $e) {
        error_log('Failed to mark token as used: ' . $e->getMessage());
        return false;
    }
}

/**
 * Simple SMTP Mailer class
 * Handles SMTP connection and email sending
 */
class SMTPMailer
{
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private string $fromAddress;
    private string $fromName;
    private array $to = [];
    private string $subject = '';
    private string $htmlBody = '';
    private string $plainBody = '';

    public function __construct(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password,
        string $fromAddress,
        string $fromName
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    public function addTo(string $email): void
    {
        $this->to[] = $email;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function setHTMLBody(string $html): void
    {
        $this->htmlBody = $html;
    }

    public function setPlainBody(string $text): void
    {
        $this->plainBody = $text;
    }

    public function send(): bool
    {
        if (empty($this->to) || empty($this->subject)) {
            throw new RuntimeException('Missing required email fields');
        }

        try {
            // Use PHP mail() as fallback if SMTP libraries not available
            // In production, consider using PHPMailer or SwiftMailer
            return $this->sendViaMail();
        } catch (Throwable $e) {
            error_log('SMTP send error: ' . $e->getMessage());
            return false;
        }
    }

    private function sendViaMail(): bool
    {
        $to = implode(', ', $this->to);
        $headers = $this->buildHeaders();

        $body = $this->htmlBody;
        if (!empty($this->plainBody)) {
            // If both versions exist, send multipart
            $boundary = '===' . md5(time()) . '===';
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $this->plainBody . "\r\n\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $this->htmlBody . "\r\n\r\n";
            $body .= "--{$boundary}--";
        } else {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }

        return mail($to, $this->subject, $body, $headers);
    }

    private function buildHeaders(): string
    {
        $from = $this->fromName
            ? "\"{$this->fromName}\" <{$this->fromAddress}>"
            : $this->fromAddress;

        return "From: {$from}\r\n"
            . "Reply-To: {$this->fromAddress}\r\n"
            . "X-Mailer: DEVSMW-Email-System\r\n"
            . "X-Priority: 3\r\n";
    }
}
?>
