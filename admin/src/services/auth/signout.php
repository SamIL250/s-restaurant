<?php
session_start();

// Clear the restaurant token cookie
setcookie("restaurant_token", "", time() + (24 * 60 * 60), "/");

// Clear session data
session_destroy();

// Redirect to signin page with success message
$_SESSION['notification'] = "You have been successfully signed out.";
header('location:../../../auth/signin');
exit();
?> 
