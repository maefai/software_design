<?php
// logout.php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page (Fixed: now points to index.php instead of account.php)
header("Location: index.php");
exit();
?>