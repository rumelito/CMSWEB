<?php
session_start();
require_once './mongodb/vendor/autoload.php';
use MongoDB\BSON\ObjectId;

// MongoDB connection
$client = new MongoDB\Client("mongodb://localhost:27017");
$updates = $client->cdmlinkup->updates;

// Ensure update session is set
if (!isset($_SESSION['edit_update'])) {
    echo "<script>alert('No update selected for editing.'); window.location.href='admindashboard.php';</script>";
    
}

$item = $_SESSION['edit_update'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_update'])) {
    $update_id = $_POST['update_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $backColor = $_POST['backColor'];

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
            'backColor' => $backColor,
            'image' => $updateImagePath
        ]]
    );

    if ($result->getModifiedCount() > 0) {
        echo "<script>alert('Update content updated successfully.'); window.location.href='admindashboard.php';</script>";
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
    <title>Edit Update</title>
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
<div class="form-container" style="background-color: <?= htmlspecialchars($item['backColor']) ?>;">
    <h1>Edit Update</h1>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="update_id" value="<?php echo htmlspecialchars($item['_id']); ?>">

        <label for="title">Title:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
        
        <label for="description">Description:</label>
        <textarea name="description" required><?php echo htmlspecialchars($item['description']); ?></textarea>

        <label>Background Color:</label>
            <select name="backColor" id="backColor" class="backselect" placeholder="Choose Color:" required>
                
                <option value="">Choose Color</option> 
                <option value="#1db330">Green (CDM)</option>
                <option value="#FFAE42">Orange (ICS)</option>
                <option value="#FFFF33">Yellow (IBE)</option>
                <option value="#00BFFF">Blue (ITE)</option>
            </select>

        <label for="update_image">Change Image:</label>
        <input type="file" name="update_image">
        <p>Current Image:</p>
        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Current Update Image">

        
        <input type="submit" name="update_update" value="Update">
    </form>
</div>
</body>
</html>
