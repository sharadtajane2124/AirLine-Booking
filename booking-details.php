<?php
include('db.php');
session_start();

if (!isset($_GET['flight_id'])) {
    die("Flight not selected.");
}

$flight_id = $_GET['flight_id'];
$flight_query = mysqli_query($conn, "
    SELECT f.*, 
           d.airport_name AS from_airport, 
           ar.airport_name AS to_airport, 
           a.airline_name, 
           a.logo AS airline_logo 
    FROM flight f 
    LEFT JOIN airport d ON f.dep_airport_id = d.airport_id 
    LEFT JOIN airport ar ON f.arr_airport_id = ar.airport_id 
    LEFT JOIN airline a ON f.airline_email = a.email 
    WHERE f.id = $flight_id
");

if (!$flight_query || mysqli_num_rows($flight_query) == 0) {
    die("Flight not found.");
}

$flight = mysqli_fetch_assoc($flight_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Flight Booking</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .flight-info p {
            margin: 10px 0;
            font-size: 16px;
        }
        .airline-logo img {
            height: 80px;
            display: block;
            margin: 10px auto;
        }
        form {
            margin-top: 20px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }
        label {
            font-weight: 500;
        }
        .passenger-form {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-left: 4px solid #0c7b93;
            border-radius: 10px;
        }
        .passenger-form h4 {
            margin: 0 0 10px 0;
            color: #0c7b93;
        }
        .hidden {
            display: none;
        }
        .btn {
            background: #0c7b93;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s ease;
        }
        .btn:hover {
            background: #095e71;
        }
        #payment_section {
            margin-top: 20px;
            animation: fadeIn 0.5s ease-in-out;
        }
        #upi_fields img {
            border-radius: 12px;
            margin-top: 10px;
        }
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
    </style>
    <script>
    function renderPassengerForms() {
        const count = document.getElementById('seat_count').value;
        const price = <?= $flight['price']; ?>;
        const container = document.getElementById('passenger_forms');
        const upiAmount = document.getElementById('upi_amount');

        container.innerHTML = '';
        for (let i = 0; i < count; i++) {
            container.innerHTML += `
                <div class="passenger-form">
                    <h4>Passenger ${i + 1}</h4>
                    <input name="passenger_name[]" placeholder="Name" required>
                    <input name="passenger_age[]" type="number" placeholder="Age" required>
                    <select name="passenger_gender[]" required>
                        <option value="">Select Gender</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                    <label>Seat Preference</label>
                    <select name="passenger_seat_preference[]" required>
                        <option value="">Seat Preference</option>
                        <option value="Window">Window</option>
                        <option value="Aisle">Aisle</option>
                        <option value="Middle">Middle</option>
                    </select>

                </div>
            `;
        }

        upiAmount.innerText = price * count;
        document.getElementById('payment_section').style.display = 'block';
    }

    function togglePaymentFields() {
        const method = document.getElementById('payment_method').value;
        document.getElementById('card_fields').style.display = method === 'Card' ? 'block' : 'none';
        document.getElementById('upi_fields').style.display = method === 'UPI' ? 'block' : 'none';
    }
    </script>
</head>
<body>
    <div class="container">
        <h2>Flight Booking</h2>

        <?php if (!empty($flight['airline_logo'])): ?>
            <div class="airline-logo">
                <img src="<?= $flight['airline_logo']; ?>" alt="Airline Logo">
            </div>
        <?php endif; ?>

        <div class="flight-info">
            <p><strong>Airline:</strong> <?= $flight['airline_name']; ?></p>
            <p><strong>From:</strong> <?= $flight['from_airport']; ?> → <strong>To:</strong> <?= $flight['to_airport']; ?></p>
            <p><strong>Departure:</strong> <?= $flight['source_date']; ?> <?= $flight['source_time']; ?></p>
            <p><strong>Arrival:</strong> <?= $flight['dest_date']; ?> <?= $flight['dest_time']; ?></p>
            <p><strong>Class:</strong> <?= $flight['flight_class']; ?></p>
            <p><strong>Available Seats:</strong> <?= $flight['seats']; ?></p>
            <p><strong>Price per seat:</strong> ₹<?= number_format($flight['price'], 2); ?></p>
        </div>

        <form method="POST" action="process-booking.php">
            <input type="hidden" name="flight_id" value="<?= $flight['id']; ?>">
            <input type="hidden" name="price" value="<?= $flight['price']; ?>">

            <label for="seat_count">Number of Seats</label>
            <input type="number" name="seat_count" id="seat_count" min="1" max="<?= $flight['seats']; ?>" onchange="renderPassengerForms()" required>

            <div id="passenger_forms"></div>

            <div id="payment_section" class="hidden">
                <h3>Payment Info</h3>
                <label for="payment_method">Payment Method</label>
                <select name="payment_method" id="payment_method" onchange="togglePaymentFields()" required>
                    <option value="">-- Select Payment Method --</option>
                    <option value="UPI">UPI</option>
                    <option value="Card">Debit / Credit Card</option>
                </select>

                <div id="card_fields" class="hidden">
                    <input name="card_number" placeholder="Card Number" maxlength="16">
                    <input name="card_name" placeholder="Cardholder Name">
                    <input name="card_expiry" placeholder="MM/YY">
                    <input name="card_cvv" placeholder="CVV" maxlength="3">
                </div>

                <div id="upi_fields" class="hidden">
                    <p><strong>Scan the QR Code to Pay:</strong></p>
                    <img src="images/image.png" alt="UPI QR Code" width="200"><br>
                    <p>Amount: ₹<span id="upi_amount"><?= $flight['price']; ?></span></p>
                </div>

                <button type="submit" class="btn">Proceed & Generate Ticket</button>
            </div>
        </form>
    </div>
</body>
</html>
