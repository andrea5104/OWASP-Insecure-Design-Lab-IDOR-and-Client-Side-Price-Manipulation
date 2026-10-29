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

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    $stmt = $conn->prepare("SELECT o.id, o.total, o.created_at, u.nome
                           FROM orders o
                           JOIN users u ON o.user_id = u.id
                           WHERE o.id = ?"); 
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = "Ordine non trovato.";
    } else {
        $order_details = $result->fetch_assoc();
        
        $stmt_products = $conn->prepare("SELECT p.name, ol.quantity, ol.price
                                       FROM order_list ol
                                       JOIN products p ON ol.product_id = p.id
                                       WHERE ol.order_id = ?");
        $stmt_products->bind_param("i", $order_id);
        $stmt_products->execute();
        $products_result = $stmt_products->get_result();
    }
} else {
    $stmt = $conn->prepare("SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>I miei ordini - E-commerce Demo</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="user-info">
        <?php echo htmlspecialchars(strtoupper($_SESSION['username'])); ?>
    </div>
    <div class="main-content">
        <h1>I miei ordini</h1>
        <p><a href="home.php">Torna alla Home</a> | <a href="logout.php">Logout</a></p>
        <hr>
        
        <?php if ($error): ?>
            <p style='color:red;'><?php echo $error; ?></p>
        <?php elseif (isset($order_details)): ?>
            <div class="centered-container">
                <h2>Dettagli Ordine #<?php echo htmlspecialchars($order_details['id']); ?></h2>
                <div class="order-details-container">
                    <p>Acquirente: <strong><?php echo htmlspecialchars($order_details['nome']); ?></strong></p>
                    <p>Data: <?php echo htmlspecialchars($order_details['created_at']); ?></p>
                    <p>Totale: €<?php echo number_format($order_details['total'], 2); ?></p>
                </div>
                
                <h3>Prodotti ordinati:</h3>
                <table>
                    <tr>
                        <th>Prodotto</th>
                        <th>Quantità</th>
                        <th>Prezzo per unità</th>
                    </tr>
                    <?php while ($row = $products_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td>€<?php echo number_format($row['price'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <p><a href="orders.php">Torna alla lista degli ordini</a></p>
            </div>
        <?php else: ?>
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>ID Ordine</th>
                        <th>Totale</th>
                        <th>Stato</th>
                        <th>Data</th>
                        <th>Azione</th>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td>€<?php echo number_format($row['total'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td><a href="orders.php?order_id=<?php echo $row['id']; ?>">Visualizza Dettagli</a></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>Non hai ancora effettuato ordini.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>