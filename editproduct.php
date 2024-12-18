<?php
session_start();
require_once './mongodb/vendor/autoload.php';
use MongoDB\BSON\ObjectId;

// MongoDB connection
$client = new MongoDB\Client("mongodb://localhost:27017");
$shop = $client->cdmlinkup->shop;

// Ensure update session is set
if (!isset($_SESSION['edit_product'])) {
    echo "<script>alert('No update selected for editing.'); window.location.href='admindashboard.php';</script>";
   
}
$product = $_SESSION['edit_product']; // Retrieve product data from session

// Handle form submission for updating product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $productId = $_POST['product_id'];
    $productName = $_POST['product_name'];
    $price = floatval($_POST['product_price']);
    $availableSizes = htmlspecialchars($_POST['available_sizes']);
    $stock = intval($_POST['product_stock']);
    $colors = $_POST['backgroundColor'];

    // Handle image upload
    $productImage = $product['image']; // Default to existing image
    if (!empty($_FILES['product_image']['name'])) {
        $productImage = 'uploads/' . basename($_FILES['product_image']['name']);
        move_uploaded_file($_FILES['product_image']['tmp_name'], $productImage);
    }

    // Update the product in the database
    $result = $shop->updateOne(
        ['_id' => new ObjectId($productId)],
        ['$set' => [
            'productName' => $productName,
            'price' => $price,
            'availableSizes' => $availableSizes,
            'stock' => $stock,
            'backgroundColor' => $colors,
            'image' => $productImage
        ]]
    );
    
    // Check if the update was successful
    if ($result->getModifiedCount() > 0) {
        echo "<script>alert('Product updated successfully'); window.location.href='admindashboard.php';</script>";
    } else {
        echo "<script>alert('No changes made or update failed.'); window.location.href='admindashboard.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <style>
         .form-container {
            max-width: 500px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        h1 {
    color: black; 
    font-size: 2em;
    margin-bottom: 15px;
}

/* Section headers */
h3 {
    color: black;
    font-size: 1.5em;
    margin-bottom: 10px;
}

/* Label styling */
label {
    font-size: 1em;
    color: black;
    margin-bottom: 8px;
    display: block;
}

/* Input and Textarea fields */
input[type="text"],
input[type="number"],
input[type="file"],
textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

/* Submit Button Styling */
input[type="submit"] {
    background-color: #2e8b57;
    color: white;
    
    border: none;
   
    cursor: pointer;
    font-size: 1em;
    transition: background-color 0.3s;

    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #a5d6a7;
    border-radius: 4px;
    font-size: 1rem;
}

input[type="submit"]:hover {
    background-color: #1e6b41;
}
.backselect {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}
        img {
            max-width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="form-container" style="background-color: <?= htmlspecialchars($product['backgroundColor']) ?>;">
    <h1>Edit Product</h1>
    <form method="POST" action="" enctype="multipart/form-data">
        <!-- Hidden field to hold the product ID -->
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['_id']); ?>">

        <label for="product_name">Product Name:</label>
        <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['productName']); ?>" required>

        <label for="product_price">Product Price:</label>
        <input type="number" name="product_price" value="<?php echo htmlspecialchars($product['price']); ?>" required>

        <label for="available_sizes">Available Sizes (comma-separated):</label>
        <input type="text" name="available_sizes" value="<?php echo htmlspecialchars($product['availableSizes']); ?>" required>

        <label for="product_stock">Stock:</label>
        <input type="number" name="product_stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>

        <label>Background Color:</label>
            <select name="backgroundColor" id="backgroundColor" class="backselect" placeholder="Choose Color:" required>
                <option value="">Choose Color</option> 
                <option value="#1db330">Green (CDM)</option>
                <option value="#FFAE42">Orange (ICS)</option>
                <option value="#FFFF33">Yellow (IBE)</option>
                <option value="#00BFFF">Blue (ITE)</option>
            </select>

        <label for="product_image">Change Product Image:</label>
        <input type="file" name="product_image">
        
        <!-- Display current image -->
        <p>Current Image:</p>
        <?php if (!empty($product['image'])): ?>
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Current Product Image">
        <?php endif; ?>
        <input type="submit" name="update_product" value="Update Product">
    </form>
</div>

</body>
</html>