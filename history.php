<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'employee';

$month = date('m');
$year = date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['month'], $_POST['year'])) {
    $month = $_POST['month'];
    $year = $_POST['year'];
}


$startDate = "$year-$month-01";
$endDate = date("Y-m-t", strtotime($startDate)); 


if ($role === 'employee') {
    $sql = "
        SELECT w.*, u.first_name, u.last_name 
        FROM work_hours w 
        JOIN users u ON w.user_id = u.id 
        WHERE w.user_id = ? AND w.work_date BETWEEN ? AND ? 
        ORDER BY w.work_date DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $userId, $startDate, $endDate);
} else {
    $sql = "
        SELECT w.*, u.first_name, u.last_name 
        FROM work_hours w 
        JOIN users u ON w.user_id = u.id 
        WHERE w.work_date BETWEEN ? AND ? 
        ORDER BY w.work_date DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
}

$stmt->execute();
$result = $stmt->get_result();


if ($role === 'employee') {
    $sumSql = "SELECT SUM(hours) AS total_month_hours FROM work_hours WHERE user_id = ? AND work_date BETWEEN ? AND ?";
    $sumStmt = $conn->prepare($sumSql);
    $sumStmt->bind_param("iss", $userId, $startDate, $endDate);
    $sumStmt->execute();
    $sumResult = $sumStmt->get_result()->fetch_assoc();
    $totalMonthHours = $sumResult['total_month_hours'] ?? 0;
} else {
    $sumSql = "
        SELECT u.first_name, u.last_name, SUM(w.hours) AS total_month_hours
        FROM work_hours w
        JOIN users u ON w.user_id = u.id
        WHERE w.work_date BETWEEN ? AND ?
        GROUP BY w.user_id
        ORDER BY u.first_name, u.last_name
    ";
    $sumStmt = $conn->prepare($sumSql);
    $sumStmt->bind_param("ss", $startDate, $endDate);
    $sumStmt->execute();
    $sumResult = $sumStmt->get_result();
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8" />
<title>Historia godzin pracy</title>
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
    header h1 {
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
    h2 {
        margin-top: 0;
    }
    form.filter-form {
        margin-bottom: 20px;
    }
    label {
        font-weight: bold;
        margin-right: 10px;
    }
    select {
        padding: 5px 10px;
        margin-right: 15px;
        border-radius: 5px;
        border: 1px solid #ccc;
    }
    button {
        padding: 7px 15px;
        background: #007bff;
        border: none;
        color: white;
        font-weight: bold;
        border-radius: 5px;
        cursor: pointer;
    }
    button:hover {
        background: #0056b3;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    table th, table td {
        padding: 12px;
        border: 1px solid #ccc;
        text-align: left;
    }
    table th {
        background: #eee;
    }
    .summary {
        margin-top: 30px;
        font-size: 1.1rem;
    }
    .summary table {
        max-width: 500px;
        margin-top: 10px;
    }
</style>
</head>
<body>

<header>
    <h1>Historia godzin pracy</h1>
    <nav>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="work_hours.php">Godziny pracy</a>
        <?php if ($role === 'moderator' || $role === 'admin'): ?>
            <a href="users.php">Zarządzaj użytkownikami</a>
        <?php endif; ?>
        <a href="logout.php">🚪 Wyloguj</a>
    </nav>
</header>

<main>
    <h2>Wybierz miesiąc i rok</h2>
    <form method="post" class="filter-form">
        <label for="month">Miesiąc:</label>
        <select id="month" name="month" required>
            <?php
            for ($m=1; $m<=12; $m++) {
                $mVal = str_pad($m, 2, '0', STR_PAD_LEFT);
                $selected = ($mVal == $month) ? 'selected' : '';
                echo "<option value='$mVal' $selected>$mVal</option>";
            }
            ?>
        </select>

        <label for="year">Rok:</label>
        <select id="year" name="year" required>
            <?php
            $currentYear = date('Y');
            for ($y = $currentYear - 5; $y <= $currentYear + 1; $y++) {
                $selected = ($y == $year) ? 'selected' : '';
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
        </select>

        <button type="submit">Pokaż</button>
    </form>

    <h2>Historia godzin pracy dla <?= htmlspecialchars("$month/$year") ?></h2>
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
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['work_date']) ?></td>
                <td><?= number_format($row['hours'], 2) ?></td>
                <td><?= htmlspecialchars($row['comment'] ?? '') ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">Brak danych dla wybranego miesiąca.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="summary">
        <?php if ($role === 'employee'): ?>
            <h3>Podsumowanie miesięczne</h3>
            <p>Łącznie przepracowanych godzin w <strong><?= htmlspecialchars("$month/$year") ?></strong>: <strong><?= number_format($totalMonthHours, 2) ?></strong></p>
        <?php else: ?>
            <h3>Podsumowanie miesięczne (wszyscy pracownicy)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Pracownik</th>
                        <th>Łączna liczba godzin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rowSum = $sumResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($rowSum['first_name'] . ' ' . $rowSum['last_name']) ?></td>
                        <td><?= number_format($rowSum['total_month_hours'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
