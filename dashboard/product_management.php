<?php
require '../db_connect.php';
session_start();
if (!isset($_SESSION["is_admin_logged_in"]) || $_SESSION["is_admin_logged_in"] !== true) {
  header("Location: ../login.php");
  exit();
}

if (isset($_POST['delete'])) {
    $product_id = $_POST['product_id'];
    $delete_query = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    header("Location: product_management.php"); 
    exit;
}

$products_query = "
    SELECT id, name, description, available_count, category, price, image_path, created_at 
    FROM products
";
$products_result = $conn->query($products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Product Management</h1>
    </header>
    <div class="main-container">
        <?php include 'navigation.php'; ?>

        <div class="main">
            <a href="add_edit_product.php" class="btn add">Add New Product</a>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Available Count</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Image</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products_result->num_rows > 0): ?>
                        <?php while ($product = $products_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td><?= $product['available_count'] ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td><?= number_format($product['price'], 2) ?></td>
                                <td>
                                    <?php if ($product['image_path']): ?>
                                        <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="Product Image" width="50">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?= $product['created_at'] ?></td>
                                <td>
                                    <a href="add_edit_product.php?id=<?= $product['id'] ?>" class="btn edit">Update</a>
                                    <form action="product_management.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" name="delete" class="btn delete" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
