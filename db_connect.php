<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = mysqli_connect("localhost", "root", "", "workerindex");

if (!$conn) {
    die("Nie udało się połączyć z bazą: " . mysqli_connect_error());
} else {
    //echo "Połączono z bazą!";
}
?>