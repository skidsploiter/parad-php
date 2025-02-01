<?php
// Start the session
session_start();

// Initialize SQLite database
$db = new SQLite3('db.db');

// Create the table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    pin INTEGER NOT NULL,
    license TEXT NOT NULL,
    balance FLOAT DEFAULT 0,
    session_id TEXT,
    last_generated INTEGER DEFAULT 0
)");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        // Login logic
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);
        $pin = intval($_POST['pin']);
        $license = htmlspecialchars($_POST['license']);

        // Query user from the database
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        // Check if password, pin, and license match
        if ($user && password_verify($password, $user['password']) && $user['pin'] == $pin && $user['license'] == $license) {
            // Check if the user is already logged in from another session
            if ($user['session_id'] && $user['session_id'] !== session_id()) {
                $error = "This account is already logged in from another session.";
            } else {
                // Set session ID and update the last generated time
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_logged_in'] = true;

                // Update session ID in the database
                $stmt = $db->prepare("UPDATE users SET session_id = :session_id, last_generated = :last_generated WHERE id = :id");
                $stmt->bindValue(':session_id', session_id(), SQLITE3_TEXT);
                $stmt->bindValue(':last_generated', time(), SQLITE3_INTEGER);
                $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                $stmt->execute();

                header('Location: dashboard.php'); // Redirect to a dashboard or secure page
                exit;
            }
        } else {
            $error = "Invalid credentials, please try again.";
        }
    }
}

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    if ($error == 'session_expired'){
        $error = 'Oops! Your account is already logged in from another device. Please logout from there and retry!';
    }
}

// Check if already logged in
if (isset($_SESSION['username'])) {
    header('Location: dashboard.php'); // Redirect if logged in
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paradise Client Panel / Login</title>
    <link rel="stylesheet" href="s.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&family=Poppins:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Poppins, sans-serif;
            height: 125vh;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="head">
            <h4><img draggable="false" src="paradise.png" alt="Paradise" style="width: 30px; height: 30px; margin-right"> Welcome to... <p class="gradient">Paradise.</p></h4>
        </div>

        <div class="under_line"></div>

        <div class="main_box">
            <!-- Login Form -->
            <form method="POST" action="index.php">
                <div class="username">
                    <h3>Username</h3>
                    <div class="username_box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input name="username" autocomplete="off" id="username" type="text" placeholder="eg., catlover12" required>
                    </div>
                </div>

                <div class="password">
                    <h3>Password</h3>
                    <div class="pass_box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                        <input name="password" id="pass" type="password" placeholder="eg., ilovekitties1234" required>
                    </div>
                </div>

                <div class="pin">
                    <h3>Pin</h3>
                    <div class="pin_box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pin"><path d="M12 17v5"/><path d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"/></svg>
                        <input name="pin" id="pin" type="number" placeholder="eg., 1337" max='9999' required>
                    </div>
                </div>

                <div class="license">
                    <h3>License</h3>
                    <div class="license_box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-id-card"><path d="M16 10h2"/><path d="M16 14h2"/><path d="M6.17 15a3 3 0 0 1 5.66 0"/><circle cx="9" cy="11" r="2"/><rect x="2" y="5" width="20" height="14" rx="2"/></svg>
                        <input name="license" autocomplete="off" id="license" type="password" placeholder="ae3052b5ded8772d14cc460d149c4bc55eb3b8766fae91924d9db1c40b83a055" required>
                    </div>
                </div>

                <button type="submit" name="login" class="Button"><p style="font-family: 'Unbounded', serif; color: orange;">Validate</p></button>
                
                <?php if (isset($error)): ?>
                    <p style="color: red;"><?= $error; ?></p>
                <?php endif; ?>
            </form>
        </div>

        <div class="already_x">
            <h1>Contact admins for registering.</h1>
        </div>
    </div>

</body>
</html>
