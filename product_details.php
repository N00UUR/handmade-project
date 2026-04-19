<?php
include 'db_connect.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    echo "<p>Product not found.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Product Details</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="product-details">
            <div class="product-image">
                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                <p class="product-price">$<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></p>
                <p class="product-available">Available: <?php echo htmlspecialchars($product['available_count']); ?> in stock</p>
                <button class="add-to-cart"  onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <?php include 'chatbot_widget.php'; ?>

</body>
</html>
