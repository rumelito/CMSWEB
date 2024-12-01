<?php
session_start();
require_once './mongodb/vendor/autoload.php';
use MongoDB\BSON\ObjectId;

// MongoDB connection
$client = new MongoDB\Client("mongodb://localhost:27017");
$shop = $client->cdmlinkup->shop;
$updates = $client->cdmlinkup->updates;

// Determine if we're editing a product or update
$type = $_GET['type'] ?? null;

// Fetch item data
if ($type === 'product' && isset($_SESSION['edit_product'])) {
    $item = $_SESSION['edit_product'];
} elseif ($type === 'update' && isset($_SESSION['edit_update'])) {
    $item = $_SESSION['edit_update'];
} else {
    echo "<script>alert('Invalid edit session or missing type.'); window.location.href='admindashboard.php';</script>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($type === 'product' && isset($_POST['update_product'])) {
        $product_id = $_POST['product_id'];
        $productName = $_POST['productName'];
        $price = $_POST['price'];
        $availableSizes = explode(', ', $_POST['availableSizes']);
        $stock = $_POST['stock'];

        // Handle image upload
        $imagePath = $item['image'];
        if (!empty($_FILES['image']['name'])) {
            $imagePath = 'uploads/' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        }

        // Update the product in the database
        $result = $shop->updateOne(
            ['_id' => new ObjectId($product_id)],
            ['$set' => [
                'productName' => $productName,
                'price' => $price,
                'availableSizes' => $availableSizes,
                'stock' => $stock,
                'image' => $imagePath
            ]]
        );

        if ($result->getModifiedCount() > 0) {
            echo "<script>alert('Product updated successfully'); window.location.href='admindashboard.php';</script>";
        } else {
            echo "<script>alert('No changes made or update failed.'); window.location.href='';</script>";
        }
    } elseif ($type === 'update' && isset($_POST['update_update'])) {
        $update_id = $_POST['update_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];

        // Handle image upload
        $updateImagePath = $item['image'];
        if (!empty($_FILES['update_image']['name'])) {
            $updateImagePath = 'uploads/' . basename($_FILES['update_image']['name']);
            move_uploaded_file($_FILES['update_image']['tmp_name'], $updateImagePath);
        }

        // Update the update in the database
        $result = $updates->updateOne(
            ['_id' => new ObjectId($update_id)],
            ['$set' => [
                'title' => $title,
                'description' => $description,
                'image' => $updateImagePath
            ]]
        );

        if ($result->getModifiedCount() > 0) {
            echo "<script>alert('Update content updated successfully'); window.location.href='admindashboard.php';</script>";
        } else {
            echo "<script>alert('No changes made or update failed.'); window.location.href='';</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo ucfirst($type); ?></title>
    <style>
    
        .form-container {
            max-width: 500px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        .form-container label {
            display: block;
            margin-top: 10px;
        }
        .form-container input, .form-container textarea, .form-container button {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        img {
            max-width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="form-container">
        <h1>Edit <?php echo ucfirst($type); ?></h1>
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if ($type === 'product'): ?>
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['_id']); ?>">
                
                <label for="productName">Product Name:</label>
                <input type="text" name="productName" value="<?php echo htmlspecialchars($item['productName']); ?>" required>

                <label for="price">Price:</label>
                <input type="number" name="price" value="<?php echo htmlspecialchars($item['price']); ?>" required>

                <label for="availableSizes">Available Sizes (comma-separated):</label>
                <input type="text" name="availableSizes" value="<?php echo htmlspecialchars(implode(', ', $item['availableSizes'])); ?>" required>

                <label for="stock">Stock:</label>
                <input type="number" name="stock" value="<?php echo htmlspecialchars($item['stock']); ?>" required>

                <label for="image">Change Image:</label>
                <input type="file" name="image">
                <p>Current Image:</p>
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Current Product Image">
                
                <button type="submit" name="update_product">Update Product</button>
            <?php elseif ($type === 'update'): ?>
                <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($item['_id']); ?>">
                
                <label for="title">Title:</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>

                <label for="description">Description:</label>
                <textarea name="description" required><?php echo htmlspecialchars($item['description']); ?></textarea>

                <label for="update_image">Change Image:</label>
                <input type="file" name="update_image">
                <p>Current Image:</p>
                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Current Update Image">
                
                <button type="submit" name="update_update">Update Update</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
