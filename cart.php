<?php
session_start();

include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in to view your cart.";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity_change = (int)$_POST['quantity_change'];

    $stmt = $conn->prepare("SELECT available_count FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_result = $stmt->get_result();

    if ($product_result->num_rows > 0) {
        $product_row = $product_result->fetch_assoc();
        $available_stock = $product_row['available_count'];

        $stmt = $conn->prepare("SELECT quantity FROM carts WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();

        if ($cart_result->num_rows > 0) {
            $cart_row = $cart_result->fetch_assoc();
            $new_quantity = $cart_row['quantity'] + $quantity_change;

            if ($new_quantity < 1) {
                $stmt = $conn->prepare("DELETE FROM carts WHERE product_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $product_id, $user_id);
                $stmt->execute();
            } elseif ($new_quantity <= $available_stock) {
                $update_stmt = $conn->prepare("UPDATE carts SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
                $update_stmt->execute();
            } else {
                echo "Requested quantity exceeds available stock.";
            }
        }
    }
    header('Location: cart.php');
    exit();
}

$sql = "SELECT products.id, products.name, products.image_path, products.price, carts.quantity 
        FROM carts
        JOIN products ON carts.product_id = products.id
        WHERE carts.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_price = 0;
$vat = 0.19;
$delivery_fee = 4.95;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];

    if (empty($name) || empty($address) || empty($phone)) {
        echo "<script>alert('Please fill in all required fields.');</script>";
    } else {
        $cart_stmt = $conn->prepare("SELECT product_id, quantity FROM carts WHERE user_id = ?");
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_items = $cart_stmt->get_result();

        while ($cart_item = $cart_items->fetch_assoc()) {
            $product_id = $cart_item['product_id'];
            $quantity_purchased = $cart_item['quantity'];

            $update_product_stmt = $conn->prepare("UPDATE products SET available_count = available_count - ? WHERE id = ?");
            $update_product_stmt->bind_param("ii", $quantity_purchased, $product_id);
            $update_product_stmt->execute();

            $remove_product_stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND available_count <= 0");
            $remove_product_stmt->bind_param("i", $product_id);
            $remove_product_stmt->execute();
        }

        $clear_cart_stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
        $clear_cart_stmt->bind_param("i", $user_id);
        if ($clear_cart_stmt->execute()) {
            echo "<script>alert('Order placed successfully! Your cart has been cleared.');</script>";
            header('Location: index.php');
            exit();
        } else {
            echo "Error clearing the cart: " . $clear_cart_stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/cart.css">
    <title>Order Summary</title>
    <style>
        .billing-section {
            margin-top: 20px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }

        .billing-section h2 {
            margin-bottom: 15px;
            color: #333;
        }

        .billing-section label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .billing-section input {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            background-color: #eef;
            color: #333;
        }

        .billing-section .form-row {
            display: flex;
            justify-content: space-between;
        }

        .billing-section .form-row .form-group {
            flex: 0 0 48%;
        }

        .order-table img {
            display: block;
            margin: 0 auto 10px;
        }

        .order-table .thin {
            display: block;
            text-align: center;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="window">
            <div class="order-info">
                <div class="order-info-content">
                    <h2>Order Summary</h2>
                    <div class="line"></div>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $item_total = $row['price'] * $row['quantity'];
                            $total_price += $item_total;
                    ?>

                            <table class="order-table">
                                <tbody>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($row['image_path']); ?>" class="full-width">
                                            <span class="thin"><?php echo htmlspecialchars($row['name']); ?></span>
                                            <br>Price: $<?php echo number_format($row['price'], 2); ?><br>
                                            <form action="cart.php" method="POST">
                                                <div class="quantity-control">
                                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="quantity_change" value="-1">
                                                    <button type="submit" name="update_quantity" class="quantity-btn">-</button>
                                                </div>
                                            </form>
                                            <form action="cart.php" method="POST">
                                                <span id="quantity-<?php echo $row['id']; ?>"><?php echo $row['quantity']; ?></span>
                                                <div class="quantity-control">
                                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="quantity_change" value="1">
                                                    <button type="submit" name="update_quantity" class="quantity-btn">+</button>
                                                </div>
                                            </form>
                                            <br>Subtotal: $<span id="subtotal-<?php echo $row['id']; ?>"><?php echo number_format($item_total, 2); ?></span>
                                            <br><br>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </form>
                            <div class="line"></div>
                    <?php
                        }
                    } else {
                        echo "<p>Your cart is empty.</p>";
                    }
                    ?>
                    <div class="total">
                        <?php
                        $vat_amount = $total_price * $vat;
                        $grand_total = $total_price + $vat_amount + $delivery_fee;
                        ?>
                        <span style="float:left;">
                            <div class="thin dense">VAT (19%)</div>
                            <div class="thin dense">Delivery</div>
                            TOTAL
                        </span>
                        <span>
                            <div class="thin dense">$<span id="vat"><?php echo number_format($vat_amount, 2); ?></span></div>
                            <div class="thin dense">$<?php echo number_format($delivery_fee, 2); ?></div>
                            $<span id="grand-total"><?php echo number_format($grand_total, 2); ?></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="credit-info">
                <div class="credit-info-content">
                    <table class="half-input-table">
                        <tr>
                            <td>Please select your card: </td>
                            <td>
                                <div class="dropdown" id="card-dropdown">
                                    <div class="dropdown-btn" id="current-card">Visa</div>
                                    <div class="dropdown-select">
                                        <ul>
                                            <li>Master Card</li>
                                            <li>American Express</li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <img src="https://dl.dropboxusercontent.com/s/ubamyu6mzov5c80/visa_logo%20%281%29.png" height="80" class="credit-card-image" id="credit-card-image">
                    <p>Card Number</p>
                    <input class="input-field" placeholder="1234 5678 9123 4567">
                    <p>Card Holder</p>
                    <input class="input-field" placeholder="John Doe">
                    <table class="half-input-table">
                        <tr>
                            <td>Expires
                                <input class="input-field" placeholder="MM/YY">
                            </td>
                            <td>CVC
                                <input class="input-field" placeholder="CVC">
                            </td>
                        </tr>
                    </table>
                    <form action="cart.php" method="POST">
                        <div class="billing-section">
                            <h2>Billing Information</h2>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name:</label>
                                    <input type="text" id="name" name="name" placeholder="Your Full Name" required>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address:</label>
                                    <input type="text" id="address" name="address" placeholder="Your Address" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number:</label>
                                <input type="text" id="phone" name="phone" placeholder="Your Phone Number" required>
                            </div>
                            <button class="pay-btn" type="submit" name="checkout">Checkout</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'chatbot_widget.php'; ?>
</body>

</html>
