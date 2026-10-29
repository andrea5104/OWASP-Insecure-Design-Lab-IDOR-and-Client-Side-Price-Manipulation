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
    
    $total_price_from_client = $_POST['total_price'];

    if($total_price_from_client < 0){
        $error = "totale carrello in negativo. Impossibile completare l ordine";
    }

    elseif (empty($_SESSION['cart'])) {
        $error = "Il tuo carrello è vuoto. Impossibile completare l'ordine.";
    } else {
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if ($row['quantity'] < $item['quantity']) {
                    $error = "Quantità insufficiente in magazzino per il prodotto: " . htmlspecialchars($item['name']);
                    break;
                }
            } else {
                $error = "Uno o più prodotti nel carrello non sono validi.";
                break;
            }
        }
        $stmt->close();
        
        if (empty($error)) {
            $stmt_balance = $conn->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt_balance->bind_param("i", $user_id);
            $stmt_balance->execute();
            $stmt_balance->bind_result($user_balance);
            $stmt_balance->fetch();
            $stmt_balance->close();

            if ($user_balance < $total_price_from_client) {
                $error = "Saldo insufficiente per completare l'acquisto. Il tuo saldo è di €" . number_format($user_balance, 2) . ".";
            } 
            else {
                $conn->begin_transaction();
                
                try {
                    // Aggiornamento saldo utente
                    $new_balance = $user_balance - $total_price_from_client;
                    $stmt_update = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
                    $stmt_update->bind_param("di", $new_balance, $user_id);
                    $stmt_update->execute();
                
                    // Inserimento ordine nel database
                    $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'completed')");
                    $stmt_order->bind_param("id", $user_id, $total_price_from_client);
                    $stmt_order->execute();
                    $order_id = $stmt_order->insert_id;
                    
                    // Inserimento ordine in order_list
                    foreach ($_SESSION['cart'] as $product_id => $item) {
                        $price_per_unit = $item['price'];
                        $quantity = $item['quantity'];
                        $stmt_list = $conn->prepare("INSERT INTO order_list (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                        $stmt_list->bind_param("iiid", $order_id, $product_id, $quantity, $price_per_unit);
                        $stmt_list->execute();

                        //Diminuizione della quantità nel database
                        $stmt_quantity = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                        $stmt_quantity->bind_param("ii", $quantity, $product_id);
                        $stmt_quantity->execute();
                        $stmt_quantity->close();
                    }

                    $conn->commit();
                    $message = "Ordine #$order_id completato! Totale: €" . number_format($total_price_from_client, 2) . ". Nuovo saldo: €" . number_format($new_balance, 2) . ".";
                    
                    $_SESSION['cart'] = [];

                } catch (mysqli_sql_exception $e) {
                    $conn->rollback();
                    $error = "Errore durante la transazione: " . $e->getMessage();
                }
            }
        }
    }
} else {
    header("Location: products.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Conferma Ordine - E-commerce Demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Conferma Ordine</h1>
    <hr>
    <?php if ($message): ?>
        <p style='color:green;'><?php echo $message; ?></p>
    <?php else: ?>
        <p style='color:red;'><?php echo $error; ?></p>
    <?php endif; ?>

    <p><a href="home.php">Torna alla Home</a> | <a href="orders.php">Visualizza i miei ordini</a></p>
</body>
</html>