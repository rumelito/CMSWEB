<?php
session_start();
require_once './mongodb/vendor/autoload.php';


$databaseConnection = new MongoDB\Client('mongodb://localhost:27017');
$signUpDb = $databaseConnection->cdmlinkup;
$userCollection = $signUpDb->users;
$postCollection = $signUpDb->posts;

$client = new MongoDB\Client("mongodb://localhost:27017");
$shop = $client->cdmlinkup->shop;
$updates = $client->cdmlinkup->updates;
$users = $client->cdmlinkup->users;
$postCollection = $client->cdmlinkup->posts;
$collection = $client->cdmlinkup->events;

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['user']; 


$user = $userCollection->findOne(['email' => $_SESSION['email']]);

// Handle post creation, editing, deletion, and commenting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle post creation
    if (isset($_POST['postContent'], $_POST['backgroundColor'], $_POST['font'])) {
        $postCollection->insertOne([
            'userId' => $user['_id'],
            'name' => $user['name'],
            'profilePicture' => $user['profilePicture'] ?? 'uploads/default.jpg',
            'content' => $_POST['postContent'],
            'backgroundColor' => $_POST['backgroundColor'],
            'font' => $_POST['font'],
            'postType' => 'freedomWall', 
            'comments' => []
        ]);
        echo "<script>alert('Post created!'); window.location.href='dash.php';</script>";
        exit();

    }

    // Handle post deletion
    elseif (isset($_POST['delete_id'])) {
        $postCollection->deleteOne([
            '_id' => new MongoDB\BSON\ObjectId($_POST['delete_id']),
            'userId' => $user['_id']
        ]);
    }

    // Handle post editing
     elseif (isset($_POST['edit_id'], $_POST['newContent'])) {
      $postId = new MongoDB\BSON\ObjectId($_POST['edit_id']);
      $newContent = $_POST['newContent'];

      $post = $postCollection->findOne([
          '_id' => $postId,
          'userId' => $user['_id']
      ]);

      if ($post) {
          $updateResult = $postCollection->updateOne(
              ['_id' => $postId],
              ['$set' => ['content' => $newContent]]
          );

          if ($updateResult->getModifiedCount() > 0) {
              echo json_encode([
                  'success' => true,
                  'updatedContent' => htmlspecialchars($newContent)
              ]);
          } else {
              echo json_encode(['success' => false, 'message' => 'No changes were made to the post.']);
          }
      } else {
          echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized.']);
      }

      exit();
  }


    elseif (isset($_POST['post_id'], $_POST['commentContent'])) {
        $postCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['post_id'])],
            ['$push' => [
                'comments' => [
                    'userId' => $user['_id'],
                    'name' => $user['name'],
                    'content' => $_POST['commentContent']
                ]
            ]]
           
        );
        echo "<script>alert('Comment successfully!');  window.location.href = 'dash.php';</script>";
        exit();
    }
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

// Fetch posts from MongoDB
$postsCursor = $postCollection->find([], ['sort' => ['_id' => -1]]);
$posts = iterator_to_array($postsCursor);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CdM LinkUp</title>
  <link rel="icon" type="image/x-icon" href="images/cdmicon.png">
  <link rel="stylesheet" href="dash.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.css" rel="stylesheet">
  <script src="dash.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>
  <script>function deletePost(postId) {
  if (confirm('Are you sure you want to delete this post?')) {
      const form = document.createElement('form');
      form.method = 'POST';
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'delete_id';
      input.value = postId;
      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
  }
}

function editPost(postId) {
const newContent = prompt("Edit your post:");
if (newContent) {
  const form = new FormData();
  form.append('newContent', newContent);
  form.append('edit_id', postId);

  fetch('', {
      method: 'POST',
      body: form,
  })
  .then(response => response.json())
  .then(data => {
      if (data.success) {
          alert("Post updated successfully.");
          // Update the specific post content on the page
          const postContentElement = document.querySelector(`#post_${postId} .post-content`);
          if (postContentElement) {
              postContentElement.textContent = data.updatedContent;
          }
      } else {
          alert('Failed to update the post: ' + (data.message || 'Unknown error'));
      }
  })
  .catch(error => {
      alert('An error occurred while updating the post.');
  });
}
}

document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  
  // Initialize the FullCalendar instance
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: <?php echo json_encode($events); ?>,
    editable: true,
    eventClick: function (info) {
      console.log("Event clicked:", info.event); 

      const event = info.event;
  
      document.getElementById('eventId').value = event.id;
      document.getElementById('eventTitle').value = event.title;
      document.getElementById('eventStart').value = event.start.toISOString().split('T')[0];
      document.getElementById('eventColor').value = event.color;

    
      document.getElementById('eventActionsModal').style.display = 'block';
    },
  });

  
  calendar.render();

  
  function refreshCalendarVisibility() {
    if (calendarEl.offsetParent !== null) {
      calendar.updateSize(); 
    }
  }

  
  const observer = new MutationObserver(() => {
    refreshCalendarVisibility();
  });

  
  observer.observe(calendarEl.parentElement, { attributes: true, childList: true, subtree: true });

  
  document.querySelectorAll("aside a").forEach((link) => {
    link.addEventListener("click", function () {
      setTimeout(() => refreshCalendarVisibility(), 300); 
    });
  });
});

</script>

<style>

#forum-section {
  margin-left: 250px;
  justify-content: center;
  align-items: center;
  margin-top: 50px;
  width: 100%; 
  height: auto; 
  padding: 20px; 
}
#future-section {
  width: auto; 
  height: 93vh; 
  padding: 20px; 
}

#calendar {
  padding: 20px;
  max-width: 950px;
    margin: 20px 28%;
  background: #f4fff4; 
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #a4d6a4;
  font-family: Arial, sans-serif; 
}

/* General grid styling */
.fc-daygrid-day {
  vertical-align: top;
  padding: 5px;
}

.fc-daygrid-day-top {
  font-size: 14px;
  color: #2d662d; 
  font-weight: bold;
  padding: 5px 0;
  text-align: center;
}


  


.fc-day-sun .fc-daygrid-day-top {
  color: #d9534f;
}


.fc-daygrid-day-frame {
  min-height: 100px;
  padding: 5px;
  border: 1px solid #d0e7d0; 
  border-radius: 4px; 
}


.fc-event {
  color: #ffffff;
  border: none;
  padding: 4px 6px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer; 
}


.fc-event:hover {
  background-color: #1f7b1f; 
}


.fc-scrollgrid {
  border: 1px solid #c0dbc0;
}

.fc-scrollgrid-section-header {
  background-color: #cce8cc; 
  color: #2d662d; 
  font-weight: bold;
  text-align: center;
  padding: 8px;
}


@media (max-width: 768px) {
  #calendar {
    max-width: 100%;
    margin: 0 auto;
    padding: 10px;
  }

  .fc-daygrid-day-frame {
    min-height: 80px; 
}
}

.fc-day-today {
  background-color: #d0f0d0; 
  border: 2px solid #5cb85c; 
  box-shadow: inset 0 0 5px rgba(0, 128, 0, 0.3); 
}



/* Feed Container */
.feed-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    justify-content: center;
    align-items: center;
    padding: 20px;
    max-width: 700px; 
    margin: 0 auto;

    
    
}


.feed-card {
    background-color: #e6f5e6; 
    border: 1px solid #b2d8b2; 
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
    padding: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: 100%;
}
.feed-card1 {
    background-color: #e6f5e6; 
    border: 1px solid #b2d8b2; 
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
    padding: 5px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    width: 100%;
  align-items: center;
  margin-bottom: 5px;
}

/* Hover Effect */
.feed-card:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}

/* Title */
.feed-card h4 {
    font-size: 24px; 
    color: #1d643b; 
    margin-bottom: 12px;
    font-weight: bold;
}
.feed-card h4 {
    font-size: 24px; 
    color: #1d643b; 
    margin-bottom: 12px;
    font-weight: bold;
}

/* Description */
.feed-card .description {
    font-size: 18px; 
    color: #3d6e57; 
    margin-bottom: 16px;
    line-height: 1.7;
}

/* Image */
.feed-card img {
    width: 100%; 
    border-radius: 10px;
    height: auto;
    margin-top: 12px;
    border: 1px solid #b2d8b2; 
}

/* Media Query for Mobile */
@media (max-width: 768px) {
    .feed-container {
        padding: 10px;
    }
    
    .feed-card {
        padding: 16px;
    }
    
    .feed-card h4 {
        font-size: 20px;
    }

    .feed-card .description {
        font-size: 16px;
    }
}
.post-button2 {
  background-color: #fff;
 
  margin-top: 5px;
  color: black;
  border: none;
  padding: 12px 25px;
  font-size: 16px;
  cursor: pointer;
  border-radius: 5px;
  width: 104%;
  border: #0bc105 solid 1px;
}

.table-container {
  background-color: #e6f5e6; 
  border: 1px solid #b2d8b2; 
  width: 90%;
  margin: auto;
  overflow-x: auto;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

h1 {
  text-align: center;
  color: #333;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}

thead {
  background-color: #333;
  color: white;
}

thead th {
  padding: 12px;
  text-align: left;
}

tbody td {
  padding: 10px;
  border-bottom: 1px solid #ddd;
  text-align: left;
}

tbody tr:hover {
  background-color: #f1f1f1;
}

.shirt-img {
  width: 100px;
  height: auto;
  border: 1px solid #ccc;
  border-radius: 4px;
}

</style>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside>
      <div class="logo-container">
        <img src="images/cdmicon.png" alt="CdM Icon">
      </div>
      <a href="#" data-section="dashboard-section"><i class="fa fa-home"></i>Home</a>
      <a href="#" data-section="updates-section"><i class="fa fa-bell"></i>School Updates</a>
      <a href="#" data-section="future-section"><i class="fa fa-clock"></i> Future events</a>
      <a href="#" data-section="shop-section"><i class="fa fa-shopping-cart"></i>School Shop List</a>
      <a href="#" data-section="forum-section"><i class="fa fa-comments"></i> Freedom Wall</a>
      <div class="character-container">
        <img src="images/Animation - 1731906256929.gif" alt="Welcome Animation" style="width: 150px; height: 180px; margin: 20px 0;">
      </div>
      
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Dashboard Section -->
      <div id="dashboard-section"  class="content-section">
        <div class="main-content">
          <header class="header">
            <h1><span>CdM</span> LinkUp</h1>
            <div class="user-menu">
              <img src="<?= htmlspecialchars($user['profilePicture'] ?? 'default.jpg'); ?>" alt="User Profile" class="user-icon" onclick="toggleDropdown()">
              <div class="dropdown-content" id="dropdown">
              <a href="aboutus.html">About Us</a>
              <a href="logout.php">Logout</a> 
              </div>
            </div>
          </header>
        
          <section class="banner">
            <img src="images/cover.png" alt="Banner Image">
          </section>
        
          <section class="content-grid">
            <!-- Featured Posts -->
            <div class="featured-posts">
              <h3 class="card-title">Featured Post</h3>
              <div class="card" style="width: 18rem;">
                <img src="images/cdmicon.png" class="card-img-top" alt="...">
                <div class="card-body">
                  <h5 class="card-title">CDM ANNOUNCEMENT</h5>
                  <p class="card-text">CHECK CDM LATEST ANNOUNCEMENT & UPDATES </p>
                  
                </div>
              </div>
            </div>
        
            <!-- CdM Shops -->
            <div class="cdm-shops">
              <h3>CdM Shops</h3>
              
              <div class="cdm-img">
                <img src="images/item1.jpg" alt="1" class="carousel-image">
                <img src="images/item2.jpg" alt="2" class="carousel-image">
                <img src="images/item3.jpg" alt="3" class="carousel-image">
              </div>
              <h5>CHECK AVAILABLE ITEMS IN SHOP</h5>
            </div>
        
            <!-- CdM Calendar -->
            <div class="cdm-calendar">
              <h3>CdM Calendar</h3>
              <div id="calendar-mini"></div>
            </div>
        
            <!-- Latest Topics -->
            <div class="latest-topics">
              <h3>Latest Topics</h3>
              <div class="topic"><div >
            <?php
            $allUpdates = $updates->find();
            foreach ($allUpdates as $update) {
                echo "<div class='feed-card1' style='background-color: " . htmlspecialchars($update['backColor']) . "; '>";
                echo "<h4>" . $update['title'] . "</h4>";
                echo "<p></p>";
                echo "<p class='description'>&nbsp;&nbsp;&nbsp;" . $update['description'] . "</p>";
                echo "</div>";
            }
            ?>
        </div>


              </div>
            </div>
        
            <!-- Latest Feedback -->
            <div class="latest-feedback">
              <h3>Latest Feedback</h3>
              <div class="feedback">

        
              <?php foreach ($posts as $post) : ?>
            <?php if ($post['postType'] === 'freedomWall') : ?>
                <?php
                // Fetch the user associated with the Freedom Wall post
                $postUser = $userCollection->findOne(['_id' => $post['userId']]);
                ?>
                <div class="feed-card1" id="post_<?= htmlspecialchars((string)$post['_id']) ?>" 
                     style="background-color: <?= htmlspecialchars($post['backgroundColor']) ?>; font-family: <?= htmlspecialchars($post['font']) ?>;">
                    <div class="user-info2" style="display: flex; align-items: center; position: relative;">
                        <!-- Profile Picture and Name Wrapper -->
                        <div style="display: flex; align-items: center;">
                            <p><strong><?= htmlspecialchars($post['name']) ?></strong></p>
                        </div>
                    </div>

                    <!-- Display Post Content or Edit Form -->
                    <?php if (isset($_POST['edit_id']) && $_POST['edit_id'] == $post['_id']) : ?>
                        <form action="" method="POST">
                            <textarea name="newContent" rows="4"><?= htmlspecialchars($post['content']) ?></textarea>
                            <input type="hidden" name="edit_id" value="<?= $post['_id'] ?>">
                        </form>
                    <?php else : ?>
                        <p class="post-content">Comment: <?= htmlspecialchars($post['content']) ?></p>
                    <?php endif; ?>

                    <!-- Comments Section -->
                    <div class="comments-section">
                        <?php if (!empty($post['comments'])) : ?>
                            <?php foreach ($post['comments'] as $comment) : ?>
                                <p>Comment: <strong><?= htmlspecialchars($comment['name']) ?> </strong> <?= htmlspecialchars($comment['content']) ?></p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>
            </div>
          </section>
        </div>
      </div>

      <!-- Updates Section -->
      <div id="updates-section" class="content-section" style="display: none;">
        <h2>Updates</h2>
        <p>Here are the latest updates.</p>
        
        <div class="feed-container">
            <?php
            $allUpdates = $updates->find();
            foreach ($allUpdates as $update) {
                echo "<div class='feed-card' style='background-color: " . htmlspecialchars($update['backColor']) . "; '>";
                echo "<h4>" . $update['title'] . "</h4>";
                echo "<p class='description'>" . $update['description'] . "</p>";
                
                echo "<img src='" . $update['image'] . "' alt='" . $update['title'] . "' />";
                echo "</div>";
            }
            ?>
        </div>
      </div>

     <!-- Shop Section -->
<div id="shop-section" class="content-section" style="display: none;">
  <h2><span>CDM</span> SHOP LIST</h2>
  <div class="table-container"  >
    <h1>Shirt Availability</h1>
    <table>
        <thead>
            <tr>
                <th>Picture</th>
                <th>Shirt Name</th>
                <th>Size</th>
                <th>Stock</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Fetch all products from the collection
        $products = $shop->find();

        foreach ($products as $product) {
            echo "<tr style='background-color: " . htmlspecialchars($product['backgroundColor']) . "; '>";

            // Product image
            $image = !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-image.jpg';
            echo "<td><img src='" . $image . "' alt='" . htmlspecialchars($product['productName']) . "' style='width: 100px; height: auto;' /></td>";

            // Product name
            echo "<td>" . htmlspecialchars($product['productName']) . "</td>";

            // Available sizes
            if (!empty($product['availableSizes'])) {
                $sizes = (array) $product['availableSizes'];
                echo "<td>" . htmlspecialchars(implode(', ', $sizes)) . "</td>";
            } else {
                echo "<td>N/A</td>";
            }

            // Stock
            $stock = isset($product['stock']) ? htmlspecialchars($product['stock']) : 'Out of Stock';
            echo "<td>" . $stock . "</td>";

            // Price
            $price = isset($product['price']) ? htmlspecialchars($product['price']) : 'N/A';
            echo "<td>₱" . $price . "</td>";

            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
</div>
 
</div>

     
       <!-- Future Events -->
<div id="future-section" class="content-section" style="display: block;">
  <h2>Future Events</h2>
  <div id="calendar"></div>
</div>



</div>

      <!-- Forum Section -->
      <div id="forum-section" class="content-section" style="display: none;">
        <h2>Freedom Wall</h2>
     
        <div class="container-post">
<div class="post-box">
    <div class="user-info">
        <img src="<?= htmlspecialchars($user['profilePicture'] ?? 'default.jpg'); ?>" alt="User Avatar" class="avatar">
        <span class="user-name">Hello, <?= htmlspecialchars($user['name']); ?></span>
    </div>
    <!-- Post form -->
    <form action="" method="POST">
    <textarea name="postContent" placeholder="What's on your mind?" rows="3" required></textarea>
    <div class="post-options">
        <div>
            <label>Background Color:</label>
            <select name="backgroundColor" required>
                <option value="">Choose Color</option> 

                <option value="#FFAE42">Orange (ICS)</option>
                <option value="#FFFF33">Yellow (IBE)</option>
                <option value="#00BFFF">Blue (ITE)</option>
                
            </select>
        </div>
        <div>
            <label>Font Style:</label>
            <select name="font">
                <option value="Arial">Arial</option>
                <option value="Courier New">Courier New</option>
                <option value="Georgia">Georgia</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Verdana">Verdana</option>
                <option value="Tahoma">Tahoma</option>
                <option value="Trebuchet MS">Trebuchet MS</option>
                <option value="Comic Sans MS">Comic Sans MS</option>
                <option value="Impact">Impact</option>
                <option value="Lucida Console">Lucida Console</option>
                <option value="Brush Script MT">Brush Script MT (Cursive)</option>
            </select>
        </div>
    </div>
    <button type="submit" class="post-button" data-section="forum-section">Post</button>
</form>

</div>


<div class="container2">
    <!-- Freedom Wall Column -->
    <div class="column" style="float: left; width: 100%; ">
        <h3>Freedom Wall</h3>
        <?php foreach ($posts as $post) : ?>
            <?php if ($post['postType'] === 'freedomWall') : ?>
                <?php
                // Fetch the user associated with the Freedom Wall post
                $postUser = $userCollection->findOne(['_id' => $post['userId']]);
                ?>
                <div class="post-container1" id="post_<?= htmlspecialchars((string)$post['_id']) ?>" 
                     style="background-color: <?= htmlspecialchars($post['backgroundColor']) ?>; font-family: <?= htmlspecialchars($post['font']) ?>;">
                    <div class="user-info2" style="display: flex; align-items: center; position: relative;">
                        <!-- Profile Picture and Name Wrapper -->
                        <div style="display: flex; align-items: center;">
                            <img src="<?= htmlspecialchars($postUser['profilePicture'] ?? 'uploads/default.jpg'); ?>" 
                                 alt="User Avatar" 
                                 class="avatar" 
                                 style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">
                            <p><strong><?= htmlspecialchars($post['name']) ?></strong></p>
                        </div>

                        <?php if ((string)$post['userId'] == (string)$user['_id']) : ?>
                            <div class="dropdown-post" style="margin-left: auto;">
                                <button class="dropdown-button-post"><i class="fa fa-cog"></i></button>
                                <div class="dropdown-content1">
                                    <button data-section="forum-section" type="button" class="dropdown-item" onclick="editPost('<?= htmlspecialchars((string)$post['_id']) ?>')">Edit</button>
                                    <form action="" method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?= htmlspecialchars((string)$post['_id']) ?>">
                                        <button data-section="forum-section" type="submit" class="dropdown-item">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Display Post Content or Edit Form -->
                    <?php if (isset($_POST['edit_id']) && $_POST['edit_id'] == $post['_id']) : ?>
                        <form action="" method="POST">
                            <textarea name="newContent" rows="4"><?= htmlspecialchars($post['content']) ?></textarea>
                            <input type="hidden" name="edit_id" value="<?= $post['_id'] ?>">
                            <button type="submit" class="post-button2">Update Post</button>
                        </form>
                    <?php else : ?>
                        <p class="post-content"><?= htmlspecialchars($post['content']) ?></p>
                    <?php endif; ?>

                    <!-- Comments Section -->
                    <div class="comments-section">
                        <h4>Comments</h4>
                        <?php if (!empty($post['comments'])) : ?>
                            <?php foreach ($post['comments'] as $comment) : ?>
                                <p><strong><?= htmlspecialchars($comment['name']) ?>:</strong> <?= htmlspecialchars($comment['content']) ?></p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <form action="" method="POST">
                            <textarea class="textarea1" name="commentContent" placeholder="Add a comment" rows="2"></textarea>
                            <input type="hidden" name="post_id" value="<?= $post['_id'] ?>">
                            <button type="submit" class="post-button2">Comment</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
</div>


      </div>

     
    </div>

   
  </div>
  </div>

 

</body>
</html>
