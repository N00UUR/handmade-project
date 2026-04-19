<?php
session_start();

include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in to add items to your cart.";
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

$stmt = $conn->prepare("SELECT quantity FROM carts WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($quantity);
    $stmt->fetch();
    $stmt->close();

    $new_quantity = $quantity + 1;
    $update_stmt = $conn->prepare("UPDATE carts SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
    $update_stmt->execute();
    $update_stmt->close();

    echo "Quantity updated in cart.";
} else {
    $stmt->close();
    $insert_stmt = $conn->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $insert_stmt->bind_param("ii", $user_id, $product_id);
    $insert_stmt->execute();
    $insert_stmt->close();

    echo "Product added to cart.";
}
?>
