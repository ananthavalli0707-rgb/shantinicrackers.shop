<?php

function send_smtp_email(string $toEmail, string $subject, string $body, bool $isHtml = true): array {
    $config = require __DIR__ . '/../config/smtp.php';

    $host = $config['host'] ?? '';
    $port = (int)($config['port'] ?? 587);
    $username = $config['username'] ?? '';
    $password = $config['password'] ?? '';
    $encryption = strtolower($config['encryption'] ?? 'tls');
    $fromEmail = $config['from_email'] ?? 'noreply@shantinicrackers.com';
    $fromName = $config['from_name'] ?? 'Shantini Crackers';
    $timeout = (int)($config['timeout'] ?? 15);

    if (empty($host) || empty($username) || empty($password)) {
        return [
            'success' => false,
            'smtp_configured' => false,
            'message' => 'SMTP credentials are not configured in config/smtp.php or environment variables.'
        ];
    }

    $socketHost = ($encryption === 'ssl') ? 'ssl://' . $host : $host;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client("{$socketHost}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        return [
            'success' => false,
            'smtp_configured' => true,
            'message' => "SMTP connection failed to {$host}:{$port} - {$errstr} ({$errno})"
        ];
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function() use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };

    $sendCommand = function(string $command) use ($socket, $readResponse) {
        fputs($socket, $command . "\r\n");
        return $readResponse();
    };

    try {
        $greeting = $readResponse();
        if (substr($greeting, 0, 3) !== '220') {
            throw new Exception("Unexpected SMTP greeting: {$greeting}");
        }

        $clientHost = gethostname() ?: 'localhost';
        $ehlo = $sendCommand("EHLO {$clientHost}");
        if (substr($ehlo, 0, 3) !== '250') {
            throw new Exception("EHLO failed: {$ehlo}");
        }

        if ($encryption === 'tls') {
            $starttls = $sendCommand("STARTTLS");
            if (substr($starttls, 0, 3) !== '220') {
                throw new Exception("STARTTLS failed: {$starttls}");
            }

            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                throw new Exception("Failed to establish TLS encryption stream.");
            }

            $ehlo = $sendCommand("EHLO {$clientHost}");
            if (substr($ehlo, 0, 3) !== '250') {
                throw new Exception("EHLO after STARTTLS failed: {$ehlo}");
            }
        }

        $auth = $sendCommand("AUTH LOGIN");
        if (substr($auth, 0, 3) !== '334') {
            throw new Exception("AUTH LOGIN failed: {$auth}");
        }

        $userResp = $sendCommand(base64_encode($username));
        if (substr($userResp, 0, 3) !== '334') {
            throw new Exception("SMTP username failed: {$userResp}");
        }

        $passResp = $sendCommand(base64_encode($password));
        if (substr($passResp, 0, 3) !== '235') {
            throw new Exception("SMTP authentication failed for user {$username}: {$passResp}");
        }

        $mailFrom = $sendCommand("MAIL FROM:<{$fromEmail}>");
        if (substr($mailFrom, 0, 3) !== '250') {
            throw new Exception("MAIL FROM failed: {$mailFrom}");
        }

        $rcptTo = $sendCommand("RCPT TO:<{$toEmail}>");
        if (substr($rcptTo, 0, 3) !== '250') {
            throw new Exception("RCPT TO failed: {$rcptTo}");
        }

        $dataResp = $sendCommand("DATA");
        if (substr($dataResp, 0, 3) !== '354') {
            throw new Exception("DATA command failed: {$dataResp}");
        }

        $contentType = $isHtml ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8';
        $headers = [
            "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>",
            "To: <{$toEmail}>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: {$contentType}",
            "Date: " . date('r'),
            "X-Mailer: Shantini Crackers SMTP Mailer"
        ];

        $emailData = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        $sendBody = $sendCommand($emailData);

        if (substr($sendBody, 0, 3) !== '250') {
            throw new Exception("Sending email payload failed: {$sendBody}");
        }

        $sendCommand("QUIT");
        fclose($socket);

        return [
            'success' => true,
            'smtp_configured' => true,
            'message' => 'Email sent successfully via SMTP.'
        ];
    } catch (Exception $e) {
        if (is_resource($socket)) {
            @fclose($socket);
        }
        return [
            'success' => false,
            'smtp_configured' => true,
            'message' => 'SMTP error: ' . $e->getMessage()
        ];
    }
}
