<?php
$host = "localhost";
$user = "root";
$password = ""; // leave empty if default XAMPP
$database = "ccs_sitin";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>