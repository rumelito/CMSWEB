<?php
session_start();
require_once './mongodb/vendor/autoload.php';
use MongoDB\BSON\ObjectId;

// client connection to mongodb
$client = new MongoDB\Client("mongodb://localhost:27017");
$shop = $client->cdmlinkup->shop;
$updates = $client->cdmlinkup->updates;
$users = $client->cdmlinkup->users;
$postCollection = $client->cdmlinkup->posts;
$collection = $client->cdmlinkup->events;
$admins = $client->cdmlinkup->admins;

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
  }
  


if (isset($_POST['add_product'])) {
    $productName = htmlspecialchars($_POST['productName']);
    $price = floatval($_POST['price']);
    $availableSizes = htmlspecialchars($_POST['availableSizes']); 
    $stock = intval($_POST['stock']);
    $colors = $_POST['backgroundColor'];

    if (isset($_FILES['image']['tmp_name']) && !empty($_FILES['image']['tmp_name'])) {
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($fileType, $allowedTypes)) {
        $image = 'uploads/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);

       
        $shop->insertOne([
            'productName' => $productName,
            'price' => $price,
            'availableSizes' => $availableSizes,
            'stock' => $stock,
            'backgroundColor' => $colors,
            'image' => $image
        ]);
        echo "<script>alert('Product added successfully!.'); window.location.href='admindashboard.php';</script>";
        exit();
    } else {
        echo "<script>alert('Invalid file type. Please upload a valid image.');  window.location.href='admindashboard.php'; </script>";
        exit();
    }
} 
 
}



if (isset($_POST['add_update'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $backColor = $_POST['backColor'];
    $image = 'uploads/' . $_FILES['update_image']['name'];
    move_uploaded_file($_FILES['update_image']['tmp_name'], $image);

    $updates->insertOne([
        'title' => $title,
        'description' => $description,
        'backColor' => $backColor,
        'image' => $image
    ]);
    echo "<script>alert('Update added successfully!'); window.location.href='admindashboard.php';</script>";
    exit();
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $productId = $_POST['product_id'];
    $shop->deleteOne(['_id' => new MongoDB\BSON\ObjectId($productId)]);
    echo "<script>alert('Product deleted successfully!'); window.location.href='admindashboard.php';</script>";
    exit();
}

// Handle update deletion
if (isset($_POST['delete_update'])) {
    $updateId = $_POST['update_id'];
    $updates->deleteOne(['_id' => new MongoDB\BSON\ObjectId($updateId)]);
    echo "<script>alert('Update deleted successfully!'); window.location.href='admindashboard.php';</script>";
    exit();
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];

    try {
       
        $userObjectId = new MongoDB\BSON\ObjectId($userId);

        
        $deleteUserResult = $users->deleteOne(['_id' => $userObjectId]);

        $deletePostsResult = $postCollection->deleteMany(['userId' => $userObjectId]);

        // If the user was deleted successfully
        if ($deleteUserResult->getDeletedCount() > 0) {
            echo "<script>alert('User and their posts deleted successfully!'); window.location.href='';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to delete user. Please try again.');</script>";
            exit();
        }
    } catch (Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        
    }
}



if (isset($_POST['edit_product'])) {
    $productId = $_POST['product_id'];
    $product = $shop->findOne(['_id' => new MongoDB\BSON\ObjectId($productId)]);
        $_SESSION['edit_product'] = (array)$product;
        header('Location: editproduct.php');
        exit;
}


if (isset($_POST['edit_update'])) {
    $updateId = $_POST['update_id'];
    $update = $updates->findOne(['_id' => new MongoDB\BSON\ObjectId($updateId)]);
    $_SESSION['edit_update'] = (array)$update;
    header("Location: editschoolupdate.php"); 
    exit;
}



// Helper function to validate ObjectId
function isValidObjectId($id) {
    return preg_match('/^[a-f0-9]{24}$/', $id);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && !empty($_POST['id']) && isValidObjectId($_POST['id'])) {
        // Update event
        $id = $_POST['id'];
        $title = $_POST['title'];
        $start = $_POST['start'];
        $color = $_POST['color'];

        $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            ['$set' => ['title' => $title, 'start' => $start, 'color' => $color]]
        );

        echo "<script>alert('Event updated successfully!'); window.location.href = 'admindashboard.php';</script>";
        exit;

       
    } else {
        // Add event CALENDAR   
        $title = $_POST['title'];
        $start = $_POST['start'];
        $color = $_POST['color'];

        $collection->insertOne([
            'title' => $title,
            'start' => $start,
            'color' => $color
        ]);

        echo "<script>alert('Event added successfully!'); window.location.href = 'admindashboard.php';</script>";
        exit;
        
       
    }
}

// Handle DELETE request to delete an event
if (isset($_GET['delete']) && isValidObjectId($_GET['delete'])) {
    $id = $_GET['delete'];
    $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    echo "<script>alert('Event deleted successfully!'); window.location.href = 'admindashboard.php';</script>";
    exit;
} elseif (isset($_GET['delete'])) {
    echo "<script>alert('Invalid event ID!');  window.location.href='admindashboard.php';</script>";
    exit;
}

// Fetch events from MongoDB
$events = [];
$cursor = $collection->find();
foreach ($cursor as $event) {
    $events[] = [
        'id' => (string)$event['_id'],
        'title' => $event['title'],
        'start' => $event['start'],
        'color' => $event['color'],
    ];
}

if (isset($_POST['update_product'])) {
    $productId = $_POST['product_id'];
    $productName = $_POST['productName'];
    $price = $_POST['price'];
    
    $availableSizes = $_POST['availableSizes'];  
    $stock = $_POST['stock'];
    $colors = $_POST['backgroundColor'];

    $image = $_FILES['image']['name'] ? 'uploads/' . $_FILES['image']['name'] : null;
    if ($image) {
        move_uploaded_file($_FILES['image']['tmp_name'], $image);
    }

    $updateData = [
        'productName' => $productName,
        'price' => (float)$price,
        'availableSizes' => $availableSizes,  
        'stock' => (int)$stock,
        'backgroundColor' => $colors,
    ];
    if ($image) {
        $updateData['image'] = $image;
    }

    $shop->updateOne(
        ['_id' => new ObjectId($productId)],
        ['$set' => $updateData]
    );

    echo "<script>alert('Product updated successfully!'); window.location.href='admindashboard.php';</script>";
    exit();
}



if (isset($_POST['update_update'])) {
    $updateId = $_POST['update_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $backColor = $_POST['backColor'];

    $image = $_FILES['update_image']['name'] ? 'uploads/' . $_FILES['update_image']['name'] : null;
    if ($image) {
        move_uploaded_file($_FILES['update_image']['tmp_name'], $image);
    }

    $updateData = [
        'title' => $title,
        'description' => $description,
        'backColor' => $backColor,
    ];

    if ($image) {
        $updateData['image'] = $image;
    }

    $updates->updateOne(
        ['_id' => new ObjectId($updateId)],
        ['$set' => $updateData]
    );

    echo "<script>alert('Update updated successfully!'); window.location.href='admindashboard.php';</script>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Admin of CDM LinkUp</title>
    <link rel="icon" type="image/x-icon" href="images/cdmicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>
   <style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
}

#calendar {
      padding: 20px;
      max-width: 900px;
      margin: 20px auto;
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }


header {
    background-color: #4CAF50; 
    padding: 10px 0;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.navbar .logo a {
    color: #fff;
    font-size: 24px;
    text-decoration: none;
    font-weight: bold;
}

.nav-links {
    display: flex;
    list-style: none;
}

.nav-links li {
    margin-left: 30px;
}

.nav-link {
    color: #fff;
    text-decoration: none;
    font-size: 20px;
    font-weight: bold;
    padding: 10px 15px;
    display: inline-block;
    transition: background-color 0.3s ease;
}

.nav-link:hover {
    background-color: #45a049; 
    border-radius: 5px;
}


/* Container for all content */
.content {
    padding: 20px;
    margin: 20px;
}

h1 {
    color: #2e8b57; 
    font-size: 2em;
    margin-bottom: 15px;
}

/* Section headers */
h3 {
    color: #2e8b57;
    font-size: 1.5em;
    margin-bottom: 10px;
}

/* Label styling */
label {
    font-size: 1em;
    color: #555;
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

/* Form styling */
.form-container {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

/* General feed container */
.feed-container {
    display: flex;
    flex-direction: column;
    align-items: center; 
    justify-content: flex-start;
    margin-top: 20px;
    padding: 0 10px;
}

/* Individual feed card */
.feed-card {
    background-color: #ffffff;
    border: 1px solid #e4e6eb;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    width: 100%;
    max-width: 600px;
    box-sizing: border-box;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease-in-out;
    font-family: 'Arial', sans-serif;
}

/* Hover effect for feed cards */
.feed-card:hover {
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
}

/* Header section with profile image and username */
.feed-card .header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.feed-card .avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #ccc;
    margin-right: 10px;
}

.feed-card .username {
    font-weight: bold;
    font-size: 1.1em;
    color: #2e8b57; 
}

/* Content section */
.feed-card .content {
    margin-bottom: 15px;
}

.feed-card h4 {
    font-size: 1.4em;
    margin: 0 0 10px;
    color: black;
}

/* Price styling for product */
.feed-card .price {
    font-size: 1.2em;
    font-weight: bold;
    color: black;
    margin-bottom: 10px;
}

/* Description for both products and updates */
.feed-card .description1 {
    font-size: 1em;
    color: black;
    margin-bottom: 15px;
}
.feed-card .description {
    font-size: 1em;
    color: black;
    margin-bottom: 15px;
}

/* Display available sizes as comma-separated list */
.feed-card .sizes {
    font-size: 0.9em;
    color: black;
    margin-bottom: 10px;
}

/* Display stock information for products */
.feed-card .stock {
    font-size: 0.9em;
    color: black;
    margin-bottom: 10px;
}

/* Image styling */
.feed-card img {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
}


.feed-card .actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9em;
    color: #555;
}

.feed-card .actions button {
    background: none;
    border: none;
    font-size: 1em;
    color: #2e8b57;
    cursor: pointer;
    transition: color 0.3s;
}

.feed-card .actions button:hover {
    color: #1e6b41;
}


.feed-card .post-options {
    display: flex;
    justify-content: flex-end;
    margin-top: 15px;
}

.feed-card .post-options form {
    display: inline;
}

.feed-card .post-options input[type="submit"] {
    background-color: #2e8b57;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    margin-right: 5px;
}

.feed-card .post-options input[type="submit"]:hover {
    background-color: #1e6b41;
}

/* Delete button styling */
.delete-button {
    background-color: #d9534f;
}

.delete-button:hover {
    background-color: #c9302c;
}

/* Edit button styling */
.edit-button {
    background-color: #2e8b57;
}

.edit-button:hover {
    background-color: #1e6b41;
}

/* Media queries for smaller screens */
@media screen and (max-width: 768px) {
    .feed-card {
        width: 100%;
        padding: 10px;
    }

    .feed-card .header {
        flex-direction: column;
        align-items: flex-start;
    }

    .feed-card .avatar {
        margin-bottom: 10px;
    }

    .feed-container {
        padding: 0 20px;
    }
}
/* Delete Button */
.feed-card form input[name="delete_product"],
.feed-card form input[name="delete_update"],
.feed-card form input[name="delete_user"] {
    background-color: #dc3545;
    color: #fff;
}

.feed-card form input[name="delete_product"]:hover,
.feed-card form input[name="delete_update"]:hover,
.feed-card form input[name="delete_user"]:hover {
    background-color: #c82333;
    transform: scale(1.05);
}

/* Edit Button */
.feed-card form input[name="edit_product"],
.feed-card form input[name="edit_update"] {
    background-color: #007bff;
    color: #fff;
}

.feed-card form input[name="edit_product"]:hover,
.feed-card form input[name="edit_update"]:hover {
    background-color: #0056b3;
    transform: scale(1.05);
}

#addEventForm {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
    }
    #addEventForm input, #addEventForm select, #addEventForm button {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #a5d6a7;
      border-radius: 4px;
      font-size: 1rem;
    }
    #addEventForm button {
      background-color: #2e8b57;
      color: #ffffff;
      border: none;
      cursor: pointer;
    }
    #addEventForm button:hover {
      background-color: #1e6b41;
    }
    .event-actions {
      display: flex;
      justify-content: space-between;
      padding: 10px;
      background-color: #f1f1f1;
      margin-top: 5px;
      border-radius: 8px;
    }
    .event-actions button {
      background-color: #ff7043;
      color: white;
      border: none;
      cursor: pointer;
    }
    .event-actions button:hover {
      background-color: #f4511e;
    }

    /* General Styles for the Modal */
#eventActionsModal {
  background-color: #fff;
  padding: 20px;
  border-radius: 8px;
 
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  position: relative;
  font-family: Arial, sans-serif;

  padding: 20px;
    max-width: 900px;
    margin: 20px auto;
    
    
    
}

#eventActionsModal h2 {
  text-align: center;
  font-size: 24px;
  margin-bottom: 20px;
  color: #333;
}

#eventActionsModal form {
  display: flex;
  flex-direction: column;
}

#eventActionsModal input,
#eventActionsModal select,
#eventActionsModal button {
  margin: 10px 0;
  padding: 10px;
  border-radius: 4px;
  border: 1px solid #ccc;
  font-size: 16px;
}

#eventActionsModal input[type="text"],
#eventActionsModal input[type="date"] {
  width: 100%;
}

#eventActionsModal select {
  width: 100%;
}

.backselect{
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}
#eventActionsModal button {
  cursor: pointer;
  background-color: #4CAF50;
  color: white;
  border: none;
  transition: background-color 0.3s ease;
}

#eventActionsModal button:hover {
  background-color: #45a049;
}

#deleteEventButton {
  background-color: #f44336;
  color: white;
  border: none;
  width: 100%;
  padding: 12px;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

#deleteEventButton:hover {
  background-color: #e53935;
}




   </style>

   
<script>
function showContent(section) {
   
    const sections = document.querySelectorAll('.content');
    sections.forEach(sec => sec.style.display = 'none');

  
    const content = document.getElementById(section);
    if (content) {
        content.style.display = 'block';
    }
}


window.onload = function() {
    showContent('post'); 
};

document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: <?php echo json_encode($events); ?>,
    editable: true,
    eventClick: function(info) {

      console.log("Event clicked:", info.event);

      const event = info.event;
      document.getElementById('eventId').value = event.id;
      document.getElementById('eventTitle').value = event.title;
      document.getElementById('eventStart').value = event.start.toISOString().split('T')[0];
      document.getElementById('eventColor').value = event.color;


      document.getElementById('eventActionsModal').style.display = 'block';
    }
  });

  calendar.render();

  // Handle deleting the event
  document.getElementById('deleteEventButton').addEventListener('click', function() {
    const eventId = document.getElementById('eventId').value;
    if (confirm('Are you sure you want to delete this event?')) {
      window.location.href = `admindashboard.php?delete=${eventId}`;
    }
  });
});

    </script>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="logout.php">CDM Linkup</a>
            </div>
            <ul class="nav-links">
                <li><a href="#" class="nav-link" onclick="showContent('post')">Post</a></li>
                <li><a href="#" class="nav-link" onclick="showContent('products')">Products</a></li>
                <li><a href="#" class="nav-link" onclick="showContent('updates')">School Updates</a></li>
                <li><a href="#" class="nav-link" onclick="showContent('events')">Future Events</a></li>
                <li><a href="#" class="nav-link" onclick="showContent('users')">Users</a></li>
            </ul>
        </nav>
    </header>

    <!-- Content sections -->
    <div class="content" id="post">
    <form method="POST" action="" enctype="multipart/form-data" class="form-container">
    <h1>Post Products</h1>

    <!-- Product Name -->
    <label for="productName">Product Name:</label>
    <input type="text" id="productName" name="productName" placeholder="Product Name:" required>

    <!-- Price -->
    <label for="price">Price:</label>
    <input type="number" id="price" name="price" placeholder="Price:" required>

    <!-- Available Sizes (Single Text Input) -->
    <label for="availableSizes">Available Sizes (comma-separated):</label>
    <input type="text" id="availableSizes" name="availableSizes" placeholder="e.g., S, M, L" required>

    <!-- Stock Quantity -->
    <label for="stock">Stock Quantity:</label>
    <input type="number" id="stock" name="stock" placeholder="Stocks:" required>

            <label>Background Color:</label>
            <select name="backgroundColor" id="backgroundColor" class="backselect" placeholder="Choose Color:" required>
                <option value="">Choose Color</option> 
                <option value="#1db330">Green (CDM)</option>
                <option value="#FFAE42">Orange (ICS)</option>
                <option value="#FFFF33">Yellow (IBE)</option>
                <option value="#00BFFF">Blue (ITE)</option>
            </select>
 
    <!-- Product Image -->
    <label for="image">Image:</label>
    <input type="file" id="image" name="image" required>

    <input type="submit" name="add_product" value="Add Product">
</form>

<form method="POST" enctype="multipart/form-data" class="form-container">
    <h3>Add School Update</h3>
    <label for="title">Title:</label>
    <input type="text" id="title" name="title" placeholder="Title:" required>
    
    <label for="description">Description:</label>
    <textarea id="description" name="description" placeholder="Description:" required></textarea>

    <label>Background Color:</label>
            <select name="backColor" id="backColor" class="backselect" placeholder="Choose Color:" required>
                
                <option value="">Choose Color</option> 
                <option value="#1db330">Green (CDM)</option>
                <option value="#FFAE42">Orange (ICS)</option>
                <option value="#FFFF33">Yellow (IBE)</option>
                <option value="#00BFFF">Blue (ITE)</option>
            </select>
    
    <label for="update_image">Image:</label>
    <input type="file" id="update_image" name="update_image">
    
    <input type="submit" name="add_update" value="Add Update">
</form>

<div id="addEventForm" class="form-container">
    <form method="POST" action="">
    <h3>Add Events Update</h3>  
      <input type="text" name="title" placeholder="Event Title" required>
      <input type="date" name="start" required>
      <select name="color" required>
        <option value="">Choose Color</option>
                <option value="#1db330">Green (CDM)</option>
                <option value="#FFAE42">Orange (ICS)</option>
                <option value="#FFFF33">Yellow (IBE)</option>
                <option value="#00BFFF">Blue (ITE)</option>
      </select>
      <button type="submit">Add Event</button>
    </form>
  </div>

    </div>
    
    <div class="content" id="products">
    <h1>Products</h1>

    <div class="feed-container">
    <?php
   
    $products = $shop->find();

    // Handle delete or edit actions
    // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //     if (isset($_POST['delete_product'])) {
    //         // Handle delete action
    //         $productId = $_POST['product_id'];
    //         $result = $shop->deleteOne(['_id' => new ObjectId($productId)]);
    //         if ($result->getDeletedCount() > 0) {
    //             echo "<script>alert('Product deleted successfully'); window.location.href='admindashboard.php';</script>";
    //         } else {
    //             echo "<script>alert('Failed to delete product');</script>";
    //         }
    //     } else if (isset($_POST['edit_product'])) {
    //         // Handle edit action
    //         $productId = $_POST['product_id'];
    //         $_SESSION['edit_product'] = $shop->findOne(['_id' => new ObjectId($productId)])->getArrayCopy();
    //         header("Location: editproduct.php"); // Redirect to edit page
            
            
    //     }
    // }

    foreach ($products as $product) {
        echo "<div class='feed-card' style='background-color: " . htmlspecialchars($product['backgroundColor']) . "; '>";
        echo "<h4 style='overflow: hidden;'>Product Name: " . htmlspecialchars($product['productName'] ?? 'Unknown Product') . "</h4>";

        $price = $product['price'] ?? 'N/A';
        echo "<p class='description1'>Price: ₱" . htmlspecialchars($price) . "</p>";

      
        if (!empty($product['availableSizes'])) {
            $sizes = explode(',', $product['availableSizes']);
            echo "<p class='sizes' style='overflow: hidden;'>Available Sizes: " . htmlspecialchars(implode(', ', $sizes)) . "</p>";
        } else {
            echo "<p class='sizes' style='overflow: hidden;'>Available Sizes: None</p>";
        }

        // Handle stock
        $stock = $product['stock'] ?? 0;
        echo "<p class='stock'>Stock: " . htmlspecialchars($stock) . "</p>";

        // Handle image
        $image = $product['image'] ?? 'default-image.jpg';
        echo "<img src='" . htmlspecialchars($image) . "' alt='" . htmlspecialchars($product['productName'] ?? 'Product') . "' />";

        // Form for Delete and Edit actions
        echo "<form method='POST'>
         <input type='hidden' name='product_id' value='" . $product['_id'] . "' />
         <input type='submit' name='delete_product' value='Delete' />
         <input type='submit' name='edit_product' value='Edit' />
         </form>";
        echo "</div>";
    }
    ?>
    </div>

</div>
    <div class="content" id="updates">
        <h1>School Updates</h1>

        <div class="feed-container">
            <?php
            $allUpdates = $updates->find();
            foreach ($allUpdates as $update) {
                echo "<div class='feed-card' style='background-color: " . htmlspecialchars($update['backColor']) . "; '>";
                echo "<h4 style='overflow: hidden;'>Title: " . $update['title'] . "</h4>";
                echo "<p class='description' style='overflow: hidden;'>Description: " . $update['description'] . "</p>";
               
                echo "<img src='" . $update['image'] . "' alt='" . $update['title'] . "' />";
                echo "<form method='POST'>
                        <input type='hidden' name='update_id' value='" . $update['_id'] . "' />
                        <input type='submit' name='delete_update' value='Delete' />
                        <input type='submit' name='edit_update' value='Edit' />
                      </form>";
                echo "</div>";
            }
            ?>
        </div>

    </div>
    <div class="content" id="events">
        <h1>Future Events</h1>

        <div id="eventActionsModal">
    <h2>Edit Event</h2>
    <form method="POST" action="">
      <input type="hidden" name="id" id="eventId">
      <input type="text" name="title" id="eventTitle" placeholder="Event Title" required>
      <input type="date" name="start" id="eventStart" required>
      <select name="color" id="eventColor" required>
        <option value="">Choose Color</option>
                <option value="#1db330">Green (CDM)</option>
                <option value="#FFAE42">Orange (ICS)</option>
                <option value="#FFFF33">Yellow (IBE)</option>
                <option value="#00BFFF">Blue (ITE)</option>
      </select>
      <button type="submit">Update Event</button>
    </form>
    <button id="deleteEventButton" style="background-color: #f44336; color: white;">Delete Event</button>
  </div>

        <div id="calendar"></div>

    </div>

    
 <div class="content" id="users">
        <h1>Users</h1>
        <div class="feed-container">
        <?php
    $allUsers = $users->find(); // Fetch all users from the collection
    foreach ($allUsers as $user) {
        echo "<div class='feed-card'>";
        echo "<h4>First Name: " . htmlspecialchars($user['name']) . "</h4>";
        echo "<p>Last Name: " . htmlspecialchars($user['lastName']) . "</h4>";
        echo "<p>Student Number: " . htmlspecialchars($user['studentNo']) . "</p>";
        echo "<p class='description'>Email: " . htmlspecialchars($user['email']) . "</p>";
        echo "<form method='POST'>
                <input type='hidden' name='user_id' value='" . $user['_id'] . "' />
                <input type='submit' name='delete_user' value='Delete User' class='delete-button' />
              </form>";
        echo "</div>";
    }
?>
    </div>


    </div>


    </div>

</body>
</html>
