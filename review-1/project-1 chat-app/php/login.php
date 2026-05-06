<?php
include "db.php";

$email = $_POST['email'];
$password = $_POST['password'];

$res = $conn->query("SELECT * FROM users WHERE email='$email'");
$row = $res->fetch_assoc();

if ($row && password_verify($password, $row['password'])) {
    echo $row['username'];
} else {
    echo "Invalid";
}
?>