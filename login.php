<?php

require './mongodb/vendor/autoload.php';

function getDatabase() {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    return $client->cdmlinkup;
}

session_start();

$message = '';
$error = '';

define('ENCRYPTION_KEY', 'your_secret_key_12345'); // Replace with a secure, randomly generated key
define('ENCRYPTION_IV', '1234567891011121'); // Must be 16 bytes for AES-256-CBC

// Function to encrypt the password
function encryptPassword($password) {
    return openssl_encrypt($password, 'AES-256-CBC', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

// Function to decrypt the password
function decryptPassword($encryptedPassword) {
    return openssl_decrypt($encryptedPassword, 'AES-256-CBC', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDatabase();
    $users = $db->users;
    $admins = $db->admins;

    // Determine if the action is sign-up or sign-in
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'signup') {
            // Handle sign-up
            $name = htmlspecialchars(trim($_POST['name']));
            $lastName = htmlspecialchars(trim($_POST['lastName'])); // Get last name
            $studentNo = htmlspecialchars(trim($_POST['studentNo'])); // Get student number
            $email = htmlspecialchars(trim($_POST['email']));
            $password = trim($_POST['password']); // Get the raw password input

            // Validate password length
            if (strlen($_POST['password']) < 8) {
                
                $message = "Password must be at least 8 characters."; 
            } 
            else if ( ! preg_match("/[a-z]/i", $_POST['password']) ) 
                {
                    $message = "Password must contain at least one letter."; 
                }
                else if ( ! preg_match("/[A-Z]/", $_POST['password']) ) 
                {
                
                    $message = "Password must contain at one Capital letter."; 
                    
                }
                
            else if ( ! preg_match("/[0-9]/", $_POST['password']))
                {
                    
                    $message = "Password must contain at least one number.";
                }
            
            else {
                // Encrypt the password
                $encryptedPassword = encryptPassword($password);

                // Handle file upload for profile picture
                $profilePicture = null;
                if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $uploadFile = $uploadDir . basename($_FILES['profilePicture']['name']);

                    if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $uploadFile)) {
                        $profilePicture = $uploadFile;
                    } else {
                        
                        $message = "Failed to upload profile picture.";
                    }
                }

                // Checking if the student number or email is already registered
                $existingUser = $users->findOne(['$or' => [['studentNo' => $studentNo], ['email' => $email]]]);
                 if ($existingUser) {
                    $message = "Student Number or Email already registered!";
                    
                } else {
                    // Inserting the new user into the database
                    $user = [
                        'name' => $name,
                        'lastName' => $lastName, // Add last name
                        'studentNo' => $studentNo, // Add student number
                        'email' => $email,
                        'password' => $encryptedPassword,
                        'profilePicture' => $profilePicture ?? 'uploads/default.jpg',
                    ];
                    $users->insertOne($user);

                    $message = "Account created successfully!";
                    
            
                }
            }
        } elseif ($_POST['action'] === 'signin') {
            $email = htmlspecialchars(trim($_POST['email']));
            $password = trim($_POST['password']);

            // Find the user in the database
            $user = $users->findOne(['email' => $email]);

            if ($user) {
                // Decrypt the stored password and compare
                $decryptedPassword = decryptPassword($user['password']);
                if ($password === $decryptedPassword) {
                    // Store user data in session
                    $_SESSION['user'] = [
                        'id' => (string) $user['_id'],
                        'name' => $user['name'],
                        'lastName' => $user['lastName'],
                        'studentNo' => $user['studentNo'],
                        'email' => $user['email'],
                        'profilePicture' => $user['profilePicture'] ?? 'uploads/default.jpg',
                    ];
                    $_SESSION['email'] = $user['email'];

                    // Redirect to dashboard
                    header("Location: dash.php");
                    exit();
                } else {
                    $error = "Invalid email or password!";
                }
            } else {
                $error = "Invalid email or password!";
            }
            
            $admin = $admins->findOne(['email' => $email]);
            
            if ($admin) {
                // Decrypt the stored password and compare
                $decryptedPassword = decryptPassword($admin['password']);
                if ($password === $decryptedPassword) {
                    // Store user data in session
                    $_SESSION['user'] = [
                        'id' => (string) $admin['_id'],
                        'name' => $admin['name'],
                        'lastName' => $admin['lastName'],
                        'studentNo' => $admin['studentNo'],
                        'email' => $admin['email'],
                        'profilePicture' => $admin['profilePicture'] ?? 'uploads/default.jpg',
                    ];
                    $_SESSION['email'] = $admin['email'];

                    // Redirect to dashboard
                    header("Location: admindashboard.php");
                    exit();
                } else {
                    $error = "Invalid email or password!";
                }
            } 
            else {
                $error = "Invalid email or password!";
            }
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDM LINKUP</title>
    <link rel="icon" type="image/x-icon" href="images/cdmicon.png">
    <link rel="stylesheet" href="login1.css">

    <style>
    .container {
	background-color: #fff;
	border-radius: 10px;
  	box-shadow: 0 14px 28px rgba(0,0,0,0.25), 
			0 10px 10px rgba(0,0,0,0.22);
	position: relative;
	overflow: hidden;
	width: 768px;
	max-width: 100%;
	min-height: 750px;
}</style>
</head>
<body>
    <h2>CDM LINK Up</h2>
<div class="container" id="container">
    <?php if ($message): ?>
        <p style="color: green; text-align: center;"><?php echo $message; ?></p>
    <?php endif; ?>

    <div class="form-container sign-up-container">
        <form action="" method="POST" enctype="multipart/form-data">
            <h1>Create Account</h1>
            <div class="social-container">
                <img src="images/cdmicon.png" alt="">
            </div>
            <span>Welcome to CDM LinkUp</span>
            <div class="profile-picture-container">
                
                <div class="image-preview">
                    <img id="profileImagePreview" src="images/profile.jpg" alt="Profile Picture">
                </div>
                <label for="profilePicture">Select Profile Picture</label>
                <input type="file" name="profilePicture" id="profilePicture" accept="image/*" onchange="previewProfilePicture(event)">   
            </div>
            <input type="text" name="name" placeholder="First Name" required />
            <input type="text" name="lastName" placeholder="Last Name" required />
            <input type="text" name="studentNo" placeholder="Student Number" required />
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <input type="hidden" name="action" value="signup" />
            <button type="submit">Sign Up</button>
            <?php if ($message): ?>
    <div class="message">
        <p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>
        </form>
       
    </div>

    <div class="form-container sign-in-container">
        <form action="" method="POST">
            <h1>Sign in</h1>
            <div class="social-container">
                <img src="images/cdmicon.png" alt="">
            </div>
            <span>Welcome to CDM LinkUp</span>
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <input type="hidden" name="action" value="signin" />
            <?php if ($error): ?>
                <span style="color: red; font-size: 14px;"><?php echo $error; ?></span>
            <?php endif; ?>
            <a href="#">Forgot your password?</a>
            <button type="submit">Sign In</button>
            <?php if ($message): ?>
    <div class="message">
        <p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>
        </form>
    </div>

    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
            <img src="images/ZC9Z.gif" alt="Welcome Animation" style="width: 100px; height: auto;">
                <h1>Welcome CDM LINK Up!</h1>
                <p>What is the CDM LINK Up? CDM Link Up is a web application where you can see all information about CDM in one place, like events, updates, and more.</p>
                <button class="ghost" id="signIn">Sign In</button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1>Hello, Users!</h1>
                <img src="images/imageanim.gif" alt="Welcome Animation" style="width: 200px; height: auto; margin: 20px 0;">
                <p>“Stop wanting to know what you want to do with your life and start exploring what you could do with your life.”</p>
                <button class="ghost" id="signUp">Sign Up</button>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>
        Created <i class="fa fa-heart"></i> by ICS Student IT
        <a> @Colegio de Montalban</a>
        Visit Colegio de Montalban official Facebook page
        <a target="_blank" href="https://www.facebook.com/official.colegiodemontalban">click here!</a>.
    </p>
</footer>

<script>
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const container = document.getElementById('container');
    
    signUpButton.addEventListener('click', () => {
        container.classList.add("right-panel-active");
    });
    
    signInButton.addEventListener('click', () => {
        container.classList.remove("right-panel-active");
    });

    function previewProfilePicture(event) {
        const reader = new FileReader();
        reader.onload = function () {
            const preview = document.getElementById('profileImagePreview');
            preview.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    

    
</script>
</body>
</html>
