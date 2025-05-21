<?php
session_start();
require('connection.php');
require_once('includes/functions.php');
require('vendor/autoload.php'); // Dompdf

use Dompdf\Dompdf;

if (!isset($_GET['booking_id'])) {
    setSessionMessage("Invalid booking.", 'error');
    header("Location: available-flights.php");
    exit();
}

$booking_id = (int) $_GET['booking_id'];

// Fetch booking and passenger details
$booking_query = mysqli_query($con, "
    SELECT b.id AS booking_id, f.airline_name, f.flight_class, f.dep_airport, f.arr_airport, f.source_date, f.source_time,
           pd.name, pd.age, pd.seat_number, pd.seat_preference, pd.gender
    FROM booked b
    JOIN flight f ON f.id = b.flight_id
    JOIN passenger_details pd ON pd.booked_id = b.id
    WHERE b.id = $booking_id
");

if (mysqli_num_rows($booking_query) == 0) {
    echo "No booking found.";
    exit();
}

$passengers = [];
$row = mysqli_fetch_assoc($booking_query);
$flight_info = $row;
do {
    $passengers[] = [
        'name' => $row['name'],
        'age' => $row['age'],
        'gender' => $row['gender'],
        'seat' => $row['seat_number'],
        'preference' => $row['seat_preference']
    ];
} while ($row = mysqli_fetch_assoc($booking_query));

// Handle PDF download
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    ob_start(); ?>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; }
            .ticket {
                width: 100%;
                border: 2px dashed #007bff;
                padding: 20px;
                border-radius: 16px;
                background: #f9f9f9;
            }
            .header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
            }
            .title { font-size: 20px; font-weight: bold; }
            .route { font-size: 18px; margin-bottom: 5px; }
            .flight-info { margin-bottom: 15px; }
            .passenger {
                border-top: 1px solid #ccc;
                padding-top: 10px;
                margin-top: 10px;
            }
            .footer {
                margin-top: 20px;
                text-align: center;
                font-style: italic;
                color: #007bff;
            }
            .barcode {
                margin-top: 20px;
                text-align: center;
            }
            .barcode img {
                width: 180px;
                height: auto;
            }
        </style>
    </head>
    <body>
    <div class="ticket">
        <div class="header">
            <div class="title"><?= $flight_info['airline_name']; ?> Boarding Pass</div>
            <div><strong>Class:</strong> <?= $flight_info['flight_class']; ?></div>
        </div>
        <div class="flight-info">
            <div class="route"><?= $flight_info['dep_airport']; ?> ✈️ <?= $flight_info['arr_airport']; ?></div>
            <div><strong>Departure:</strong> <?= $flight_info['source_date']; ?> at <?= $flight_info['source_time']; ?></div>
            <div><strong>Booking ID:</strong> <?= $booking_id; ?></div>
        </div>

        <?php foreach ($passengers as $p): ?>
            <div class="passenger">
                <strong><?= htmlspecialchars($p['name']) ?></strong> |
                Age: <?= $p['age'] ?> |
                Gender: <?= $p['gender'] ?> |
                Seat: <?= $p['seat'] ?> (<?= $p['preference'] ?>)
            </div>
        <?php endforeach; ?>

        <div class="barcode">
            <img src="file://<?= realpath('images/barcode.png') ?>" alt="Barcode">
        </div>

        <div class="footer">Have a safe journey!</div>
    </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("ticket_$booking_id.pdf", ["Attachment" => true]);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Flight Ticket Confirmation</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f2f6fc;
            padding: 30px;
        }
        .ticket {
            max-width: 900px;
            margin: auto;
            background: white;
            border: 2px dashed #007bff;
            border-radius: 18px;
            padding: 30px 40px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
            color: #007bff;
        }
        .flight-info, .passenger-info {
            margin-bottom: 20px;
        }
        .flight-info div, .passenger {
            margin-bottom: 10px;
            font-size: 16px;
        }
        .passenger strong {
            color: #000;
        }
        .btns {
            text-align: center;
            margin-top: 25px;
        }
        .btns a {
            display: inline-block;
            background: #007bff;
            color: white;
            text-decoration: none;
            padding: 10px 25px;
            margin: 5px;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s ease;
        }
        .btns a:hover {
            background: #0056b3;
        }
        .barcode {
            text-align: center;
            margin-top: 30px;
        }
        .barcode img {
            width: 180px;
            height: auto;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-style: italic;
            color: #007bff;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="ticket">
    <div class="header">
        <h2><?= $flight_info['airline_name']; ?> Boarding Pass</h2>
        <div><strong>Class:</strong> <?= $flight_info['flight_class']; ?></div>
    </div>

    <div class="flight-info">
        <div><strong>From:</strong> <?= $flight_info['dep_airport']; ?> ✈️ <strong>To:</strong> <?= $flight_info['arr_airport']; ?></div>
        <div><strong>Departure:</strong> <?= $flight_info['source_date']; ?> at <?= $flight_info['source_time']; ?></div>
        <div><strong>Booking ID:</strong> <?= $booking_id; ?></div>
    </div>

    <div class="passenger-info">
        <h3>Passenger Details</h3>
        <?php foreach ($passengers as $p): ?>
            <div class="passenger">
                <strong><?= htmlspecialchars($p['name']) ?></strong> |
                Age: <?= $p['age'] ?> |
                Gender: <?= $p['gender'] ?> |
                Seat: <?= $p['seat'] ?> (<?= $p['preference'] ?>)
            </div>
        <?php endforeach; ?>
    </div>

    <div class="barcode">
        <img src="images/barcode.png" alt="Barcode">
    </div>

    <div class="footer">Have a safe journey!</div>

    <div class="btns">
        <a href="?booking_id=<?= $booking_id ?>&download=pdf">Download Ticket (PDF)</a>
        <a href="customer-dashboard.php">Back to Home</a>
    </div>
</div>

</body>
</html>
