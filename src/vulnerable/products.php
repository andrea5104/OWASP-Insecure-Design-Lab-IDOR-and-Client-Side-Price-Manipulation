<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if ($product && $quantity > 0 && $quantity <= $product['quantity']) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
        $message = htmlspecialchars($product['name']) . " è stato aggiunto al carrello.";
    } else {
        $error = "Errore: Prodotto non valido o quantità insufficiente.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $product_id_to_remove = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id_to_remove])) {
        unset($_SESSION['cart'][$product_id_to_remove]);
        $message = "Prodotto rimosso dal carrello.";
    }
}

// Recupera i prodotti dal DB per la visualizzazione
$products_result = $conn->query("SELECT id, name, price, description, quantity FROM products ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Prodotti - E-commerce Demo</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="user-info">
        <?php echo htmlspecialchars(strtoupper($_SESSION['username'])); ?>
    </div>
    <h1>Lista Prodotti</h1>
    <p><a href="home.php">Torna alla Home</a> | <a href="orders.php">I miei ordini</a> | <a href="logout.php">Logout</a></p>
    <hr>
    
    <?php if ($message): ?>
        <p style='color:green;'><?php echo $message; ?></p>
    <?php elseif ($error): ?>
        <p style='color:red;'><?php echo $error; ?></p>
    <?php endif; ?>

    <h2>Il tuo carrello</h2>
    <?php if (count($_SESSION['cart']) > 0): ?>
        <table>
            <tr>
                <th>Prodotto</th>
                <th>Quantità</th>
                <th>Prezzo Totale</th>
                <th>Azione</th>
            </tr>
            <?php 
            $cart_total = 0;
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $item_total = $item['price'] * $item['quantity'];
                $cart_total += $item_total;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                echo "<td>€" . number_format($item_total, 2) . "</td>";
                echo "<td>
                        <form method='post' action='products.php'>
                            <input type='hidden' name='product_id' value='$product_id'>
                            <input type='hidden' name='remove_from_cart' value='1'>
                            <input type='submit' value='Rimuovi'>
                        </form>
                      </td>";
                echo "</tr>";
            }
            ?>
            <tr>
                <td colspan="3" style="text-align:right;"><b>Totale Carrello:</b></td>
                <td><b>€<?php echo number_format($cart_total, 2); ?></b></td>
            </tr>
        </table>
        <br>
        <form method="post" action="checkout.php">
            <input type="hidden" name="total_price" value="<?php echo $cart_total; ?>">
            <input type="submit" value="Procedi al Checkout">
        </form>
        
    <?php else: ?>
        <p>Il tuo carrello è vuoto.</p>
    <?php endif; ?>
    
    <hr>
    
    <h2>Aggiungi Prodotti</h2>
    <?php if ($products_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Nome</th>
                <th>Descrizione</th>
                <th>Prezzo (€)</th>
                <th>Quantità disponibile</th>
                <th>Azione</th>
            </tr>
            <?php while ($row = $products_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td><?php echo number_format($row['price'], 2); ?></td>
                <td><?php echo (int)$row['quantity']; ?></td>
                <td>
                    <?php if ($row['quantity'] > 0): ?>
                        <form method="post" action="products.php">
                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $row['quantity']; ?>">
                            <input type="submit" value="Aggiungi al Carrello">
                        </form>
                    <?php else: ?>
                        <span>Esaurito</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Nessun prodotto disponibile al momento.</p>
    <?php endif; ?>
</body>
</html>