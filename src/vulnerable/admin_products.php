<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity_to_add = (int)$_POST['quantity'];

    if ($quantity_to_add > 0) {
        $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity_to_add, $product_id);
        
        if ($stmt->execute()) {
            $message = "Quantità aggiornata con successo per il prodotto ID: $product_id";
        } else {
            $error = "Errore durante l'aggiornamento della quantità.";
        }
        $stmt->close();
    } else {
        $error = "La quantità da aggiungere deve essere maggiore di zero.";
    }
}

// Recupera i prodotti per la visualizzazione nel form
$products_result = $conn->query("SELECT id, name, quantity FROM products ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Prodotti Admin - E-commerce Demo</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <h1>Pannello di Gestione Prodotti (Admin)</h1>
    <p><a href="home.php">Torna alla Home</a></p>
    <hr>
    
    <?php if ($message): ?>
        <p style='color:green;'><?php echo $message; ?></p>
    <?php elseif ($error): ?>
        <p style='color:red;'><?php echo $error; ?></p>
    <?php endif; ?>

    <h2>Aggiungi Quantità ai Prodotti</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Quantità Attuale</th>
            <th>Aggiungi Quantità</th>
        </tr>
        <?php if ($products_result->num_rows > 0): ?>
            <?php while ($row = $products_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo (int)$row['quantity']; ?></td>
                    <td>
                        <form method="post" action="admin_products.php">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <input type="number" name="quantity" value="1" min="1" required>
                            <input type="submit" name="add_quantity" value="Aggiungi">
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">Nessun prodotto trovato.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>