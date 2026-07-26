<?php
// Database configuration with error handling
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'tacos_v2';

// Set MySQL connection options
$options = array(
  MYSQLI_OPT_CONNECT_TIMEOUT => 10,
  MYSQLI_OPT_READ_TIMEOUT => 30,
  MYSQLI_INIT_COMMAND => "SET NAMES utf8"
);

// Create connection with error handling
$conn = mysqli_init();
if (!$conn) {
  die('mysqli_init failed');
}

// Set connection options
foreach ($options as $option => $value) {
  mysqli_options($conn, $option, $value);
}

// Connect to database
if (!mysqli_real_connect($conn, $host, $username, $password, $database)) {
  die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, 'utf8');

// Check connection
if (mysqli_connect_error()) {
  die('Database connection failed: ' . mysqli_connect_error());
}
 