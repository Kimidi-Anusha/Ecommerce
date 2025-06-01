<?php
session_start(); // Start the session, though not strictly needed for basic registration here, good practice.

// Include your database connection file.
// Path: Go up one directory from 'admin/' to 'ecommerce/', then into 'includes/'.
include '../includes/db.php'; // Assumes db.php establishes a PDO connection in $conn

$registration_message = ""; // Variable to store messages for the user

// Check if the registration form has been submitted
if (isset($_POST['register_admin'])) {
    // Get form data
    $email = trim($_POST['email']);
    $plain_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Input Validation ---
    if (empty($email) || empty($plain_password) || empty($confirm_password)) {
        $registration_message = "<p style='color:red; text-align:center;'>All fields are required.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_message = "<p style='color:red; text-align:center;'>Please enter a valid email address.</p>";
    } elseif ($plain_password !== $confirm_password) {
        $registration_message = "<p style='color:red; text-align:center;'>Passwords do not match.</p>";
    } elseif (strlen($plain_password) < 6) { // Example: minimum password length
        $registration_message = "<p style='color:red; text-align:center;'>Password must be at least 6 characters long.</p>";
    } else {
        try {
            // Check if the email already exists in the database
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            if ($stmt_check === false) {
                $registration_message = "<p style='color:red; text-align:center;'>Database error checking email: " . implode(" ", $conn->errorInfo()) . "</p>";
            } else {
                $stmt_check->execute([$email]);
                if ($stmt_check->rowCount() > 0) {
                    $registration_message = "<p style='color:red; text-align:center;'>An account with this email already exists.</p>";
                } else {
                    // --- Hash the password securely ---
                    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

                    // Insert the new admin user into the 'users' table
                    // The 'role' is explicitly set to 'admin' here.
                    $stmt_insert = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'admin')");
                    if ($stmt_insert === false) {
                        $registration_message = "<p style='color:red; text-align:center;'>Database error inserting user: " . implode(" ", $conn->errorInfo()) . "</p>";
                    } else {
                        if ($stmt_insert->execute([$email, $hashed_password])) {
                            $registration_message = "<p style='color:green; text-align:center;'>Admin registered successfully! You can now <a href='login.php'>login</a>.</p>";
                            // Optionally, redirect directly to the login page after success
                            // header("Location: login.php");
                            // exit();
                        } else {
                            $registration_message = "<p style='color:red; text-align:center;'>Registration failed. Please try again.</p>";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            // Catch any database connection or query errors
            $registration_message = "<p style='color:red; text-align:center;'>Database connection error: " . $e->getMessage() . "</p>";
            // In a production environment, log $e->getMessage() instead of displaying it.
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .register-container {
            width: 100%;
            max-width: 450px;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        input[type="email"],
        input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 0 0 15px;
            /* Adjust margin-bottom */
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            /* A blue color for registration */
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
        }

        .message {
            margin-top: 15px;
            font-weight: bold;
        }

        p.login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
        }

        p.login-link a {
            color: #007bff;
            text-decoration: none;
        }

        p.login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="register-container">
        <h2>Admin Registration</h2>
        <?php echo $registration_message; // Display registration messages ?>
        <form method="POST">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required autocomplete="username">

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required autocomplete="new-password">

            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password">

            <button type="submit" name="register_admin">Register Admin</button>
        </form>
        <p class="login-link">Already have an admin account? <a href="login.php">Login here</a></p>
    </div>

</body>

</html>