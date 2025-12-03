<?php
$localhost = 'localhost';
$user = 'root';
$pass = '';
$database = 'test';

$conn = new mysqli($localhost, $user, $pass, $database);

if ($conn->connect_error) { 
    die(''. $conn->connect_error);
}


?>