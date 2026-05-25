<?php
// includes/mail_helper.php

// Ensure oauth_config is loaded so we have the constants
require_once __DIR__ . '/oauth_config.php';

/**
 * Sends a password reset email using Mailjet API (or falls back to php mail() if not configured)
 */
function sendResetEmail($to, $name, $reset_link) {
    $subject = "Password Reset Request - GreenBridge OJT Portal";
    
    $textMessage = "Hello $name,\n\n";
    $textMessage .= "Click the link below to reset your password:\n\n";
    $textMessage .= $reset_link . "\n\n";
    $textMessage .= "This link expires in 1 hour.\n\n";
    $textMessage .= "If you didn't request this, please ignore this email.\n\n";
    $textMessage .= "Best regards,\nGreenBridge Team";

    // Premium designed HTML email matching GreenBridge color scheme
    $htmlMessage = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8e0; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <h2 style='color: #1a3a24; font-family: Georgia, serif;'>GREEN BRIDGE</h2>
            <p style='font-size: 14px; color: #5a6e5f;'>OJT Portal Password Reset</p>
        </div>
        <hr style='border: 0; border-top: 1px solid #e2e8e0;' />
        <div style='padding: 10px 0;'>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>We received a request to reset the password for your GreenBridge account. Click the button below to proceed:</p>
            <div style='text-align: center; margin: 25px 0;'>
                <a href='" . htmlspecialchars($reset_link) . "' style='background-color: #4caf78; color: white; padding: 12px 24px; text-decoration: none; border-radius: 40px; font-weight: bold; display: inline-block; box-shadow: 0 4px 6px rgba(76,175,120,0.15);'>Reset Password</a>
            </div>
            <p style='font-size: 13px; color: #5a6e5f; background-color: #f9fbf8; padding: 12px; border-radius: 6px; border: 1px solid #e2e8e0;'>
                <strong>Link expiration:</strong> This link will expire in 1 hour.<br>
                If you did not request this reset, you can safely ignore this email.
            </p>
        </div>
        <hr style='border: 0; border-top: 1px solid #e2e8e0; margin-top: 20px;' />
        <p style='font-size: 11px; color: #5a6e5f; text-align: center; margin-top: 20px;'>
            GreenBridge OJT Portal &copy; " . date('Y') . "
        </p>
    </div>
    ";
    
    return sendMailjetEmail($to, $name, $subject, $textMessage, $htmlMessage);
}

/**
 * Sends an email using the Mailjet Send API v3.1
 */
function sendMailjetEmail($toEmail, $toName, $subject, $textPart, $htmlPart = null) {
    $senderEmail = defined('MAILJET_SENDER_EMAIL') ? MAILJET_SENDER_EMAIL : 'noreply@greenbridgedlsud.site';
    $senderName = defined('MAILJET_SENDER_NAME') ? MAILJET_SENDER_NAME : 'GreenBridge OJT Portal';

    // If Mailjet is not configured, fall back to native PHP mail()
    if (!defined('MAILJET_API_KEY') || MAILJET_API_KEY === 'your-mailjet-api-key' ||
        !defined('MAILJET_SECRET_KEY') || MAILJET_SECRET_KEY === 'your-mailjet-secret-key') {
        
        $headers = "From: " . $senderName . " <" . $senderEmail . ">\r\n";
        if ($htmlPart) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            return mail($toEmail, $subject, $htmlPart, $headers);
        } else {
            return mail($toEmail, $subject, $textPart, $headers);
        }
    }

    $body = [
        'Messages' => [
            [
                'From' => [
                    'Email' => $senderEmail,
                    'Name' => $senderName
                ],
                'To' => [
                    [
                        'Email' => $toEmail,
                        'Name' => $toName
                    ]
                ],
                'Subject' => $subject,
                'TextPart' => $textPart,
                'HTMLPart' => $htmlPart ?: nl2br(htmlspecialchars($textPart))
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mailjet.com/v3.1/send");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, MAILJET_API_KEY . ":" . MAILJET_SECRET_KEY);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $resData = json_decode($response, true);
        if (isset($resData['Messages'][0]['Status']) && $resData['Messages'][0]['Status'] === 'success') {
            return true;
        }
    }
    
    error_log("Mailjet Send Failed (HTTP $httpCode): " . $response);
    return false;
}

function sendWelcomePendingEmail($to, $name, $user_type) {
    $subject = "Registration Received - Pending Verification";
    
    $textMessage = "Hello $name,\n\n";
    $textMessage .= "Thank you for registering at GreenBridge OJT Portal as a " . ucfirst($user_type) . ".\n\n";
    $textMessage .= "Your account registration has been received and is currently pending verification by our administrators.\n";
    $textMessage .= "You will receive another email once your account has been reviewed and activated.\n\n";
    $textMessage .= "Best regards,\nGreenBridge Team";

    $htmlMessage = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8e0; border-radius: 12px; background-color: #ffffff;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <h2 style='color: #1a3a24; font-family: Georgia, serif;'>GREEN BRIDGE</h2>
            <p style='font-size: 14px; color: #5a6e5f;'>Registration Pending Verification</p>
        </div>
        <hr style='border: 0; border-top: 1px solid #e2e8e0;' />
        <div style='padding: 10px 0;'>
            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Thank you for registering on the GreenBridge OJT Portal as a <strong>" . htmlspecialchars(ucfirst($user_type)) . "</strong>.</p>
            <p>Your registration is currently <strong>pending approval</strong> from our system administrators. We are reviewing your submitted documents and credentials.</p>
            <p style='font-size: 13px; color: #5a6e5f; background-color: #f9fbf8; padding: 12px; border-radius: 6px; border: 1px solid #e2e8e0;'>
                <strong>Next Steps:</strong> You don't need to do anything. We will send you an email notification as soon as your account has been approved and activated.
            </p>
        </div>
        <hr style='border: 0; border-top: 1px solid #e2e8e0; margin-top: 20px;' />
        <p style='font-size: 11px; color: #5a6e5f; text-align: center; margin-top: 20px;'>
            GreenBridge OJT Portal &copy; " . date('Y') . "
        </p>
    </div>
    ";

    return sendMailjetEmail($to, $name, $subject, $textMessage, $htmlMessage);
}

function generateResetToken() {
    return bin2hex(random_bytes(32));
}
?>