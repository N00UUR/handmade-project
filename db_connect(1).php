<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hand_made';
$conn = mysqli_connect($host,$username,$password,$database);

if(!$conn){
echo "Error : ". mysqli_connect_error();
}