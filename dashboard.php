<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$firstName = $_SESSION['first_name'] ?? '';
$lastName = $_SESSION['last_name'] ?? '';
$role = $_SESSION['role'] ?? 'employee';

?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8" />
<title>Dashboard</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f9f9f9;
        margin: 0;
        padding: 0;
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
        max-width: 800px;
        margin: 40px auto;
        background: white;
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    h2 {
        margin-top: 0;
    }
    ul.options {
        list-style: none;
        padding: 0;
    }
    ul.options li {
        margin: 15px 0;
    }
    ul.options li a {
        color: #007bff;
        font-weight: bold;
        text-decoration: none;
        font-size: 1.1rem;
    }
    ul.options li a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<header>
    <h1>Witaj, <?= htmlspecialchars($firstName . ' ' . $lastName) ?></h1>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="work_hours.php">Godziny pracy</a>
        <a href="history.php">Historia</a>
        <?php if ($role === 'moderator' || $role === 'admin'): ?>
            <a href="users.php">Zarządzaj użytkownikami</a>
        <?php endif; ?>
        <a href="logout.php">Wyloguj się</a>
    </nav>
</header>

<main>
    <h2>Panel użytkownika</h2>

    <?php if ($role === 'employee'): ?>
        <p>Jesteś zalogowany jako <strong>pracownik</strong>. Możesz przeglądać swoje godziny pracy i dodawać komentarze.</p>
        <ul class="options">
            <li><a href="work_hours.php">Przeglądaj godziny pracy</a></li>
            <li><a href="history.php">Historia przepracowanych dni</a></li>
        </ul>

    <?php elseif ($role === 'moderator'): ?>
        <p>Jesteś zalogowany jako <strong>moderator</strong>. Możesz zarządzać godzinami pracy pracowników oraz przeglądać użytkowników.</p>
        <ul class="options">
            <li><a href="work_hours.php">Zarządzaj godzinami pracy</a></li>
            <li><a href="history.php">📅 Historia przepracowanych dni</a></li>
            <li><a href="users.php">Zarządzaj użytkownikami</a></li>
        </ul>

    <?php elseif ($role === 'admin'): ?>
        <p>Jesteś zalogowany jako <strong>administrator</strong>. Masz pełny dostęp do zarządzania aplikacją.</p>
        <ul class="options">
            <li><a href="work_hours.php">Zarządzaj godzinami pracy</a></li>
            <li><a href="history.php">Historia przepracowanych dni</a></li>
            <li><a href="users.php">Zarządzaj użytkownikami</a></li>
            <li><a href="settings.php">Ustawienia systemu</a></li>
        </ul>

    <?php else: ?>
        <p>Nieznana rola użytkownika.</p>
    <?php endif; ?>

</main>

</body>
</html>
