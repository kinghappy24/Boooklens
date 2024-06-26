<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Edit Users</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        table {
            border-collapse: collapse;
            margin: auto; 
        }

        table td, table th {
            border: 1px solid #000;
            padding: 10px;
        }

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

$items_per_page = 8; // genau gleich wie books page ( nur mit kunden)

$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$search_column = isset($_GET['column']) ? $_GET['column'] : 'username';
$date_order = isset($_GET['date_order']) ? $_GET['date_order'] : '';

$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$start_number = ($current_page - 1) * $items_per_page;

if (!in_array($search_column, ['username', 'email'])) {
    $search_column = 'username';
}

$query = "SELECT * FROM users WHERE $search_column LIKE :search_term";
$params = [':search_term' => "%$search_term%"];
if (!empty($date_order)) {
    if ($date_order == 'newest') {
        $query .= " ORDER BY created_at DESC";
    } elseif ($date_order == 'oldest') {
        $query .= " ORDER BY created_at ASC";
    }
}
$query .= " LIMIT :start_number, :items_per_page";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':start_number', $start_number, PDO::PARAM_INT);
$stmt->bindValue(':items_per_page', $items_per_page, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$users = $stmt->fetchAll();

$count_query = "SELECT COUNT(*) as total FROM users WHERE $search_column LIKE :search_term";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute([':search_term' => "%$search_term%"]);
$total_users = $count_stmt->fetch()['total'];

$total_pages = ceil($total_users / $items_per_page);
?>

    <div class="controls">
        <form action="edit_users.php" method="get">
            <input type="text" name="search" placeholder="Search">
            <select name="column">
                <option value="username" <?= $search_column == 'username' ? 'selected' : '' ?>>Username</option>
                <option value="email" <?= $search_column == 'email' ? 'selected' : '' ?>>Email</option>
            </select>
            <select name="date_order">
                <option value="">Sort by Date</option>
                <option value="newest" <?= $date_order == 'newest' ? 'selected' : '' ?>>Datum Neu</option>
                <option value="oldest" <?= $date_order == 'oldest' ? 'selected' : '' ?>>Datum Alt</option>
            </select>
            <input type="submit" value="Search">
            <a href="register.php">Create New User</a>
        </form>
    </div>

    <div class="box">
        <table>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Password</th>
                <th>Join Date</th>
                <th>Edit</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= str_repeat('*', strlen($user['password'])) ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td><a href="edit_user.php?id=<?= htmlspecialchars($user['id']) ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php
    echo '<div class="pagination">';

    $start_page = max(1, $current_page - 5);
    $end_page = min($total_pages, $current_page + 5);

    if ($current_page > 1) {
        $prev_page = max(1, $current_page - 15);
        echo "<a class=\"page-arrow\" href=\"$_SERVER[PHP_SELF]?search=$search_term&column=$search_column&date_order=$date_order&page=$prev_page\"><<</a>";
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            echo "<span class=\"page-number current-page\">$i</span>";
        } else {
            echo "<a class=\"page-number\" href=\"$_SERVER[PHP_SELF]?search=$search_term&column=$search_column&date_order=$date_order&page=$i\">$i</a>";
        }
    }

    if ($current_page < $total_pages) {
        $next_page = min($total_pages, $current_page + 15);
        echo "<a class=\"page-arrow\" href=\"$_SERVER[PHP_SELF]?search=$search_term&column=$search_column&date_order=$date_order&page=$next_page\">>></a>";
    }

    echo '</div>';
    ?>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
