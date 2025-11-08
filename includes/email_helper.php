<?php
/**
 * Enhanced Email helper for Legend Academy
 * Provides:
 *  - send_best_effort_email (simple HTML)
 *  - send_best_effort_email_with_attachments (HTML + attachments)
 *  - send_branded_email (consistent branded wrapper)
 *  - build_branded_email (returns branded HTML only)
 */

// Ensure composer autoload (PHPMailer, etc.) is available when helper is included standalone
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

function legend_env($key, $default = '') {
    $sources = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
    foreach ($sources as $value) {
        if ($value !== false && $value !== null && $value !== '') {
            return $value;
        }
    }
    return $default;
}

function legend_smtp_config() {
    $host = legend_env('SMTP_HOST');
    $user = legend_env('SMTP_USER');
    $pass = legend_env('SMTP_PASS');
    if (!$host || !$user || !$pass) {
        return null;
    }
    return [
        'host' => $host,
        'user' => $user,
        'pass' => $pass,
        'port' => (int)(legend_env('SMTP_PORT') ?: 587),
        'secure' => strtolower(legend_env('SMTP_SECURE') ?: 'tls')
    ];
}

function send_via_phpmailer($to, $subject, $html, $attachments, $fromName, $fromEmail) {
    $phpMailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
    if (!class_exists($phpMailerClass)) {
        return false;
    }
    $smtp = legend_smtp_config();
    if (!$smtp) {
        return false;
    }
    $mailer = new $phpMailerClass(true);
    try {
        $mailer->isSMTP();
        $mailer->Host = $smtp['host'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtp['user'];
        $mailer->Password = $smtp['pass'];
        $secure = $smtp['secure'];
        if ($secure === 'ssl') {
            $const = $phpMailerClass . '::ENCRYPTION_SMTPS';
            $mailer->SMTPSecure = defined($const) ? constant($const) : 'ssl';
        } else {
            $const = $phpMailerClass . '::ENCRYPTION_STARTTLS';
            $mailer->SMTPSecure = defined($const) ? constant($const) : 'tls';
        }
        $mailer->Port = $smtp['port'] > 0 ? $smtp['port'] : 587;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($to);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $html;
        foreach ($attachments as $att) {
            if (isset($att['content'], $att['name'])) {
                $ctype = $att['type'] ?? 'application/octet-stream';
                $mailer->addStringAttachment($att['content'], $att['name'], 'base64', $ctype);
            }
        }
        $mailer->send();
        return true;
    } catch (Throwable $t) {
        error_log('PHPMailer unexpected error: ' . $t->getMessage());
        return false;
    }
}

// --- Core simple sender (HTML only) ---
function send_best_effort_email($to, $subject, $html, $fromName = 'Legend Academy', $fromEmail = 'noreply@legenddanceacademy.com') {
    return send_best_effort_email_with_attachments($to, $subject, $html, [], $fromName, $fromEmail);
}

// --- Core sender with optional attachments ---
// $attachments: array of ['name'=>string, 'type'=>mime, 'content'=>raw bytes]
function send_best_effort_email_with_attachments($to, $subject, $html, $attachments = [], $fromName = 'Legend Academy', $fromEmail = 'noreply@legenddanceacademy.com') {
    $to = trim((string)$to);
    if ($to === '') { return false; }

    $envFromEmail = legend_env('SENDER_EMAIL');
    if ($envFromEmail) { $fromEmail = $envFromEmail; }
    $envFromName = legend_env('SENDER_NAME');
    if ($envFromName) { $fromName = $envFromName; }

    // TestMail (capture sandbox) integration: if TESTMAIL_NAMESPACE is set, redirect outbound
    $testmailNs = getenv('TESTMAIL_NAMESPACE') ?: '';
    if ($testmailNs) {
        // Preserve original recipient in a header note inside HTML for traceability
        $original = $to;
        // Build a safe tag from original email local part for uniqueness (strip domain, non-alphanum)
        $localTag = preg_replace('/[^a-z0-9]+/i','-', explode('@',$original)[0]);
        // Route all mail to namespace inbox (anything@<ns>.testmail.app). Allow grouping by tag + timestamp.
        $to = 'legend-' . $localTag . '-' . date('His') . '@' . $testmailNs . '.testmail.app';
        // Annotate original target inside body (non-intrusive small footer note)
        $html .= '<div style="margin-top:28px;font-size:11px;color:#999">[TestMail capture: original recipient ' . htmlspecialchars($original,ENT_QUOTES) . ']</div>';
    }

    $apiKey = legend_env('SENDGRID_API_KEY');
    if ($apiKey) {
        $payload = [
            'personalizations' => [[ 'to' => [[ 'email' => $to ]] ]],
            'from' => [ 'email' => $fromEmail, 'name' => $fromName ],
            'subject' => $subject,
            'content' => [[ 'type' => 'text/html', 'value' => $html ]],
        ];
        if (!empty($attachments)) {
            $payload['attachments'] = array_map(function($att){
                return [
                    'content' => base64_encode($att['content']),
                    'type'    => $att['type'],
                    'filename'=> $att['name'],
                    'disposition' => 'attachment'
                ];
            }, $attachments);
        }
        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($code >= 200 && $code < 300) {
            return true;
        }
        error_log('SendGrid email failed: HTTP ' . $code . ' ' . $err . ' body=' . substr((string)$resp,0,500));
        // fall through to mail() fallback
    }

    // Attempt SMTP via PHPMailer if configured
    if (send_via_phpmailer($to, $subject, $html, $attachments, $fromName, $fromEmail)) {
        return true;
    }

    // Fallback: build headers & possibly multipart for attachments
    if (empty($attachments)) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . sprintf('%s <%s>', $fromName, $fromEmail)
        ];
        if (!empty($testmailNs)) {
            $headers[] = 'X-TestMail-Namespace: ' . $testmailNs;
        }
        return @mail($to, $subject, $html, implode("\r\n", $headers)) ? true : false;
    }

    $boundary = 'LEGEND_BOUNDARY_' . md5(uniqid('', true));
    $headers = [
        'MIME-Version: 1.0',
        'From: ' . sprintf('%s <%s>', $fromName, $fromEmail),
        'Content-Type: multipart/mixed; boundary="' . $boundary . '"'
    ];
    if (!empty($testmailNs)) {
        $headers[] = 'X-TestMail-Namespace: ' . $testmailNs;
    }
    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $html . "\r\n";
    foreach ($attachments as $att) {
        if (!isset($att['content']) || !isset($att['name'])) continue;
        $ctype = $att['type'] ?? 'application/octet-stream';
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$ctype}; name=\"" . addslashes($att['name']) . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"" . addslashes($att['name']) . "\"\r\n\r\n";
        $body .= chunk_split(base64_encode($att['content'])) . "\r\n";
    }
    $body .= "--{$boundary}--\r\n";
    return @mail($to, $subject, $body, implode("\r\n", $headers)) ? true : false;
}

// --- Branding helpers ---
function branded_color_variants($accent) {
    // Simple lighten/darken adjustments (fallbacks if not hex)
    $accent = preg_match('/^#?[0-9a-f]{6}$/i', $accent) ? ltrim($accent, '#') : 'ff6600';
    $r = hexdec(substr($accent,0,2));
    $g = hexdec(substr($accent,2,2));
    $b = hexdec(substr($accent,4,2));
    $light = sprintf('#%02x%02x%02x', min(255,$r+26), min(255,$g+26), min(255,$b+26));
    $dark  = sprintf('#%02x%02x%02x', max(0,$r-26), max(0,$g-26), max(0,$b-26));
    return [$accent ? ('#'.$accent) : '#ff6600', $light, $dark];
}

function build_branded_email($headline, $bodyHtml, $accent = '#ff6600', $statusLabel = '', $footerNote = '') {
    list($base, $light, $dark) = branded_color_variants($accent);
    $safeHeadline = htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
    $badge = '';
    if ($statusLabel !== '') {
        $safeStatus = htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');
        $badge = "<span style=\"display:inline-block;background:{$dark};color:#fff;padding:4px 10px;border-radius:14px;font-size:12px;letter-spacing:.5px;margin-top:6px\">{$safeStatus}</span>";
    }
    $footerNote = $footerNote ?: 'Este es un correo automático, por favor no responder directamente.';
    $year = date('Y');
    return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>{$safeHeadline}</title></head><body style=\"margin:0;font-family:Arial,sans-serif;background:#f5f6fa;color:#222;\">"
        ."<div style=\"max-width:640px;margin:0 auto;\">"
        ."<div style=\"background:linear-gradient(135deg, {$base} 0%, {$light} 100%);padding:24px 28px;color:#fff;border-radius:18px 18px 0 0;\">"
        ."<h1 style=\"margin:0;font-size:22px;font-weight:600;\">{$safeHeadline}</h1>{$badge}"
        ."</div>"
        ."<div style=\"background:#ffffff;padding:28px;border:1px solid #e6e8ef;border-top:none;border-radius:0 0 18px 18px;\">"
        .$bodyHtml
        ."<hr style=\"margin:30px 0;border:none;border-top:1px solid #eee\">"
        ."<div style=\"font-size:12px;color:#666;line-height:1.4\">{$footerNote}<br>&copy; {$year} Legend Dance Academy</div>"
        ."</div></div></body></html>";
}

function send_branded_email($to, $subject, $headline, $bodyHtml, $statusLabel = '', $accent = '#ff6600', $attachments = [], $fromName = 'Legend Academy', $fromEmail = 'noreply@legenddanceacademy.com') {
    $html = build_branded_email($headline, $bodyHtml, $accent, $statusLabel);
    return send_best_effort_email_with_attachments($to, $subject, $html, $attachments, $fromName, $fromEmail);
}
