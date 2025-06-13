<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hours'])) {
    $employeeId = (int)$_POST['employee_id'];
    $workDate = $_POST['work_date'];
    $hours = (float)$_POST['hours'];
    $comment = $_POST['comment'] ?? null;

    if ($role === 'admin' || $role === 'moderator') {
        $stmt = $conn->prepare("INSERT INTO work_hours (user_id, work_date, hours, comment, added_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsi", $employeeId, $workDate, $hours, $comment, $userId);
        $stmt->execute();
        $stmt->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    $work_id = (int)$_POST['work_id'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    $check = mysqli_query($conn, "SELECT user_id FROM work_hours WHERE id = $work_id");
    $record = mysqli_fetch_assoc($check);

    if ($role === 'admin' || $role === 'moderator' || $userId == $record['user_id']) {
        $update = mysqli_query($conn, "UPDATE work_hours SET comment = '$comment' WHERE id = $work_id");
        $msg = $update ? "Komentarz zaktualizowany." : "Błąd podczas aktualizacji komentarza.";
    } else {
        $msg = "Brak uprawnień do edycji komentarza.";
    }
}


$usersResult = mysqli_query($conn, "SELECT id, first_name, last_name FROM users ORDER BY first_name");
$hoursResult = ($role === 'employee')
    ? mysqli_query($conn, "SELECT w.*, u.first_name, u.last_name FROM work_hours w JOIN users u ON w.user_id = u.id WHERE w.user_id = $userId ORDER BY work_date DESC")
    : mysqli_query($conn, "SELECT w.*, u.first_name, u.last_name FROM work_hours w JOIN users u ON w.user_id = u.id ORDER BY work_date DESC");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8" />
    <title>Godziny pracy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
            margin: 0; padding: 0;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        nav a:hover {
            text-decoration: underline;
        }
        main {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        form {
            margin-bottom: 30px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
        }
        select, input[type="date"], input[type="number"], textarea {
            width: 100%;
            padding: 8px 12px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            resize: vertical;
        }
        button {
            background-color: #28a745;
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #1e7e34;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 12px 15px;
            border: 1px solid #ccc;
            text-align: left;
            vertical-align: top;
        }
        table th {
            background: #eee;
        }
        .success {
            color: #28a745;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .error {
            color: #dc3545;
            font-weight: 600;
            margin-bottom: 20px;
        }
        textarea {
            min-height: 60px;
        }

        td form {
            margin: 0;
        }
        td form textarea {
            margin-bottom: 6px;
        }
        td form button {
            padding: 6px 14px;
            font-size: 0.9rem;
            background-color: #007bff;
        }
        td form button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<header>
    <h2>Godziny pracy</h2>
    <nav>
        <a href="dashboard.php">Panel</a>
        <?php if ($role !== 'employee'): ?>
            <a href="users.php">Użytkownicy</a>
        <?php endif; ?>
        <a href="logout.php">Wyloguj</a>
    </nav>
</header>

<main>
    <?php if (isset($msg)) echo "<p class='success'>" . htmlspecialchars($msg) . "</p>"; ?>

    <?php if ($role !== 'employee'): ?>
        <h3>Dodaj godziny pracy</h3>
        <form method="post">
            <label for="employee_id">Pracownik:</label>
            <select name="employee_id" id="employee_id" required>
                <?php 

                mysqli_data_seek($usersResult, 0);
                while ($u = mysqli_fetch_assoc($usersResult)): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></option>
                <?php endwhile; ?>
            </select>

            <label for="work_date">Data:</label>
            <input type="date" name="work_date" id="work_date" required>

            <label for="hours">Liczba godzin:</label>
            <input type="number" step="0.25" name="hours" id="hours" min="0" max="24" required>

            <label for="comment">Komentarz:</label>
            <textarea name="comment" id="comment" rows="2"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>

            <button type="submit" name="add_hours">Dodaj</button>
        </form>
    <?php endif; ?>

    <h3>Historia pracy</h3>
    <table>
        <thead>
            <tr>
                <th>Pracownik</th>
                <th>Data</th>
                <th>Godziny</th>
                <th>Komentarz</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($hoursResult)): ?>
            <tr>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['work_date']) ?></td>
                <td><?= number_format($row['hours'], 2) ?></td>
                <td>
                    <?php if ($role === 'admin' || $role === 'moderator' || $userId == $row['user_id']): ?>
                        <form method="post">
                            <input type="hidden" name="work_id" value="<?= $row['id'] ?>">
                            <textarea name="comment"><?= htmlspecialchars($row['comment'] ?? '') ?></textarea>
                            <button type="submit" name="comment_submit">Zapisz</button>
                        </form>
                    <?php else: ?>
                        <?= htmlspecialchars($row['comment'] ?? '') ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>

</body>
</html>
