<?php
$conn = mysqli_connect("localhost", "root", "", "booking"); // <- Replace "booking" if your DB name is different

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
