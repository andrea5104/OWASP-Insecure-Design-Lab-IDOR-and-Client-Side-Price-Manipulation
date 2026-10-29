<?php
$host = "localhost";
$db = "ecommerce_demo";
$user = "utente";
$pass = "password";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

?>
