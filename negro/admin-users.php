<?php
// db init thing
include_once('checkauth.php');
$db = new SQLite3('db.db');

// db init thing 2
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        password TEXT NOT NULL,
        pin INTEGER NOT NULL,
        license TEXT NOT NULL,
        balance REAL NOT NULL
    )
");

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        // add a new user
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);
        $pin = intval($_POST['pin']);
        $license = htmlspecialchars($_POST['license']);
        $bal = floatval($_POST['bal']);  // convert to float

        // secure that pwd
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // insert into db
        $db->exec("INSERT INTO users (username, password, pin, license, balance) VALUES ('$username', '$hashedPassword', $pin, '$license', $bal)");
    } elseif (isset($_POST['update'])) {
        // update an existing user
        $id = intval($_POST['id']);
        $username = htmlspecialchars($_POST['username']); // get the new username
        $bal = floatval($_POST['bal']);  // convert to float (10.201002 or smth)

        // update the user info
        $db->exec("UPDATE users SET username='$username', balance=$bal WHERE id=$id");
    } elseif (isset($_POST['delete'])) {
        // delete a user
        $id = intval($_POST['id']);
        $db->exec("DELETE FROM users WHERE id=$id");
    }
}

// fetch all users
$users = $db->query("SELECT * FROM users");

// fetch specific user data for updating
$userToUpdate = null;
if (isset($_GET['update_id'])) {
    $idToUpdate = intval($_GET['update_id']);
    $userToUpdate = $db->querySingle("SELECT * FROM users WHERE id = $idToUpdate", true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #2b2b2b;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        h2 {
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table td, table th {
            border: 1px solid #444;
            padding: 10px;
            text-align: left;
            word-wrap: break-word;
        }
        table th {
            background-color: #333;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        form input, form button {
            padding: 10px;
            font-size: 16px;
            margin: 5px 0;
        }
        form button {
            background-color: #5a5;
            color: white;
            border: none;
            cursor: pointer;
        }
        form button:hover {
            background-color: #494;
        }
        .form-box {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #333;
            border-radius: 8px;
        }
        a {
            color: #5a5;
            text-decoration: none;
            border: 2px #444 solid;
            margin-bottom: 10px;
            border-radius: 100px;
        }
    </style>
    <link rel='stylesheet' href='s.css'>
</head>
<body>
    <div class="container">
        <h1>Admin Panel</h1>

        <!-- Display all users -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Password Hash</th>
                    <th>Pin</th>
                    <th>License</th>
                    <th>Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $users->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['password']) ?></td>
                        <td><?= $row['pin'] ?></td>
                        <td><?= htmlspecialchars($row['license']) ?></td>
                        <td>$<?= floatval($row['balance']) ?></td>
                        <td>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete">Delete</button>
                            </form>
                            <!-- Link to update this user -->
                            <a href="?update_id=<?= $row['id'] ?>">Update</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Add a new user -->
        <div class="form-box">
            <h2>Add New User</h2>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="text" name="password" placeholder="Password" required>
                <input type="number" name="pin" placeholder="Pin" required>
                <input type="text" name="license" placeholder="License" required>
                <input type="number" step="0.001" name="bal" placeholder="Balance ($)" required>
                <button type="submit" name="add">Add User</button>
            </form>
        </div>

        <!-- Update user info (with fetched data) -->
        <?php if ($userToUpdate): ?>
        <div class="form-box">
            <h2>Update User Info</h2>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $userToUpdate['id'] ?>">
                <input type="text" name="username" value="<?= htmlspecialchars($userToUpdate['username']) ?>" required>
                <input type="text" name="password" value="******" disabled> <!-- Hide password -->
                <input type="number" name="pin" value="<?= $userToUpdate['pin'] ?>" disabled>
                <input type="text" name="license" value="<?= htmlspecialchars($userToUpdate['license']) ?>" disabled>
                <input type="text" name="bal" value="<?= $userToUpdate['balance'] ?>" required>
                <button type="submit" name="update">Update User Info</button>
            </form>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
