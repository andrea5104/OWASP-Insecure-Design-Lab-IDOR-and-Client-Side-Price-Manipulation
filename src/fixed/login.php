<?php
session_start();
require_once 'config.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE LOWER(nome) = LOWER(?)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hash_password, $role);
            $stmt->fetch();

            if ($password === $hash_password) {
                $_SESSION['user_id'] = $id;
                $_SESSION['role'] = $role;
                $_SESSION['username'] = $username;

                header("Location: home.php");
                exit;
            } else {
                $error = "Password errata.";
            }
        } else {
            $error = "Username non trovato.";
        }

        $stmt->close();
    } else {
        $error = "Compila tutti i campi.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login E-commerce Demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="center-container">
    <h2>Login</h2>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <input type="submit" value="Login">
    </form>
</body>
</html>
