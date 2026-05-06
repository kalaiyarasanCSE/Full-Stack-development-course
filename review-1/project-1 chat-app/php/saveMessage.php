<?php
include "db.php";

$sender = $_POST['sender'];
$receiver = $_POST['receiver'];
$message = $_POST['message'];

$conn->query("INSERT INTO messages(sender,receiver,message) VALUES('$sender','$receiver','$message')");
echo "Saved";
?>