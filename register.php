<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$conn = mysqli_connect("localhost", "root", "", "workerindex");
if (!$conn) {
    die("Błąd połączenia: " . mysqli_connect_error());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!$first_name || !$last_name || !$email || !$password || !$password_confirm) {
        $error = "Wszystkie pola są wymagane.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Niepoprawny format e-mail.";
    } elseif ($password !== $password_confirm) {
        $error = "Hasła się nie zgadzają.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Ten e-mail jest już zarejestrowany.";
        }
        mysqli_stmt_close($stmt);
    }

    if (!$error) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $first_name, $last_name, $email, $password_hash);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Rejestracja zakończona sukcesem. Możesz się teraz zalogować.";
        } else {
            $error = "Błąd podczas rejestracji.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8" />
<title>Rejestracja</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .register-box {
        background: white;
        padding: 30px 25px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
        box-sizing: border-box;
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
    }
    input[type=text],
    input[type=email],
    input[type=password] {
        width: 100%;
        box-sizing: border-box;
        padding: 12px;
        margin: 10px 0;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 1rem;
    }
    button {
        width: 100%;
        padding: 12px;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        font-size: 1rem;
    }
    button:hover {
        background: #218838;
    }
    .error {
        color: red;
        margin-bottom: 15px;
        text-align: center;
    }
    .success {
        color: green;
        margin-bottom: 15px;
        text-align: center;
    }
    .bottom-text {
        margin-top: 15px;
        text-align: center;
        font-size: 0.9rem;
    }
    .bottom-text a {
        color: #007bff;
        text-decoration: none;
    }
    .bottom-text a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<div class="register-box">
    <h2>Rejestracja</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <input type="text" name="first_name" placeholder="Imię" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" />
        <input type="text" name="last_name" placeholder="Nazwisko" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" />
        <input type="email" name="email" placeholder="E-mail" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        <input type="password" name="password" placeholder="Hasło" required />
        <input type="password" name="password_confirm" placeholder="Powtórz hasło" required />
        <button type="submit">Zarejestruj się</button>
    </form>

    <div class="bottom-text">
        Masz już konto? <a href="login.php">Zaloguj się tutaj</a>
    </div>
</div>
</body>
</html>