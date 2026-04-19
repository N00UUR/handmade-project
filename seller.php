<?php
session_start();
if (!isset($_SESSION["is_logged_in"]) || $_SESSION["is_logged_in"] !== true) {
    header("Location: login.php");
    exit();
}
?>

<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['productName'];
    $description = $_POST['productDescription'];
    $category = $_POST['productCategory'];
    $price = $_POST['productPrice'];
    $available_count = $_POST['productCount'];

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["productImage"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (in_array($imageFileType, ["jpg", "jpeg", "png", "gif"]) && move_uploaded_file($_FILES["productImage"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO products (name, description, available_count, category, price, image_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisss", $name, $description, $available_count, $category, $price, $target_file);

        if ($stmt->execute()) {
            echo "Product added successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "There was an error uploading the image.";
    }
}

$conn->close();
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@200;300;400;500;600;700&display=swap">
</head>

<body>
    <div class="wrapper">
        <div class="logout-btn-container">
            <a href="logout.php" class="action-btn">
                <button>Logout</button>
            </a>
        </div>
        <h2>Add New Product</h2>

        <form action="seller.php" method="POST" enctype="multipart/form-data">
            <div class="input-field">
                <input type="text" id="productName" name="productName" required>
                <label for="productName">Product Name</label>
            </div>

            <div class="input-field">
                <textarea id="productDescription" name="productDescription" rows="3" required></textarea>
                <label for="productDescription">Description</label>
            </div>

            <select id="productCategory" name="productCategory" class="select-option" required>
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

            <div class="input-field">
                <input type="number" id="productPrice" name="productPrice" step="0.01" required>
                <label for="productPrice">Price</label>
            </div>

            <div class="input-field">
                <input type="number" id="productCount" name="productCount" required>
                <label for="productCount">Available Count</label>
            </div>

            <div class="input-field">
                <input type="file" id="productImage" name="productImage" accept="image/*" required>
                <label for="productImage">Upload Image</label>
            </div>

            <button type="submit">Add Product</button>
        </form>
    </div>
    <?php include 'chatbot_widget.php'; ?>
</body>
</html>
