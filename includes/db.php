<?php
$servername = "SERVERNAME"; 
$username   = "USERNAME";                     
$password   = "PASSWORD";       
$dbname     = "DATABASE";                  
$port       = "PORT";                     

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
