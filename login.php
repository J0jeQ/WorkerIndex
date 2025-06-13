<?php
session_start();
require_once "db_connect.php";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Nieprawidłowy e-mail lub hasło.";
            }
        } else {
            $error = "Nieprawidłowy e-mail lub hasło.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $error = "Wypełnij oba pola.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8" />
<title>Logowanie</title>
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
    .login-box {
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
        background: #007bff;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        font-size: 1rem;
    }
    button:hover {
        background: #0056b3;
    }
    .error {
        color: red;
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
<div class="login-box">
    <h2>Logowanie</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <input type="email" name="email" placeholder="E-mail" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        <input type="password" name="password" placeholder="Hasło" required />
        <button type="submit">Zaloguj się</button>
    </form>

    <div class="bottom-text">
        Nie masz konta? <a href="register.php">Zarejestruj się tutaj</a>
    </div>
</div>
</body>
</html>