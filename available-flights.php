<?php
session_start();
require_once('includes/showMessage.php');
require 'includes/functions.php';
displaySessionMessage();

if (isset($_SESSION['user_type'])) {
    include("navOptions/customer-dashboard-nav-options.php");
} else {
    include("navOptions/index-nav-options.php");
}

include('connection.php');

if (isset($_POST['search_flight']) || (isset($_SESSION['source_date']) && isset($_SESSION['source_time']) && isset($_SESSION['dest_date']) && isset($_SESSION['dest_time']) && isset($_SESSION['dep_airport']) && isset($_SESSION['arr_airport']) && isset($_SESSION['flight_class']))) {

    if (isset($_POST['search_flight'])) {
        $_SESSION['source_date'] = $_POST['source_date'];
        $_SESSION['dest_date'] = $_POST['dest_date'];
        $_SESSION['dep_airport'] = $_POST['dep_airport'];
        $_SESSION['arr_airport'] = $_POST['arr_airport'];
        $_SESSION['flight_class'] = $_POST['flight_class'];
    }

    $source_date = $_SESSION['source_date'];
    $dest_date = $_SESSION['dest_date'];
    $dep_airport = $_SESSION['dep_airport'];
    $arr_airport = $_SESSION['arr_airport'];
    $flight_class = $_SESSION['flight_class'];

    $source_timestamp = strtotime("$source_date");
    $dest_timestamp = strtotime("$dest_date");

    $sql = "SELECT f.*, 
                   (f.seats - IFNULL(b.booked_seats, 0)) AS available_seats
            FROM flight f
            LEFT JOIN (
                SELECT flight_id, COUNT(*) AS booked_seats
                FROM booked
                GROUP BY flight_id
            ) b ON f.id = b.flight_id
            WHERE f.dep_airport = '$dep_airport'
            AND f.arr_airport = '$arr_airport'
            AND f.flight_class = '$flight_class'
            AND CONCAT(f.source_date, ' ', f.source_time) >= '$source_timestamp'
            AND CONCAT(f.dest_date, ' ', f.dest_time) >= '$dest_timestamp'";

    if (!empty($_POST['airline_name'])) {
        $airline_name = $_POST['airline_name'];
        $sql .= " AND f.airline_name = '$airline_name'";
    }
    $sql .= " ORDER BY f.source_date, f.source_time ASC";
    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Error: " . mysqli_error($con);
    } else {
        $search_results = $result;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Flights</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/general.css">
</head>
<body>

<header></header>
<nav>
    <a class="logo" href="index.php"> <img src="images/Easyfly.png" alt="site-logo"> </a>
    <?php include('navOptions/nav.php'); ?>
</nav>

<div class="container mt-5" style="margin-top: 150px;">
    <div class="right-column">
        <?php
        if (isset($search_results) && mysqli_num_rows($search_results) > 0) {
            echo "<h3>Available Flights:</h3>";
            echo "<table class='table'>";
            echo "<thead><tr><th>Departure</th><th>Arrival</th><th>Airline</th><th>Seats</th><th>Price</th><th>Action</th></tr></thead>";
            echo "<tbody>";

            while ($row = mysqli_fetch_assoc($search_results)) {
                echo "<tr>";
                echo "<form method='get' action='booking-details.php'>";
                echo "<td>{$row['dep_airport']}</td>";
                echo "<td>{$row['arr_airport']}</td>";
                echo "<td>{$row['airline_name']}</td>";
                echo "<td>{$row['available_seats']}</td>";
                echo "<td>{$row['price']}</td>";
                echo "<input type='hidden' name='flight_id' value='{$row['id']}'>";
                echo "<td><button type='submit' class='btn btn-primary'>Book</button></td>";
                echo "</form>";
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
            mysqli_free_result($search_results);
        } else {
            echo "<h3>No flights available</h3>";
        }
        ?>
    </div>
</div>

<footer>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="aboutUs.php">About Us</a></li>
        <li><a href="aboutUs.php#targeting-contact">Contact</a></li>
        <li><a href="booking-form.php">Services</a></li>
    </ul>
    <p>&copy; 2025 Skylines, all rights reserved</p>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
