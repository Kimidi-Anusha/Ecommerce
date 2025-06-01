<?php
session_start();
include 'includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $message = "Only JPG, PNG, and GIF files are allowed.";
        } else {
            // Save the uploaded image to /images/ folder
            $uploadDir = __DIR__ . '/images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $imageName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // Insert product into database
                $stmt = $conn->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $price, $description, $imageName]);
                $message = "Product added successfully!";
            } else {
                $message = "Failed to upload image.";
            }
        }
    } else {
        $message = "Please upload an image.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Add Product</title>
</head>

<body>
    <h1>Add New Product</h1>
    <?php if ($message): ?>
        <p><strong><?= htmlspecialchars($message) ?></strong></p>
    <?php endif; ?>
    <form action="add_products.php" method="POST" enctype="multipart/form-data">

        <label>
            Product Name:<br />
            <input type="text" name="name" required />
        </label><br /><br />

        <label>
            Price ($):<br />
            <input type="number" step="0.01" name="price" required />
        </label><br /><br />

        <label>
            Description:<br />
            <textarea name="description" rows="4" cols="40" required></textarea>
        </label><br /><br />

        <label>
            Product Image:<br />
            <input type="file" name="image" accept="image/*" required />
        </label><br /><br />

        <button type="submit">Add Product</button>
    </form>
    <p><a href="index.php">Back to Store</a></p>
</body>

</html>