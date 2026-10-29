<?php
session_start();
require_once 'config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT nome, balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nome, $balance);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Home - E-commerce Demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Benvenuto, <?php echo htmlspecialchars($nome); ?>!</h1>
    <p>Saldo disponibile: €<?php echo number_format($balance, 2); ?></p>

    <nav>
        <a href="products.php">Prodotti</a>
        <a href="orders.php">I miei ordini</a>
        <a href="logout.php">Logout</a>
        <a href="deposit.php">Ricarica il conto</a>

        <?php
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <br><br>
            <a href="admin_products.php">Gestione Prodotti (Admin)</a>
        <?php endif; ?>
    </nav>

    
    <hr>
    <p>Benvenuto nella tua area personale dell’E-commerce Demo. Qui puoi consultare i prodotti disponibili e gestire i tuoi ordini.</p>
</body>
</html>
