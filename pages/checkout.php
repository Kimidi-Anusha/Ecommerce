<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php'; // Your database connection

$user_id = $_SESSION['user_id'];
$checkout_message = ""; // To display messages to the user

// --- Handle Order Placement ---
if (isset($_POST['place_order'])) {
    // Removed: $shipping_address = trim($_POST['shipping_address']);
    // Removed: if (empty($shipping_address)) { ... } else { ... }

    try {
        // 1. Fetch current cart items to calculate total and get product details
        $stmt_cart = $conn->prepare("SELECT c.product_id, c.quantity, p.price, p.name 
                                      FROM cart c JOIN products p ON c.product_id = p.id 
                                      WHERE c.user_id = ?");
        $stmt_cart->execute([$user_id]);
        $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            $checkout_message = "<p style='color:red; text-align:center;'>Your cart is empty. Cannot place an order.</p>";
        } else {
            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }

            // 2. Insert into 'orders' table
            $conn->beginTransaction(); // Start a transaction for atomicity

            // Changed: Removed shipping_address from the INSERT statement
            $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'Pending')");
            $stmt_order->execute([$user_id, $total_amount]); // Removed $shipping_address
            $order_id = $conn->lastInsertId(); // Get the ID of the newly created order

            // 3. Insert into 'order_items' table for each product in the cart
            $stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $stmt_order_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }

            // 4. Clear the user's cart
            $stmt_clear_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt_clear_cart->execute([$user_id]);

            $conn->commit(); // Commit the transaction if all operations are successful

            // Redirect to a confirmation page
            $_SESSION['order_id'] = $order_id; // Store order ID for confirmation page
            header("Location: order_confirmation.php");
            exit();
        }
    } catch (PDOException $e) {
        $conn->rollBack(); // Rollback on error
        $checkout_message = "<p style='color:red; text-align:center;'>Error placing order: " . $e->getMessage() . "</p>";
        // In a production environment, log the error and provide a generic message to the user.
    }
}


// --- Fetch Cart Items for Display ---
$stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.image 
                        FROM cart c JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items_display = $stmt->fetchAll(PDO::FETCH_ASSOC);

$display_total_cost = 0;
foreach ($cart_items_display as $item) {
    $display_total_cost += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            width: 90%;
            max-width: 800px;
            margin: 40px auto;
            background-color: #fff;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h2 {
            text-align: center;
            font-size: 2.2em;
            margin-bottom: 30px;
            color: #343a40;
        }

        .cart-summary {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background-color: #fefefe;
        }

        .cart-summary h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #495057;
        }

        .cart-summary ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .cart-summary li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }

        .cart-summary li:last-child {
            border-bottom: none;
        }

        .cart-summary .total {
            font-weight: bold;
            font-size: 1.3em;
            margin-top: 15px;
            text-align: right;
            color: #343a40;
        }

        /* Removed styles for shipping form elements */
        /* .shipping-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .shipping-form textarea {
            width: calc(100% - 22px); 
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            min-height: 100px;
            resize: vertical;
        } */

        .checkout-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .checkout-buttons button,
        .checkout-buttons a {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: center;
        }

        .checkout-buttons button:hover,
        .checkout-buttons a:hover {
            background-color: #218838;
        }

        .checkout-buttons a:first-child {
            /* Style for 'Back to Cart' */
            background-color: #6c757d;
        }

        .checkout-buttons a:first-child:hover {
            background-color: #5a6268;
        }

        .empty-cart-message {
            text-align: center;
            font-size: 1.2em;
            color: #dc3545;
            padding: 20px;
            border: 1px solid #dc3545;
            background-color: #f8d7da;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Checkout</h2>

        <?php echo $checkout_message; // Display any messages (e.g., empty cart, error) ?>

        <?php if (empty($cart_items_display)): ?>
            <p class="empty-cart-message">Your cart is empty. Please add items before checking out.</p>
            <div class="checkout-buttons">
                <a href="cart.php">Back to Cart</a>
                <a href="../index.php">Back to Shop</a>
            </div>
        <?php else: ?>
            <div class="cart-summary">
                <h3>Order Summary</h3>
                <ul>
                    <?php foreach ($cart_items_display as $item): ?>
                        <li>
                            <span><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                            <span>$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="total">
                    Total: $<?= number_format($display_total_cost, 2); ?>
                </div>
            </div>

            <form method="POST" class="shipping-form">
                <div class="checkout-buttons">
                    <a href="cart.php">Back to Cart</a>
                    <button type="submit" name="place_order">Place Order</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>