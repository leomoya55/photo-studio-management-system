<?php
/**
 * Email helper: best-effort HTML email via SendGrid API fallback to PHP mail()
 * Usage: send_best_effort_email($to, $subject, $html, $fromName, $fromEmail)
 */

function send_best_effort_email($to, $subject, $html, $fromName = 'Legend Academy', $fromEmail = 'noreply@legenddanceacademy.com') {
    $to = trim((string)$to);
    if ($to === '') { return false; }

    // Try SendGrid if API key present
    $apiKey = getenv('SENDGRID_API_KEY') ?: '';
    if ($apiKey) {
        $payload = [
            'personalizations' => [[ 'to' => [[ 'email' => $to ]] ]],
            'from' => [ 'email' => $fromEmail, 'name' => $fromName ],
            'subject' => $subject,
            'content' => [[ 'type' => 'text/html', 'value' => $html ]],
        ];
        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($code >= 200 && $code < 300) {
            return true;
        } else {
            error_log('SendGrid email failed: HTTP ' . $code . ' ' . $err . ' body=' . substr((string)$resp,0,500));
        }
    }

    // Fallback: PHP mail()
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . sprintf('%s <%s>', $fromName, $fromEmail)
    ];
    $ok = @mail($to, $subject, $html, implode("\r\n", $headers));
    return $ok ? true : false;
}
