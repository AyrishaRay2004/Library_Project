<?php
// Enable errors (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$member = $_GET['member'] ?? '';
$email  = $_GET['email'] ?? '';
$book   = $_GET['book'] ?? '';
$days   = $_GET['days'] ?? 0;
$fine   = $_GET['fine'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fine Receipt</title>
    <style>
        body { font-family: Arial; padding: 30px; }
        .receipt {
            max-width: 500px;
            margin: auto;
            border: 1px solid #000;
            padding: 20px;
        }
        h2 { text-align: center; }
        .line { margin: 8px 0; }
        .total { font-weight: bold; }
        button { margin-top: 20px; }
    </style>
</head>
<body>

<div class="receipt">
    <h2>Library Fine Receipt</h2>
    <hr>

    <div class="line"><strong>Member Name:</strong> <?= htmlspecialchars($member) ?></div>
    <div class="line"><strong>Book Title:</strong> <?= htmlspecialchars($book) ?></div>
    <div class="line"><strong>Email:</strong> <?= htmlspecialchars($email) ?></div>
    <div class="line"><strong>Overdue Days:</strong> <?= $days ?></div>
    <div class="line total"><strong>Total Fine:</strong> ₹ <?= $fine ?></div>

    <button onclick="window.print()">🖨 Print Receipt</button>
</div>

</body>
</html>

