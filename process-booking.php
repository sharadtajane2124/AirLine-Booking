<?php
session_start();
require('connection.php');
require_once('includes/functions.php');

if (!isset($_SESSION['email']) || !isset($_POST['flight_id'])) {
    setSessionMessage("Invalid access.", 'error');
    header("Location: available-flights.php");
    exit();
}

$flight_id = $_POST['flight_id'];
$customer_email = $_SESSION['email'];
$passenger_names = $_POST['passenger_name'];
$passenger_ages = $_POST['passenger_age'];
$seat_preferences = $_POST['passenger_seat_preference'];
$payment_method = $_POST['payment_method'];

// Get total seat capacity
$flight_res = mysqli_query($con, "SELECT seats FROM flight WHERE id = $flight_id");
$flight = mysqli_fetch_assoc($flight_res);
$total_seats = (int)$flight['seats'];

// Get already booked seats
$booked_seats_res = mysqli_query($con, "
    SELECT seat_number FROM passenger_details 
    JOIN booked ON booked.id = passenger_details.booked_id
    WHERE booked.flight_id = $flight_id
");

$booked_seats = [];
while ($row = mysqli_fetch_assoc($booked_seats_res)) {
    $booked_seats[] = $row['seat_number'];
}

// Generate available seat numbers
$available_seats = [];
$rows = ceil($total_seats / 6); // Assuming 6 seats per row (A-F)
$columns = ['A', 'B', 'C', 'D', 'E', 'F'];
foreach (range(1, $rows) as $r) {
    foreach ($columns as $col) {
        $seat = $r . $col;
        if (!in_array($seat, $booked_seats)) {
            $available_seats[] = $seat;
        }
    }
}

// Seat preference map
$pref_map = [
    'Window' => ['A', 'F'],
    'Aisle'  => ['C', 'D'],
    'Middle' => ['B', 'E'],
];

// Seat assignment function
function assignSeatsWithPreference($preferences, &$available_seats, $pref_map) {
    $assigned = [];

    foreach ($preferences as $pref) {
        $found = false;
        foreach ($available_seats as $key => $seat) {
            $lastChar = substr($seat, -1);
            if (in_array($lastChar, $pref_map[$pref])) {
                $assigned[] = $seat;
                unset($available_seats[$key]);
                $available_seats = array_values($available_seats);
                $found = true;
                break;
            }
        }

        if (!$found && count($available_seats) > 0) {
            $assigned[] = array_shift($available_seats);
        }
    }

    return $assigned;
}

$assigned_seats = assignSeatsWithPreference($seat_preferences, $available_seats, $pref_map);

// Insert booking
$insertBooking = "INSERT INTO booked (flight_id, customer_email) VALUES ('$flight_id', '$customer_email')";
if (mysqli_query($con, $insertBooking)) {
    $booking_id = mysqli_insert_id($con);

    // Insert passengers
    for ($i = 0; $i < count($passenger_names); $i++) {
        $name = mysqli_real_escape_string($con, $passenger_names[$i]);
        $age = (int)$passenger_ages[$i];
        $preference = mysqli_real_escape_string($con, $seat_preferences[$i]);
        $seat_number = mysqli_real_escape_string($con, $assigned_seats[$i]);

        $insertPassenger = "INSERT INTO passenger_details 
            (booked_id, name, age, seat_number, seat_preference)
            VALUES ('$booking_id', '$name', '$age', '$seat_number', '$preference')";
        mysqli_query($con, $insertPassenger);
    }

    // ✅ FIXED HERE: Corrected column name to `booked_id`
// Insert payment
$insertPayment = "INSERT INTO payments (booked_id, payment_method)
                  VALUES ('$booking_id', '$payment_method')";
mysqli_query($con, $insertPayment);


    // ✅ Redirect to modern ticket page with QR code and PDF option
    header("Location: booking-confirmation.php?booking_id=$booking_id");
    exit();
} else {
    echo "Error: " . mysqli_error($con);
}
?>
