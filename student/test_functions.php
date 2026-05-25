<?php
echo "<h2>Testing Functions</h2>";

require_once '../includes/auth_functions.php';

echo "<h3>Function checks:</h3>";
echo "<ul>";
echo "<li>function_exists('requireStudent'): " . (function_exists('requireStudent') ? "✅ YES" : "❌ NO") . "</li>";
echo "<li>function_exists('getStudentData'): " . (function_exists('getStudentData') ? "✅ YES" : "❌ NO") . "</li>";
echo "<li>function_exists('isStudent'): " . (function_exists('isStudent') ? "✅ YES" : "❌ NO") . "</li>";
echo "<li>function_exists('requireLogin'): " . (function_exists('requireLogin') ? "✅ YES" : "❌ NO") . "</li>";
echo "</ul>";

echo "<h3>SITE_URL constant:</h3>";
echo defined('SITE_URL') ? "✅ SITE_URL = " . SITE_URL : "❌ SITE_URL not defined";
?>