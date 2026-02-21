<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$current_user = $user_result->fetch_assoc();
$stmt->close();

// Get all posts with user info
$posts_query = "SELECT posts.*, users.username, users.first_name, users.last_name, users.profile_pic 
                FROM posts 
                LEFT JOIN users ON posts.user_id = users.id 
                ORDER BY posts.created_at DESC";
$posts_result = $conn->query($posts_query);
$posts = $posts_result->fetch_all(MYSQLI_ASSOC);

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);
    $image = '';
    
    // Handle image upload
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $target_dir = dirname(__DIR__) . "/images/post_images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['post_image']['name']);
        $target_file = $target_dir . $file_name;
        
        if (copy($_FILES['post_image']['tmp_name'], $target_file)) {
            $image = $file_name;
        }
    }
    
    if (!empty($content) || !empty($image)) {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $content, $image);
        
        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Zenvia</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="icon" href="images/logo.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        
        /* Header Styles */
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
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }
        
        .header-search {
            flex: 1;
            max-width: 400px;
            margin: 0 20px;
        }
        
        .header-search input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .header-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .header-nav a {
            color: #333;
            text-decoration: none;
            font-size: 14px;
        }
        
        .header-nav a:hover {
            color: #667eea;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 80px auto 20px;
            padding: 20px;
            display: grid;
            grid-template-columns: 250px 1fr 300px;
            gap: 20px;
        }
        
        /* Sidebar */
        .sidebar-left {
            position: sticky;
            top: 80px;
            height: fit-content;
        }
        
        .sidebar-menu {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s;
        }
        
        .sidebar-menu a:hover {
            background: #f0f2f5;
        }
        
        /* Feed */
        .feed {
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Create Post */
        .create-post {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .create-post-header {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .create-post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .create-post input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .create-post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .create-post-options {
            display: flex;
            gap: 15px;
        }
        
        .create-post-options label {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-post {
            padding: 10px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-post:hover {
            background: #5568d3;
        }
        
        /* Post */
        .post {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .post-user-info h4 {
            font-size: 15px;
            color: #333;
        }
        
        .post-user-info span {
            font-size: 12px;
            color: #666;
        }
        
        .post-content {
            margin-bottom: 15px;
            line-height: 1.5;
            color: #333;
        }
        
        .post-image {
            width: 100%;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .post-actions {
            display: flex;
            gap: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .post-action {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            cursor: pointer;
            font-size: 14px;
            background: none;
            border: none;
        }
        
        .post-action:hover {
            color: #667eea;
        }
        
        /* Right Sidebar */
        .sidebar-right {
            position: sticky;
            top: 80px;
            height: fit-content;
        }
        
        .suggestions {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .suggestions h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }
        
        .suggestion-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .suggestion-info {
            flex: 1;
        }
        
        .suggestion-info h4 {
            font-size: 14px;
            color: #333;
        }
        
        .suggestion-info span {
            font-size: 12px;
            color: #666;
        }
        
        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar-left, .sidebar-right {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">Zenvia</a>
            
            <div class="header-search">
                <input type="text" placeholder="Search Zenvia...">
            </div>
            
            <nav class="header-nav">
                <a href="index.php">Home</a>
                <a href="profile.php">Profile</a>
                <a href="friends.php">Friends</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <!-- Main Container -->
    <div class="container">
        <!-- Left Sidebar -->
        <aside class="sidebar-left">
            <div class="sidebar-menu">
                <a href="index.php">🏠 Home</a>
                <a href="profile.php?id=<?php echo $user_id; ?>">👤 My Profile</a>
                <a href="friends.php">👥 Friends</a>
                <a href="search.php">🔍 Search</a>
                <a href="edit-profile.php">⚙️ Settings</a>
            </div>
        </aside>
        
        <!-- Feed -->
        <main class="feed">
            <!-- Create Post -->
            <div class="create-post">
                <form method="POST" enctype="multipart/form-data">
                    <div class="create-post-header">
                        <img src="images/profile_pics/<?php echo $current_user['profile_pic']; ?>" alt="Profile" class="create-post-avatar">
                        <input type="text" name="post_content" placeholder="What's on your mind, <?php echo htmlspecialchars($current_user['first_name']); ?>?" required>
                    </div>
                    <div class="create-post-actions">
                        <div class="create-post-options">
                            <label>
                                📷 Photo
                                <input type="file" name="post_image" accept="image/*" style="display: none;">
                            </label>
                        </div>
                        <button type="submit" class="btn-post">Post</button>
                    </div>
                </form>
            </div>
            
            <!-- Posts -->
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="images/profile_pics/<?php echo $post['profile_pic']; ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="post-avatar">
                        <div class="post-user-info">
                            <h4><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h4>
                            <span>@<?php echo htmlspecialchars($post['username']); ?> • <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($post['content'])): ?>
                        <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($post['image'])): ?>
                        <img src="images/post_images/<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-image">
                    <?php endif; ?>
                    
                    <div class="post-actions">
                        <button class="post-action" onclick="likePost(<?php echo $post['id']; ?>)">❤️ Like</button>
                        <button class="post-action" onclick="showCommentBox(<?php echo $post['id']; ?>)">💬 Comment</button>
                        <button class="post-action">🔗 Share</button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($posts)): ?>
                <div class="post">
                    <p style="text-align: center; color: #666;">No posts yet. Be the first to post!</p>
                </div>
            <?php endif; ?>
        </main>
        
        <!-- Right Sidebar -->
        <aside class="sidebar-right">
            <div class="suggestions">
                <h3>People You May Know</h3>
                <?php
                // Get users who are not friends yet
                $suggestions_query = "SELECT * FROM users WHERE id != $user_id LIMIT 5";
                $suggestions_result = $conn->query($suggestions_query);
                $suggestions = $suggestions_result->fetch_all(MYSQLI_ASSOC);
                
                foreach ($suggestions as $suggestion): ?>
                    <div class="suggestion-item">
                        <img src="images/profile_pics/<?php echo $suggestion['profile_pic']; ?>" alt="<?php echo htmlspecialchars($suggestion['username']); ?>" class="suggestion-avatar">
                        <div class="suggestion-info">
                            <h4><?php echo htmlspecialchars($suggestion['first_name'] . ' ' . $suggestion['last_name']); ?></h4>
                            <span>@<?php echo htmlspecialchars($suggestion['username']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
