<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: sans-serif;
        }
        .ticket {
            border: 1px solid #ccc;
            padding: 20px;
            max-width: 700px;
            margin: auto;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .flight-info, .passenger-info {
            margin-bottom: 20px;
        }
        .passenger {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
<div class="ticket">
    <h2>Flight Ticket</h2>
    <div class="flight-info">
        <p><strong>Flight:</strong> <?= $flight_info['flight_number'] ?></p>
        <p><strong>From:</strong> <?= $flight_info['source'] ?> <strong>To:</strong> <?= $flight_info['destination'] ?></p>
        <p><strong>Date:</strong> <?= $flight_info['date'] ?> <strong>Time:</strong> <?= $flight_info['time'] ?></p>
    </div>
    <div class="passenger-info">
        <h3>Passenger Details</h3>
        <?php foreach ($passengers as $p): ?>
            <div class="passenger">
                <?= $p['name'] ?> | Age: <?= $p['age'] ?> | Seat: <?= $p['seat'] ?> (<?= $p['preference'] ?>)
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
