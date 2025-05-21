<?php
session_start();
if (!isset($_SESSION['user_type'])) {
    header('location: login.php');
    exit;
}
require_once('includes/showMessage.php');
require 'includes/functions.php';
require 'connection.php';
displaySessionMessage();

if (isset($_POST['flight_but'])) {
    $source_date = $_POST['source_date'];
    $source_time = $_POST['source_time'];
    $dest_date = $_POST['dest_date'];
    $dest_time = $_POST['dest_time'];
    $dep_airport = $_POST['dep_airport'];
    $arr_airport = $_POST['arr_airport'];
    $seats = $_POST['seats'];
    $price = $_POST['price'];
    $flight_class = $_POST['flight_class'];
    $airline_name = $_POST['airline_name'];

    if ($dep_airport === $arr_airport) {
        header('Location: add-flight.php?error=same');
        exit;
    }

    $dep_datetime = strtotime("$source_date $source_time");
    $arr_datetime = strtotime("$dest_date $dest_time");

    if ($arr_datetime <= $dep_datetime) {
        header('Location: add-flight.php?error=destless');
        exit;
    }

    $dep_query = mysqli_query($con, "SELECT airport_id FROM airport WHERE airport_name='$dep_airport'");
    $arr_query = mysqli_query($con, "SELECT airport_id FROM airport WHERE airport_name='$arr_airport'");
    $airline_query = mysqli_query($con, "SELECT email FROM airline WHERE airline_name='$airline_name'");

    if (mysqli_num_rows($dep_query) && mysqli_num_rows($arr_query) && mysqli_num_rows($airline_query)) {
        $dep_row = mysqli_fetch_assoc($dep_query);
        $arr_row = mysqli_fetch_assoc($arr_query);
        $airline_row = mysqli_fetch_assoc($airline_query);

        $dep_airport_id = $dep_row['airport_id'];
        $arr_airport_id = $arr_row['airport_id'];
        $airline_email = $airline_row['email'];

        $sql = "INSERT INTO flight (
            source_date, source_time, dest_date, dest_time,
            dep_airport, arr_airport, seats, price, flight_class, airline_name,
            dep_airport_id, arr_airport_id, airline_email
        ) VALUES (
            '$source_date', '$source_time', '$dest_date', '$dest_time',
            '$dep_airport', '$arr_airport', $seats, $price, '$flight_class', '$airline_name',
            $dep_airport_id, $arr_airport_id, '$airline_email'
        )";

        if (mysqli_query($con, $sql)) {
            $_SESSION['msg'] = "Flight successfully added.";
            header('Location: add-flight.php');
            exit;
        } else {
            header('Location: add-flight.php?error=sqlerr');
            exit;
        }
    } else {
        header('Location: add-flight.php?error=sqlerr');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Flight</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
        }

        .form-container {
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h3 {
            font-weight: 600;
            color: #333;
        }

        select, input[type="date"], input[type="time"], input[type="number"] {
            border-radius: 8px !important;
        }

        .form-label {
            font-weight: 500;
            color: #444;
        }

        .btn-modern {
            font-size: 18px;
            padding: 10px 30px;
            background: #0069d9;
            color: #fff;
            border: none;
            border-radius: 8px;
        }

        .btn-modern:hover {
            background: #0053b3;
        }

        .alert-custom {
            font-size: 16px;
        }
    </style>
</head>

<body>
<?php include('includes/admin-nav.php'); ?>

<div class="container mt-4">
    <?php
    if (isset($_GET['error'])) {
        $msg = "";
        if ($_GET['error'] === 'destless') $msg = "Arrival time must be after departure time.";
        if ($_GET['error'] === 'same') $msg = "Departure and Arrival airports cannot be the same.";
        if ($_GET['error'] === 'sqlerr') $msg = "Something went wrong. Try again.";
        echo "<div class='alert alert-danger alert-custom text-center'>$msg</div>";
    }
    ?>
    <div class="form-container mx-auto col-md-10">
        <h3 class="text-center mb-4">Add Flight Details</h3>
        <form method="POST" action="add-flight.php">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Departure Date</label>
                    <input type="date" name="source_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Departure Time</label>
                    <input type="time" name="source_time" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Arrival Date</label>
                    <input type="date" name="dest_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Arrival Time</label>
                    <input type="time" name="dest_time" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Departure Airport</label>
                    <select name="dep_airport" class="form-select" required>
                        <option value="" disabled selected>Select Departure</option>
                        <?php
                        $result = mysqli_query($con, "SELECT airport_name FROM airport");
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['airport_name'] . '">' . $row['airport_name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Arrival Airport</label>
                    <select name="arr_airport" class="form-select" required>
                        <option value="" disabled selected>Select Arrival</option>
                        <?php
                        $result = mysqli_query($con, "SELECT airport_name FROM airport");
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['airport_name'] . '">' . $row['airport_name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Seats</label>
                    <input type="number" name="seats" class="form-control" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" name="price" class="form-control" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Flight Class</label>
                    <select name="flight_class" class="form-select" required>
                        <option value="" disabled selected>Select Class</option>
                        <option value="Economy">Economy</option>
                        <option value="Business">Business</option>
                        <option value="First Class">First Class</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Airline</label>
                    <select name="airline_name" class="form-select" required>
                        <option value="" disabled selected>Select Airline</option>
                        <?php
                        $result = mysqli_query($con, "SELECT airline_name FROM airline");
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['airline_name'] . '">' . $row['airline_name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" name="flight_but" class="btn btn-modern">
                    <i class="fa fa-paper-plane me-2"></i>Submit Flight
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
