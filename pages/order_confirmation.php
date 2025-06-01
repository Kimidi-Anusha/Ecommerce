<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Optionally get order ID from session if redirected from checkout
$order_id = $_SESSION['order_id'] ?? 'N/A';
unset($_SESSION['order_id']); // Clear the session variable after displaying

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }

        .confirmation-container {
            width: 90%;
            max-width: 600px;
            background-color: #fff;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }

        h2 {
            color: #28a745;
            /* Green color for success */
            font-size: 2.5em;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .order-id {
            font-weight: bold;
            color: #007bff;
            font-size: 1.2em;
        }

        .button-link {
            display: inline-block;
            margin-top: 30px;
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }

        .button-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="confirmation-container">
        <h2>Order Placed Successfully!</h2>
        <p>Thank you for your purchase.</p>
        <p>Your Order ID is: <span class="order-id"><?= htmlspecialchars($order_id); ?></span></p>
        <p>You will receive an email confirmation shortly.</p>
        <a href="../index.php" class="button-link">Continue Shopping</a>
    </div>
</body>

</html>