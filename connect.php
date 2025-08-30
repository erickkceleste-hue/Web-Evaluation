<?php 

$host = "localhost";
$user = "root";
$password = "";
$database = "user";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error){
    die("Connection failed: " . $connect->connection_error);
}


?>