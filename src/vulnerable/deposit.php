<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    
    if ($amount === false || $amount <= 0) {
        $error = "Importo non valido. Inserisci un valore positivo.";
    } else {
        $stmt_balance = $conn->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt_balance->bind_param("i", $user_id);
        $stmt_balance->execute();
        $stmt_balance->bind_result($user_balance);
        $stmt_balance->fetch();
        $stmt_balance->close();

        $new_balance = $user_balance + $amount;

        $stmt_update = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt_update->bind_param("di", $new_balance, $user_id);
        
        if ($stmt_update->execute()) {
            $message = "Deposito di €" . number_format($amount, 2) . " completato con successo. Il tuo nuovo saldo è di €" . number_format($new_balance, 2) . ".";
        } else {
            $error = "Si è verificato un errore durante il deposito.";
        }
        $stmt_update->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Deposita Denaro - E-commerce Demo</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <h1>Deposita Denaro</h1>
    <p><a href="home.php">Torna alla Home</a> | <a href="logout.php">Logout</a></p>
    <hr>

    <?php if ($error): ?>
        <p style='color:red;'><?php echo $error; ?></p>
    <?php elseif ($message): ?>
        <p style='color:green;'><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="post" action="deposit.php">
        <label for="amount">Importo da depositare:</label><br>
        <input type="number" id="amount" name="amount" min="0.01" step="0.01" required><br><br>
        <input type="submit" value="Deposita">
    </form>
</body>
</html>