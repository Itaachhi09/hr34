<?php
/**
 * Gmail Configuration for H Vill Hospital HR System
 * This file sets the Gmail credentials for 2FA emails
 */

// Set Gmail credentials for 2FA emails
putenv('GMAIL_USER=deguroj@gmail.com');
putenv('GMAIL_APP_PASSWORD=jrhd bqfh lrch ushe');

// You can also set them directly in $_ENV
$_ENV['GMAIL_USER'] = 'deguroj@gmail.com';
$_ENV['GMAIL_APP_PASSWORD'] = 'jrhd bqfh lrch ushe';

// Don't output anything to avoid JSON parsing errors
// echo "Gmail configuration loaded. Please update the GMAIL_APP_PASSWORD with your actual Gmail App Password.";
?>
