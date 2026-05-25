<?php
// test_mailjet.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/mail_helper.php';

$to = $_GET['to'] ?? '';
$name = $_GET['name'] ?? 'Test User';
$link = SITE_URL . "reset_password.php?token=testtoken123";

if (empty($to)) {
    echo "<h2>Mailjet Send Tester</h2>";
    echo "<p>Please add a <code>?to=your_email@example.com</code> parameter to the URL to test sending.</p>";
    echo "<p>Example: <code>http://localhost:8000/test_mailjet.php?to=myaddress@gmail.com</code></p>";
    exit();
}

echo "<h3>Mailjet Configuration Status:</h3>";
echo "<ul>";
echo "<li><strong>API Key:</strong> " . (defined('MAILJET_API_KEY') ? htmlspecialchars(substr(MAILJET_API_KEY, 0, 5)) . "..." : "NOT DEFINED") . "</li>";
echo "<li><strong>Secret Key:</strong> " . (defined('MAILJET_SECRET_KEY') ? htmlspecialchars(substr(MAILJET_SECRET_KEY, 0, 5)) . "..." : "NOT DEFINED") . "</li>";
echo "<li><strong>Sender Email:</strong> " . (defined('MAILJET_SENDER_EMAIL') ? htmlspecialchars(MAILJET_SENDER_EMAIL) : "NOT DEFINED") . "</li>";
echo "<li><strong>Sender Name:</strong> " . (defined('MAILJET_SENDER_NAME') ? htmlspecialchars(MAILJET_SENDER_NAME) : "NOT DEFINED") . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p>Attempting to send test reset password email to: <strong>" . htmlspecialchars($to) . "</strong>...</p>";

// Temporarily capture or check return info of the sendMailjetEmail function
function testSendMailjetEmail($toEmail, $toName, $subject, $textPart, $htmlPart = null) {
    $senderEmail = defined('MAILJET_SENDER_EMAIL') ? MAILJET_SENDER_EMAIL : 'noreply@greenbridgedlsud.site';
    $senderName = defined('MAILJET_SENDER_NAME') ? MAILJET_SENDER_NAME : 'GreenBridge OJT Portal';

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

    echo "<h4>Mailjet Send API Response:</h4>";
    echo "<pre>HTTP Status Code: $httpCode\n";
    echo "Response Body: " . htmlspecialchars($response) . "</pre>";

    if ($httpCode === 200) {
        $resData = json_decode($response, true);
        if (isset($resData['Messages'][0]['Status']) && $resData['Messages'][0]['Status'] === 'success') {
            return true;
        }
    }
    return false;
}

$subject = "Password Reset Request - GreenBridge OJT Portal";
$textMessage = "Hello $name,\n\nClick this link to test your reset: $link";
$htmlMessage = "<h3>Hello $name</h3><p>Click <a href='$link'>here</a> to reset your password.</p>";

$success = testSendMailjetEmail($to, $name, $subject, $textMessage, $htmlMessage);

if ($success) {
    echo "<h3 style='color: green;'>Success! Email sent successfully. Check your spam/junk folder too.</h3>";
} else {
    echo "<h3 style='color: red;'>Failed to send. See the response details above.</h3>";
}
?>
