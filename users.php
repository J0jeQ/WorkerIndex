<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
    header("Location: login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$message = '';

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateRole($role) {
    $roles = ['employee', 'moderator', 'admin'];
    return in_array($role, $roles);
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'employee';

    if (!$id || !$firstName || !$lastName || !validateEmail($email) || !validateRole($role)) {
        $message = "Wypełnij poprawnie wszystkie wymagane pola.";
    } else {
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("sssssi", $firstName, $lastName, $email, $hashedPassword, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $firstName, $lastName, $email, $role, $id);
        }

        if ($stmt->execute()) {
            header("Location: users.php?message=Użytkownik zaktualizowany pomyślnie");
            exit();
        } else {
            $message = "Błąd aktualizacji: " . $stmt->error;
        }

        $stmt->close();
    }
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'employee';

    if (!$firstName || !$lastName || !validateEmail($email) || !$password || !validateRole($role)) {
        $message = "Wypełnij poprawnie wszystkie wymagane pola.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            header("Location: users.php?message=Użytkownik dodany pomyślnie");
            exit();
        } else {
            $message = "Błąd dodawania użytkownika: " . $stmt->error;
        }

        $stmt->close();
    }
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editId = intval($_GET['id'] ?? 0);
    if ($editId > 0) {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editUser = $result->fetch_assoc();
        $stmt->close();

        if (!$editUser) {
            header("Location: users.php?message=Użytkownik nie znaleziony");
            exit();
        }
    } else {
        header("Location: users.php");
        exit();
    }
}

if ($action === 'delete') {
    $deleteId = intval($_GET['id'] ?? 0);
    if ($deleteId > 0) {
        if ($deleteId === $_SESSION['user_id']) {
            $message = "Nie możesz usunąć swojego konta.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $deleteId);
            if ($stmt->execute()) {
                header("Location: users.php?message=Użytkownik usunięty pomyślnie");
                exit();
            } else {
                $message = "Błąd usuwania użytkownika: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $message = "Niepoprawny ID użytkownika.";
    }
}

$result = $conn->query("SELECT id, first_name, last_name, email, role FROM users ORDER BY last_name, first_name");
$users = $result->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8" />
    <title>Zarządzanie użytkownikami</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
        header { background-color: #007bff; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 1.5rem; }
        nav a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; }
        nav a:hover { text-decoration: underline; }
        main { max-width: 900px; margin: 40px auto; background: white; padding: 25px 30px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #007bff; color: white; }
        a.button, button {
            background-color: #007bff;
            border: none;
            color: white;
            padding: 6px 14px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }
        a.button:hover, button:hover { background-color: #0056b3; }
        form {
            margin-top: 30px;
            background: #f0f8ff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
        }
        form label { display: block; margin-top: 15px; font-weight: bold; }
        form input[type="text"], form input[type="email"], form input[type="password"], form select {
            width: 100%;
            padding: 8px;
            margin-top: 6px;
            box-sizing: border-box;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .message { margin-top: 15px; color: green; font-weight: bold; }
    </style>
</head>
<body>
<header>
    <h1>Zarządzanie użytkownikami</h1>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="work_hours.php">Godziny pracy</a>
        <a href="users.php">Użytkownicy</a>
        <a href="logout.php">Wyloguj się</a>
    </nav>
</header>

<main>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if (!$action || $action === 'list'): ?>
        <a href="users.php?action=add" class="button">Dodaj nowego użytkownika</a>
        <table>
            <thead>
                <tr>
                    <th>Imię</th>
                    <th>Nazwisko</th>
                    <th>Email</th>
                    <th>Rola</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['first_name']) ?></td>
                        <td><?= htmlspecialchars($user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <a href="users.php?action=edit&id=<?= $user['id'] ?>" class="button">Edytuj</a>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <a href="users.php?action=delete&id=<?= $user['id'] ?>" class="button" onclick="return confirm('Na pewno usunąć tego użytkownika?')">Usuń</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <h2><?= $action === 'add' ? "Dodaj nowego użytkownika" : "Edytuj użytkownika" ?></h2>
        <form method="post" action="users.php?action=<?= $action ?>">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($editUser['id']) ?>" />
            <?php endif; ?>

            <label for="first_name">Imię:</label>
            <input type="text" id="first_name" name="first_name" required value="<?= $action === 'edit' ? htmlspecialchars($editUser['first_name']) : '' ?>" />

            <label for="last_name">Nazwisko:</label>
            <input type="text" id="last_name" name="last_name" required value="<?= $action === 'edit' ? htmlspecialchars($editUser['last_name']) : '' ?>" />

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?= $action === 'edit' ? htmlspecialchars($editUser['email']) : '' ?>" />

            <label for="password"><?= $action === 'edit' ? "Nowe hasło (opcjonalnie):" : "Hasło:" ?></label>
            <input type="password" id="password" name="password" <?= $action === 'add' ? 'required' : '' ?> />

            <label for="role">Rola:</label>
            <select id="role" name="role" required>
                <?php
                $roles = ['employee' => 'Employee', 'moderator' => 'Moderator', 'admin' => 'Admin'];
                $selectedRole = $action === 'edit' ? $editUser['role'] : 'employee';
                foreach ($roles as $val => $label) {
                    $sel = $selectedRole === $val ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>$label</option>";
                }
                ?>
            </select>

            <br><br>
            <button type="submit"><?= $action === 'add' ? "Dodaj użytkownika" : "Zapisz zmiany" ?></button>
            <a href="users.php" class="button" style="background:#6c757d; margin-left:10px;">Anuluj</a>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
