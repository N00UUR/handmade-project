<?php
require '../db_connect.php';

$product_id = $name = $description = $category = "";
$available_count = $price = 0;
$errors = [];
$image_path = null;
$is_edit = isset($_GET['id']);

if ($is_edit) {
    $product_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        $name = $product['name'];
        $description = $product['description'];
        $available_count = $product['available_count'];
        $category = $product['category'];
        $price = $product['price'];
        $image_path = $product['image_path'];
    } else {
        $errors[] = "Product not found.";
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $description = $_POST["description"];
    $available_count = intval($_POST["available_count"]);
    $category = $_POST["category"];
    $price = floatval($_POST["price"]);

    if (empty($name)) $errors[] = "Product name is required.";
    if (empty($description)) $errors[] = "Product description is required.";
    if ($available_count <= 0) $errors[] = "Available count must be greater than 0.";
    if (empty($category)) $errors[] = "Product category is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";

    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../uploads/';
        $file_name = basename($_FILES['image']['name']);
        $target_file = $upload_dir . time() . '_' . $file_name; 
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($image_file_type, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }

        if (empty($errors) && move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = substr($target_file, 3); 
        } else {
            $errors[] = "Failed to upload image.";
        }
    }

    if (empty($errors)) {
        if ($is_edit) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, available_count = ?, category = ?, price = ?, image_path = ? WHERE id = ?");
            $stmt->bind_param("ssisdsi", $name, $description, $available_count, $category, $price, $image_path, $product_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, description, available_count, category, price, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisds", $name, $description, $available_count, $category, $price, $image_path);
        }

        if ($stmt->execute()) {
            header('Location: product_management.php');
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?= $is_edit ? 'Edit Product' : 'Add Product' ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="wrapper">
        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . ($is_edit ? "?id=$product_id" : '') ?>" method="POST" enctype="multipart/form-data">
            <h2><?= $is_edit ? 'Edit Product' : 'Add Product' ?></h2>

            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>

            <div class="input-field">
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
                <label>Product Name</label>
            </div>
            <div class="input-field">
                <textarea name="description" required><?= htmlspecialchars($description) ?></textarea>
                <label>Description</label>
            </div>
            <div class="input-field">
                <input type="number" name="available_count" value="<?= htmlspecialchars($available_count) ?>" min="1" required>
                <label>Available Count</label>
            </div>
            <div class="input-field">
                <select id="productCategory" name="category" class="select-option" required>
                    <option value="">Select Category</option>
                <option value="Home">Home</option>
                <option value="Manual">Manual</option>
                <option value="Spooky Wreath">Spooky Wreath</option>
                <option value="Candel Sconces">Candel Sconces</option>
                <option value="Leopard Print">Leopard Print</option>
                <option value="Baby Clothing">Baby Clothing</option>
                <option value="Necklace">Necklace</option>
                <option value="Art Print">Art Print</option>
                <option value="Art Board">Art Board</option>
                <option value="Water Drawing">Water Drawing</option>
                <option value="Clothing">Clothing</option>
                <option value="Sand Drawing">Sand Drawing</option>
                <option value="Women's">Women's</option>
                <option value="Formal">Formal</option>
                <option value="Casual">Casual</option>
                <option value="Perfume">Perfume</option>
                <option value="Cosmetics">Cosmetics</option>
                <option value="Bags">Bags</option>
                <option value="Jewelry">Jewelry</option>
                <option value="Earrings">Earrings</option>
                <option value="Couple Rings">Couple Rings</option>
                <option value="Bracelets">Bracelets</option>
                <option value="Clothes Perfume">Clothes Perfume</option>
                <option value="Deodorant">Deodorant</option>
                <option value="Flower Fragrance">Flower Fragrance</option>
                <option value="Air Freshener">Air Freshener</option>
                <option value="Blog">Blog</option>
                <option value="Hot Offers">Hot Offers</option>
                <option value="Men's (Mobile)">Men's (Mobile)</option>
                <option value="Shirt">Shirt</option>
                <option value="Shorts & Jeans">Shorts & Jeans</option>
                <option value="Safety Shoes">Safety Shoes</option>
                <option value="Wallet">Wallet</option>
                <option value="Women's (Mobile)">Women's (Mobile)</option>
                <option value="Dress & Frock">Dress & Frock</option>
                <option value="Makeup Kit">Makeup Kit</option>
                <option value="Jewelry (Mobile)">Jewelry (Mobile)</option>
                <option value="Perfume (Mobile)">Perfume (Mobile)</option>
                </select>
            </div>
            <div class="input-field">
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($price) ?>" min="0.01" required>
                <label>Price</label>
            </div>
            <div class="input-field">
                <input type="file" name="image" <?= $is_edit ? '' : 'required' ?>>
                <?php if ($image_path): ?>
                    <img src="../<?= htmlspecialchars($image_path) ?>" alt="Product Image" width="100">
                <?php endif; ?>
            </div>
            <button type="submit"><?= $is_edit ? 'Update' : 'Add' ?> Product</button>
            <p><a href="product_management.php">Back to Product List</a></p>
        </form>
    </div>
</body>

</html>
