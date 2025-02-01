<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redirect to login if not authenticated
    exit();
}

$db = new SQLite3('db.db');

// Ensure the users table exists
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    balance FLOAT DEFAULT 0,
    session_id TEXT,
    last_generated INTEGER DEFAULT 0
)");

// Get username from session
$user = htmlspecialchars($_SESSION['username']);
$costPerAccount = 0.003;
$cooldownTime = 10; // 10-second cooldown

// Validate session (One session per account)
$sessionCheck = $db->prepare("SELECT session_id FROM users WHERE username = :username");
$sessionCheck->bindValue(':username', $user, SQLITE3_TEXT);
$sessionResult = $sessionCheck->execute();
$sessionRow = $sessionResult->fetchArray(SQLITE3_ASSOC);

if ($sessionRow && $sessionRow['session_id'] !== session_id()) {
    session_destroy();
    header('Location: index.php?error=session_expired'); // Redirect with session expired error
    exit();
}

// Fetch user balance and last generated time
$balanceQuery = $db->prepare("SELECT balance, last_generated FROM users WHERE username = :username");
$balanceQuery->bindValue(':username', $user, SQLITE3_TEXT);
$balanceResult = $balanceQuery->execute();
$balanceRow = $balanceResult->fetchArray(SQLITE3_ASSOC);

$balance = $balanceRow ? $balanceRow['balance'] : 0;
$lastGenerated = $balanceRow ? $balanceRow['last_generated'] : 0;
$currentTime = time();

$generatedAccount = "";
$errorMessage = "";

// Handle stock generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_name'])) {
    if (($currentTime - $lastGenerated) < $cooldownTime) {
        $errorMessage = "Please wait before generating again.";
    } elseif ($balance >= $costPerAccount) {
        $serviceName = $_POST['service_name'];
        $serviceDb = new SQLite3($serviceName);

        // Fetch a random stock
        $query = $serviceDb->query("SELECT id, username, password FROM stocks ORDER BY RANDOM() LIMIT 1");
        $stock = $query->fetchArray(SQLITE3_ASSOC);

        if ($stock) {
            $generatedAccount = "Username: " . htmlspecialchars($stock['username']) . "<br>Password: " . htmlspecialchars($stock['password']);

            // Remove the generated account from the database
            $deleteQuery = $serviceDb->prepare("DELETE FROM stocks WHERE id = :id");
            $deleteQuery->bindValue(':id', $stock['id'], SQLITE3_INTEGER);
            $deleteQuery->execute();

            // Deduct balance
            $newBalance = $balance - $costPerAccount;
            $updateBalance = $db->prepare("UPDATE users SET balance = :balance, last_generated = :last_generated WHERE username = :username");
            $updateBalance->bindValue(':balance', $newBalance, SQLITE3_FLOAT);
            $updateBalance->bindValue(':last_generated', $currentTime, SQLITE3_INTEGER);
            $updateBalance->bindValue(':username', $user, SQLITE3_TEXT);
            $updateBalance->execute();

            // Refresh balance
            $balance = $newBalance;
        } else {
            $errorMessage = "No accounts available in this service.";
        }
    } else {
        $errorMessage = "Insufficient balance.";
    }
}

// Scan the current directory for .db files excluding `db.db`
$dbFiles = array_filter(glob("*.db"), function ($file) {
    return basename($file) !== 'db.db';
});

// Store session ID in the database to ensure only one session per user
$updateSession = $db->prepare("UPDATE users SET session_id = :session_id WHERE username = :username");
$updateSession->bindValue(':session_id', session_id(), SQLITE3_TEXT);
$updateSession->bindValue(':username', $user, SQLITE3_TEXT);
$updateSession->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        h1 {
            margin-bottom: 20px;
        }
        a {
            color: #1e90ff;
            text-decoration: none;
            margin-top: 20px;
        }
        a:hover {
            text-decoration: underline;
        }
        select {
            background-color: #333333;
            color: #ffffff;
            padding: 10px;
            border: 1px solid #444444;
            border-radius: 5px;
            outline: none;
        }
        select:focus {
            border-color: #1e90ff;
        }
        .container {
            text-align: center;
        }
        .account-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #222;
            border: 1px solid #444;
            display: inline-block;
            border-radius: 5px;
            font-size: 16px;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
    <link rel="stylesheet" href='s.css'>
</head>
<body>
    <div class="container">
        <h1 style="font-family: 'unbounded', serif" class="gradient"><img src="paradise.png" alt="Paradise" style="width: 30px; height: 30px; margin-right" draggable="false"> Paradise</h1>
        <h1>Welcome, <i><u><?php echo $user; ?></u></i>!</h1>
        <p>You currently have <b>$<?php echo number_format($balance, 3); ?></b>!</p>
        
        <h3>Select a Service:</h3>
        <form method="POST" action="">
            <select name="service_name" required>
                <option value="" selected>Select a service</option>
                <?php foreach ($dbFiles as $dbFile): ?>
                    <option value="<?php echo htmlspecialchars($dbFile); ?>">
                        <?php echo htmlspecialchars(pathinfo($dbFile, PATHINFO_FILENAME)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <button type="submit" class="Button">Generate</button>
        </form>
        
        <?php if ($generatedAccount): ?>
            <div class="account-box">
                <b>Generated Account:</b><br>
                <?php echo $generatedAccount; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <br>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
