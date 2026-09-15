<?php

$localConfig = file_exists(__DIR__ . '/smtp.local.php') ? require __DIR__ . '/smtp.local.php' : [];

return [
    'host' => getenv('SMTP_HOST') ?: ($localConfig['host'] ?? 'smtp.gmail.com'),
    'port' => (int)(getenv('SMTP_PORT') ?: ($localConfig['port'] ?? 587)),
    'username' => getenv('SMTP_USER') ?: ($localConfig['username'] ?? ''),
    'password' => getenv('SMTP_PASS') ?: ($localConfig['password'] ?? ''),
    'encryption' => getenv('SMTP_SECURE') ?: ($localConfig['encryption'] ?? 'tls'),
    'from_email' => getenv('SMTP_FROM_EMAIL') ?: ($localConfig['from_email'] ?? ''),
    'from_name' => getenv('SMTP_FROM_NAME') ?: ($localConfig['from_name'] ?? 'Shantini Crackers'),
    'timeout' => 15,
];
