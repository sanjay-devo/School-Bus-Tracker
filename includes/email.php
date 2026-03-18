<?php
/**
 * 📧 UPGRADED EMAIL SYSTEM
 * - Instant delivery (0-2 seconds)
 * - SMTP KeepAlive for multiple emails
 * - Retry logic (2 attempts)
 * - Email logging to database
 * - Error handling
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer (you need to download PHPMailer or use composer)
// For Hostinger without composer, download PHPMailer manually
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

// Global PHPMailer instance for SMTP KeepAlive
$GLOBALS['mailer'] = null;

/**
 * 🔥 Get or create PHPMailer instance with SMTP KeepAlive
 */
function getMailer() {
    global $pdo;
    
    if ($GLOBALS['mailer'] === null) {
        $mail = new PHPMailer(true);
        
        try {
            // 🔥 SMTP Settings with KeepAlive for speed
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->SMTPKeepAlive = true; // 🔥 Keep connection alive for multiple emails
            $mail->Timeout = defined('SMTP_TIMEOUT') ? SMTP_TIMEOUT : 10;
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            
            // Sender
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            
            $GLOBALS['mailer'] = $mail;
        } catch (Exception $e) {
            error_log('PHPMailer initialization failed: ' . $e->getMessage());
            return null;
        }
    }
    
    return $GLOBALS['mailer'];
}

/**
 * 🔥 Send email with retry logic and logging
 */
function sendEmail($to, $subject, $body, $messageType = 'general', $isHTML = true) {
    global $pdo;
    
    $retryCount = defined('EMAIL_RETRY_COUNT') ? EMAIL_RETRY_COUNT : 2;
    $attempt = 0;
    $lastError = '';
    
    while ($attempt <= $retryCount) {
        try {
            $mail = getMailer();
            
            if ($mail === null) {
                throw new Exception('Failed to initialize mailer');
            }
            
            // Clear previous recipients
            $mail->clearAddresses();
            $mail->clearAttachments();
            
            // Add recipient
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if ($isHTML) {
                $mail->AltBody = strip_tags($body);
            }
            
            // 🔥 Send email (should be instant with KeepAlive)
            $mail->send();
            
            // 🔥 Log successful email
            logEmail($to, $subject, $messageType, 'sent', null, $attempt);
            
            return true;
            
        } catch (Exception $e) {
            $lastError = $mail ? $mail->ErrorInfo : $e->getMessage();
            error_log("Email sending failed (attempt $attempt): $lastError");
            $attempt++;
            
            // Wait 1 second before retry
            if ($attempt <= $retryCount) {
                sleep(1);
            }
        }
    }
    
    // 🔥 Log failed email after all retries
    logEmail($to, $subject, $messageType, 'failed', $lastError, $retryCount);
    
    return false;
}

/**
 * 🔥 Log email to database
 */
function logEmail($recipient, $subject, $messageType, $status, $errorMessage = null, $retryCount = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (
                recipient_email, 
                subject, 
                message_type, 
                status, 
                error_message, 
                retry_count
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $recipient,
            $subject,
            $messageType,
            $status,
            $errorMessage,
            $retryCount
        ]);
    } catch (PDOException $e) {
        error_log('Email logging failed: ' . $e->getMessage());
    }
}

/**
 * 🔥 Send Trip Start Email
 */
function sendTripStartEmail($parentEmail, $parentName, $studentName, $busNumber, $tripId = null) {
    $subject = "🚌 Trip Started - $studentName's Bus is on the way";
    $trackingLink = SITE_URL . '/parent/dashboard.php';
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .button { background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Trip Started</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>$parentName</strong>,</p>
                <p>Great news! The bus (Number: <strong>$busNumber</strong>) for <strong>$studentName</strong> has just started its trip.</p>
                <div style='background-color: #e8f5e9; padding: 15px; margin: 15px 0; border-radius: 5px;'>
                    <p style='margin: 0;'>✅ <strong>Trip Status:</strong> Active</p>
                    <p style='margin: 5px 0 0 0;'>🕒 <strong>Started At:</strong> " . date('h:i A') . "</p>
                </div>
                <p>You can track the bus location in real-time on the parent portal.</p>
                <a href='$trackingLink' class='button' style='background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>📍 Track Bus Now</a>
                <p style='margin-top: 20px;'>Thank you,<br><strong>" . SITE_NAME . "</strong></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($parentEmail, $subject, $body, 'trip_start');
}

/**
 * 🔥 Send Trip End Email
 */
function sendTripEndEmail($parentEmail, $parentName, $studentName, $busNumber, $tripId = null) {
    $subject = "✅ Trip Completed - $studentName's Bus has reached destination";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2196F3; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Trip Completed</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>$parentName</strong>,</p>
                <p>The bus (Number: <strong>$busNumber</strong>) for <strong>$studentName</strong> has successfully completed its trip.</p>
                <div style='background-color: #e3f2fd; padding: 15px; margin: 15px 0; border-radius: 5px;'>
                    <p style='margin: 0;'>✅ <strong>Trip Status:</strong> Completed</p>
                    <p style='margin: 5px 0 0 0;'>🕒 <strong>Completed At:</strong> " . date('h:i A') . "</p>
                </div>
                <p>Thank you for using our tracking service!</p>
                <p style='margin-top: 20px;'>Best regards,<br><strong>" . SITE_NAME . "</strong></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($parentEmail, $subject, $body, 'trip_end');
}

function sendBusNearStopEmail($parentEmail, $parentName, $studentName, $busNumber, $stopName, $eta) {
    $subject = "Bus Near Stop - $studentName's Bus is approaching";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #FF9800; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .info { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Bus Approaching Stop</h2>
            </div>
            <div class='content'>
                <p>Dear $parentName,</p>
                <p>The bus (Number: <strong>$busNumber</strong>) for <strong>$studentName</strong> is approaching the stop.</p>
                <div class='info'>
                    <p><strong>Stop:</strong> $stopName</p>
                    <p><strong>Estimated Arrival:</strong> $eta</p>
                </div>
                <p>Please be ready at the stop.</p>
                <p>Thank you,<br>" . SITE_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($parentEmail, $subject, $body, 'bus_near_stop');
}

function sendSOSEmail($adminEmail, $driverName, $busNumber, $latitude, $longitude) {
    $subject = "⚠️ SOS ALERT - Emergency from Bus $busNumber";
    $mapLink = "https://www.google.com/maps?q=$latitude,$longitude";
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f44336; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .alert { background-color: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0; }
            .button { background-color: #f44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>⚠️ SOS EMERGENCY ALERT</h2>
            </div>
            <div class='content'>
                <div class='alert'>
                    <p><strong>URGENT:</strong> An SOS alert has been triggered!</p>
                </div>
                <p><strong>Driver:</strong> $driverName</p>
                <p><strong>Bus Number:</strong> $busNumber</p>
                <p><strong>Location:</strong> Lat: $latitude, Lng: $longitude</p>
                <a href='$mapLink' class='button' target='_blank'>View Location on Map</a>
                <p>Please take immediate action.</p>
                <p>" . SITE_NAME . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($adminEmail, $subject, $body, 'sos');
}

/**
 * 🔥 Send New User Creation Email
 */
function sendNewUserEmail($userEmail, $userName, $role, $password = null) {
    $subject = "🎉 Welcome to " . SITE_NAME;
    $loginLink = SITE_URL;
    
    $passwordInfo = $password ? "<p><strong>Temporary Password:</strong> $password</p><p style='color: #d32f2f;'><em>Please change your password after first login.</em></p>" : "";
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #673AB7; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .button { background-color: #673AB7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎉 Welcome!</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>$userName</strong>,</p>
                <p>Your account has been successfully created on <strong>" . SITE_NAME . "</strong>.</p>
                <div style='background-color: #f3e5f5; padding: 15px; margin: 15px 0; border-radius: 5px;'>
                    <p style='margin: 0;'><strong>Role:</strong> " . ucfirst($role) . "</p>
                    <p style='margin: 5px 0 0 0;'><strong>Email:</strong> $userEmail</p>
                    $passwordInfo
                </div>
                <a href='$loginLink' class='button'>Login Now</a>
                <p style='margin-top: 20px;'>If you have any questions, please contact the administrator.</p>
                <p>Thank you,<br><strong>" . SITE_NAME . "</strong></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($userEmail, $subject, $body, 'new_user');
}

/**
 * 🔥 Close SMTP connection when done
 */
function closeMailer() {
    if ($GLOBALS['mailer'] !== null) {
        try {
            $GLOBALS['mailer']->smtpClose();
        } catch (Exception $e) {
            // Ignore close errors
        }
        $GLOBALS['mailer'] = null;
    }
}
?>
