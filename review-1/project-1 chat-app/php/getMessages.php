<?php
include "db.php";

$sender = $_GET['sender'];
$receiver = $_GET['receiver'];

$res = $conn->query("SELECT * FROM messages 
WHERE (sender='$sender' AND receiver='$receiver') 
OR (sender='$receiver' AND receiver='$sender')");

while($row = $res->fetch_assoc()) {

    $class = ($row['sender'] == $sender) ? "sent" : "received";

    echo "<div class='message $class'>
            <b>{$row['sender']}:</b> {$row['message']}
          </div>";
}
?>