<?php
session_start();
if (!isset($_SESSION['user_type'])) {
    header('location: login.php');
    exit();
}

require_once('includes/showMessage.php');
require 'includes/functions.php';
displaySessionMessage();
include("navOptions/customer-dashboard-nav-options.php");

// Get user email from session
$user_email = $_SESSION['email'] ?? '';

// Fetch customer name
require_once 'connection.php';
$customer_name = "Guest";
if (!empty($user_email)) {
    $sql = "SELECT first_name, last_name FROM customer WHERE email = '$user_email'";
    $result = $con->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $customer_name = $row['first_name'] . ' ' . $row['last_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
</head>

<body>
<nav>
    <a class="logo" href="index.php"> <img src="images/Easyfly.png" alt="site-logo"> </a>
    <?php include('navOptions/nav.php'); ?>
</nav>

<div class="user-info">
    <p style="text-align: right; font-size: 24px;">
        <span style="color: #999;"><em>user:</em></span>
        <span style="font-size: 20pt; color: #333;"><?php echo htmlspecialchars($customer_name); ?></span>
    </p>
</div>

<div class="container mt-5">
    <h2>Your Booked Flights</h2>
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th>Airline</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Departure Date</th>
                <th>Arrival Date</th>
                <th>Class</th>
                <th>Tickets</th>
                <th>Price per Ticket</th>
                <th>Total Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Delete Booking Operation
            if (isset($_POST["confirm_delete_booking"])) {
                $deleteBookingId = $_POST["delete_booking_id"];
                $deleteSql = "DELETE FROM booked WHERE id = '$deleteBookingId'";
                if ($con->query($deleteSql) === TRUE) {
                    setSessionMessage("Booking deleted successfully");
                    header('location: customer-dashboard.php');
                    exit();
                } else {
                    echo "<script>showModal('errorModal', 'Error deleting booking: " . $con->error . "');</script>";
                }
            }

            // Fetch bookings with correct price and passenger count
            $sqlBookings = "
                SELECT 
                    b.id, b.customer_email, 
                    f.airline_name, 
                    a1.airport_name as dep_airport, 
                    a2.airport_name as arr_airport, 
                    f.source_date, f.source_time, f.dest_date, f.dest_time, 
                    f.flight_class,
                    COUNT(pd.id) AS total_tickets,
                    f.price AS price_per_passenger,
                    f.price * COUNT(pd.id) AS total_price
                FROM booked b
                INNER JOIN flight f ON b.flight_id = f.id
                INNER JOIN customer c ON b.customer_email = c.email
                INNER JOIN airport a1 ON f.dep_airport_id = a1.airport_id
                INNER JOIN airport a2 ON f.arr_airport_id = a2.airport_id
                LEFT JOIN passenger_details pd ON b.id = pd.booked_id
                WHERE c.email = '$user_email'
                GROUP BY b.id
                ORDER BY f.source_date DESC
            ";

            $resultBookings = $con->query($sqlBookings);

            if ($resultBookings && $resultBookings->num_rows > 0) {
                while ($row = $resultBookings->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["airline_name"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["dep_airport"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["arr_airport"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["source_date"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["dest_date"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["flight_class"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["total_tickets"]) . "</td>";
                    echo "<td>₹" . number_format($row["price_per_passenger"], 2) . "</td>";
                    echo "<td><strong>₹" . number_format($row["total_price"], 2) . "</strong></td>";
                    echo "<td><button class='btn btn-danger btn-sm delete-booking' data-id='" . $row["id"] . "' data-toggle='modal' data-target='#deleteBookingModal'>Delete</button></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='10' class='text-center'><h4>No bookings found.<br><a href='booking-form.php'>Book Now</a></h4></td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Delete Modal -->
    <form method="POST">
        <input type="hidden" name="delete_booking_id" id="delete_booking_id">
        <div class="modal fade" id="deleteBookingModal" tabindex="-1" role="dialog" aria-labelledby="deleteBookingModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">Are you sure you want to delete this booking?</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="confirm_delete_booking">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).on("click", ".delete-booking", function () {
        var deleteBookingId = $(this).data('id');
        $('#delete_booking_id').val(deleteBookingId);
    });
</script>

<footer>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="aboutUs.php">About Us</a></li>
        <li><a href="aboutUs.php#targeting-contact">Contact</a></li>
        <li><a href="booking-form.php">Services</a></li>
    </ul>
    <p>&copy; 2025 Skylines, All rights reserved</p>
</footer>

</body>
</html>
