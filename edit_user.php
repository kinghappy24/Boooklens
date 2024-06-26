<?php
session_start();
require 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT admin FROM users WHERE username = :username");
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();

if (!$user || !$user['admin']) {
    header("Location: nur_fuer_admins.php");
    exit;
}

include 'header.php';

// nimmt die ID von der person
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['delete'])) {
            
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$user_id])) {
                header('Location: delete_successful.php');
                exit;
            } else {
                header('Location: delete_failed.php');
                exit;
            }
        } elseif (isset($_POST['update'])) {
            // um die daten zu updaten
            $username = $_POST['username'];
            $email = $_POST['email'];
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $admin = isset($_POST['admin']) ? 1 : 0;

            // prüft ob passwort mit dem origianllen übereinstimmt , so dass änderungen vorgenommen werden können
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password'])) {
                // Update password, email, and admin status
                $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET username = ?, email = ?, password = ?, admin = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$username, $email, $hashed_new_password, $admin, $user_id])) {
                    header('Location: change_successful.php');
                    exit;
                } else {
                    header('Location: change_failed.php');
                    exit;
                }
            } else {
                header('Location: change_failed.php');
                exit;
            }
        }
    } else {
        // damit man nicht alles von anfang machen muss , wird hier die ID genommen und bereits eingestezt
        $sql = "SELECT username, email, admin FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $username = $user['username'];
            $email = $user['email'];
            $admin = $user['admin'];
        } else {
            echo "User not found";
            exit;
        }
    }
} else {
    echo "Invalid user ID";
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="styles.css">
    <style>
         /* wir haben hier den sytle angepasst weil es ein wenig unÜbersichtlich wurde in der style file */
        main {
            display: flex; 
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            padding: 20px; 
            width: 100%;
        }

        .box {
            border: 1px solid #000; 
            padding: 20px; 
            box-sizing: border-box; 
            background-color: #BBC5FF;
            margin: 1em;
            font-family: 'Segoe UI', sans-serif;
            border-radius: 15px;
        }
    </style>
</head>
<body>
<main>
    <?php include 'header.php'; ?>

    <div class="box">
        <h1>Edit User</h1>
        <form method="post" action="">
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>"><br>
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"><br>
            <label for="current_password">Current Password:</label><br>
            <input type="password" id="current_password" name="current_password" required><br>
            <label for="new_password">New Password:</label><br>
            <input type="password" id="new_password" name="new_password" required><br><br>
            <label for="admin">Admin:</label>
            <input type="checkbox" id="admin" name="admin" <?php if ($admin) echo 'checked'; ?>><br><br>
            <input type="submit" name="update" value="Update">
        </form>
        <form method="post" action="">
            <input type="submit" name="delete" value="Delete User" style="background-color: red; color: white;">
        </form>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
