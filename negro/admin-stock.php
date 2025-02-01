<?php
include_once('checkauth.php');
// check if server method post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceName = htmlspecialchars($_POST['service_name']);
    $stocks = $_POST['stocks'];

    if (!empty($serviceName) && !empty($stocks)) {
        // erm what the sigma
        $serviceName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $serviceName);

        // db conn
        $db = new SQLite3("$serviceName.db");

        // create table db
        $db->exec("
            CREATE TABLE IF NOT EXISTS stocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                password TEXT NOT NULL
            )
        ");

        // prep. statements
        $stmt = $db->prepare("INSERT INTO stocks (username, password) VALUES (:username, :password)");

        // split the thang
        $lines = explode("\n", $stocks);
        $added = 0;

        foreach ($lines as $line) {
            $parts = explode(":", trim($line));
            if (count($parts) === 2) {
                $stmt->bindValue(':username', trim($parts[0]), SQLITE3_TEXT);
                $stmt->bindValue(':password', trim($parts[1]), SQLITE3_TEXT);
                $stmt->execute();
                $added++;
            }
        }

        if ($added > 0) {
            $successMessage = "$added stocks added successfully to $serviceName.db!";
        } else {
            $errorMessage = "No valid stock entries found.";
        }
    } else {
        $errorMessage = "Please provide both service name and stocks.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Stocks</title>
    <style>
        <?php include 's.css'; ?>
    </style>
</head>
<body>
    <div class="container">
        <div class="head">
            <h4>Stock | Paradise</h4>
            <div class="under_line"></div>
        </div>
        <div class="main_box">
            <form method="POST" action="">
                <div class="username">
                    
                    <h3>Service Name</h3>
                </div>
                <div class="username_box">
                    <img src="https://ro-exec.live/icon/key.png">
                    <input type="text" name="service_name" placeholder="Enter service name (e.g., Netflix, Roblox, without spaces)" required>
                </div>
                <div class="pin">
                    <h3><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>Stocks (username:password)</h3>
                </div>
                <div class="pin_box" style="height: 200px;">
                    
                    <textarea name="stocks" rows="10" placeholder="Enter stocks, one per line (e.g., user1:pass1)" style="width: 95%; height: 90%; background-color: transparent; color: #fff; border: none; resize: none;" required></textarea>
                </div>
                <button class="Button" type="submit">Add Stocks</button>
            </form>
            <?php if (isset($successMessage)): ?>
                <p style="color: green; font-family: 'Roboto', sans-serif; margin-top: 20px; text-align: center;"><?php echo $successMessage; ?></p>
            <?php elseif (isset($errorMessage)): ?>
                <p style="color: red; font-family: 'Roboto', sans-serif; margin-top: 20px; text-align: center;"><?php echo $errorMessage; ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
