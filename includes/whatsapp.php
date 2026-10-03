<?php
// includes/whatsapp.php

/**
 * Send a standard text message via Meta WhatsApp Cloud API.
 * Note: Free-form text messages can only be sent if the recipient has sent a message
 * to the business within the last 24 hours (Customer Service Window), or if you are
 * messaging a registered test number in the Meta developer console.
 */
function send_whatsapp_message($to, $message) {
    $config = require __DIR__ . '/../config/whatsapp.php';
    
    if ($config['access_token'] === 'YOUR_META_ACCESS_TOKEN') {
        return ['success' => false, 'error' => 'WhatsApp API not configured. Please update config/whatsapp.php'];
    }

    $url = "https://graph.facebook.com/{$config['version']}/{$config['phone_number_id']}/messages";
    
    // Ensure phone number has country code but no '+'
    $to = preg_replace('/[^0-9]/', '', $to);
    
    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $to,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $message
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP testing
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'response' => $responseData];
    }
    
    return ['success' => false, 'error' => $responseData['error']['message'] ?? 'Unknown error', 'raw' => $response];
}

/**
 * Send a pre-approved template message via Meta WhatsApp Cloud API.
 * This can be sent at any time, initiating a conversation (Business-Initiated).
 */
function send_whatsapp_template($to, $templateName, $languageCode = 'en_US', $components = []) {
    $config = require __DIR__ . '/../config/whatsapp.php';
    
    if ($config['access_token'] === 'YOUR_META_ACCESS_TOKEN') {
        return ['success' => false, 'error' => 'WhatsApp API not configured.'];
    }

    $url = "https://graph.facebook.com/{$config['version']}/{$config['phone_number_id']}/messages";
    $to = preg_replace('/[^0-9]/', '', $to);
    
    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $to,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => ['code' => $languageCode],
        ]
    ];
    
    if (!empty($components)) {
        $data['template']['components'] = $components;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local XAMPP testing
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'response' => $responseData];
    }
    
    return ['success' => false, 'error' => $responseData['error']['message'] ?? 'Unknown error'];
}
