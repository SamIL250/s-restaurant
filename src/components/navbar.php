<?php
$currentCustomer = $currentCustomer ?? null;
$siteBase = SITE_WEB_PATH;
$isLoggedIn = $currentCustomer !== null;
?>

<header id="header" class="header fixed-top">

    <div class="topbar d-flex align-items-center">
      <div class="container d-flex justify-content-end justify-content-md-between">
        <div class="contact-info d-flex align-items-center">
          <i class="bi bi-phone d-flex align-items-center d-none d-lg-block"><span>+250 788 277 182</span></i>
          <i class="bi bi-clock ms-4 d-none d-lg-flex align-items-center"><span>Mon-Sat: 9:00 AM - 23:00 PM</span></i>
        </div>
        <a href="<?php echo $siteBase; ?>/#book-a-table" class="cta-btn">Book a table</a>
      </div>
    </div>

    <div class="branding d-flex align-items-cente">

      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="<?php echo $siteBase; ?>/" class="logo d-flex align-items-center">
          <img src="<?php echo $siteBase; ?>/assets/img/logo.png" alt="">
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="<?php echo $siteBase; ?>/#hero">Home</a></li>
            <li><a href="<?php echo $siteBase; ?>/#menu">Menu</a></li>
            <li><a href="<?php echo $siteBase; ?>/#promotions">Promotions</a></li>
            <li><a href="<?php echo $siteBase; ?>/#events">Events</a></li>
            <li><a href="<?php echo $siteBase; ?>/#book-a-table">Book a Table</a></li>
            <li><a href="<?php echo $siteBase; ?>/#gallery">Gallery</a></li>
            <li><a href="<?php echo $siteBase; ?>/#location">Find Us</a></li>
            <?php if ($isLoggedIn): ?>
            <li class="nav-auth-item"><a href="<?php echo $siteBase; ?>/account" class="nav-auth-btn nav-auth-btn-account">My Account</a></li>
            <li class="nav-auth-item"><a href="#" id="customerLogoutLink" class="nav-auth-btn nav-auth-btn-logout">Sign Out</a></li>
            <?php else: ?>
            <li class="nav-auth-item"><a href="<?php echo $siteBase; ?>/login" class="nav-auth-btn nav-auth-btn-signin">Sign In</a></li>
            <li class="nav-auth-item"><a href="<?php echo $siteBase; ?>/register" class="nav-auth-btn nav-auth-btn-register">Register</a></li>
            <?php endif; ?>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

      </div>

    </div>

  </header>
