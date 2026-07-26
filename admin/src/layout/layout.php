<?php
session_start();
include './config/config.php';
include './config/roles.php';
include './src/services/auth/auth-state.php';

$current_user = checkAuthState();
enforcePageAccess();
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default">

<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Resto Restaurant | Management System</title>
    <meta name="msapplication-TileImage" content="src/assets/img/icons/logo.png">
    <meta name="theme-color" content="#f97316">
    <?php
    include './src/components/links.php'
    ?>
    <script>
        var phoenixIsRTL = window.config.config.phoenixIsRTL;
        if (phoenixIsRTL) {
            var linkDefault = document.getElementById('style-default');
            var userLinkDefault = document.getElementById('user-style-default');
            linkDefault.setAttribute('disabled', true);
            userLinkDefault.setAttribute('disabled', true);
            document.querySelector('html').setAttribute('dir', 'rtl');
        } else {
            var linkRTL = document.getElementById('style-rtl');
            var userLinkRTL = document.getElementById('user-style-rtl');
            linkRTL.setAttribute('disabled', true);
            userLinkRTL.setAttribute('disabled', true);
        }
    </script>

</head>

<body>

    <main class="main" id="top">
        <?php
        include './src/components/toasts.php';
        include 'src/components/widgets/asidebar.php';
        include 'src/components/widgets/topbar.php';
        ?>


        <?php
        include './src/components/widgets/customization.php'
        ?>
        <div class="content">
            <!-- //main contents -->