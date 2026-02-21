<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $bio = trim($_POST['bio']);
    $location = trim($_POST['location']);
    $website = trim($_POST['website']);
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $relationship_status = $_POST['relationship_status'];
    
    // Handle profile picture upload
    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        // Use absolute path for the upload directory
        $target_dir = dirname(__DIR__) . "/images/profile_pics/";
        
        // Create directory if it doesn't exist
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                $error = "Failed to create upload folder.";
            }
        }
        
        $file_name = time() . '_' . basename($_FILES['profile_pic']['name']);
        $target_file = $target_dir . $file_name;
        
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Use copy() instead of move_uploaded_file() for local development (XAMPP)
            if (copy($_FILES['profile_pic']['tmp_name'], $target_file)) {
                $profile_pic = $file_name;
            } else {
                // Debug: Show more details about the error
                $error = "Upload failed. Temp: " . $_FILES['profile_pic']['tmp_name'] . " -> Target: " . $target_file;
            }
        } else {
            $error = "Invalid file type. Only JPG, JPEG, PNG and GIF are allowed.";
        }
    }
    
    // Handle cover picture upload
    $cover_pic = $user['cover_pic'];
    if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] == 0) {
        $target_dir = "images/profile_pics/";
        $file_name = 'cover_' . time() . '_' . basename($_FILES['cover_pic']['name']);
        $target_file = $target_dir . $file_name;
        
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['cover_pic']['tmp_name'], $target_file)) {
                $cover_pic = $file_name;
            }
        }
    }
    
    // Update user profile
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ?, location = ?, website = ?, birth_date = ?, gender = ?, relationship_status = ?, profile_pic = ?, cover_pic = ? WHERE id = ?");
    $stmt->bind_param("ssssssssssi", $first_name, $last_name, $bio, $location, $website, $birth_date, $gender, $relationship_status, $profile_pic, $cover_pic, $user_id);
    
    if ($stmt->execute()) {
        $success = "Profile updated successfully!";
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $error = "Failed to update profile. Please try again.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Zenvia</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="icon" href="images/logo.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .header {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo { font-size: 24px; font-weight: bold; color: #667eea; text-decoration: none; }
        
        .header-nav { display: flex; gap: 20px; }
        .header-nav a { color: #333; text-decoration: none; font-size: 14px; }
        
        .container {
            max-width: 800px;
            margin: 80px auto 20px;
            padding: 20px;
        }
        
        .edit-profile {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .edit-profile h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .current-photo {
            margin-bottom: 15px;
        }
        
        .current-photo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
        }
        
        .success-message {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .btn-save {
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-save:hover {
            background: #5568d3;
        }
        
        .btn-cancel {
            padding: 12px 30px;
            background: #e4e6eb;
            color: #333;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 10px;
            text-decoration: none;
        }
        
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">Zenvia</a>
            <nav class="header-nav">
                <a href="index.php">Home</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="edit-profile">
            <h1>Edit Profile</h1>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <h2>Profile Picture</h2>
                    <div class="current-photo">
                        <img src="images/profile_pics/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Current Profile Picture">
                    </div>
                    <div class="form-group">
                        <label for="profile_pic">Upload New Profile Picture</label>
                        <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Basic Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" placeholder="Tell us about yourself"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" placeholder="City, Country" value="<?php echo htmlspecialchars($user['location']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" placeholder="https://yourwebsite.com" value="<?php echo htmlspecialchars($user['website']); ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Personal Details</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="birth_date">Birth Date</label>
                            <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select</option>
                                <option value="male" <?php echo $user['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo $user['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo $user['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="relationship_status">Relationship Status</label>
                            <select id="relationship_status" name="relationship_status">
                                <option value="">Select</option>
                                <option value="single" <?php echo $user['relationship_status'] == 'single' ? 'selected' : ''; ?>>Single</option>
                                <option value="in_relationship" <?php echo $user['relationship_status'] == 'in_relationship' ? 'selected' : ''; ?>>In a Relationship</option>
                                <option value="married" <?php echo $user['relationship_status'] == 'married' ? 'selected' : ''; ?>>Married</option>
                                <option value="divorced" <?php echo $user['relationship_status'] == 'divorced' ? 'selected' : ''; ?>>Divorced</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">Save Changes</button>
                <a href="profile.php" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
