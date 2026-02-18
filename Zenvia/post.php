<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle post creation via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_post') {
    header('Content-Type: application/json');
    
    $content = trim($_POST['content']);
    $image = '';
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "images/post_images/";
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $file_name;
        
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $file_name;
            }
        }
    }
    
    if (!empty($content) || !empty($image)) {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $content, $image);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Post created successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create post']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Post cannot be empty']);
    }
    exit();
}

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_post') {
    header('Content-Type: application/json');
    
    $post_id = $_POST['post_id'];
    
    // Check if the post belongs to the user
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();
    
    if ($post && $post['user_id'] == $user_id) {
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Post deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own posts']);
    }
    exit();
}

// Get single post
if (isset($_GET['id'])) {
    $post_id = $_GET['id'];
    
    $stmt = $conn->prepare("SELECT posts.*, users.username, users.first_name, users.last_name, users.profile_pic 
                           FROM posts 
                           LEFT JOIN users ON posts.user_id = users.id 
                           WHERE posts.id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();
    
    if (!$post) {
        header("Location: index.php");
        exit();
    }
    
    // Get comments for this post
    $stmt = $conn->prepare("SELECT comments.*, users.username, users.first_name, users.last_name, users.profile_pic 
                           FROM comments 
                           LEFT JOIN users ON comments.user_id = users.id 
                           WHERE comments.post_id = ? 
                           ORDER BY comments.created_at ASC");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post - Zenvia</title>
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
            max-width: 700px;
            margin: 80px auto 20px;
            padding: 20px;
        }
        
        .post-detail {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .post-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .post-user-info h3 {
            font-size: 16px;
            color: #333;
        }
        
        .post-user-info a {
            color: #333;
            text-decoration: none;
        }
        
        .post-user-info span {
            font-size: 13px;
            color: #666;
        }
        
        .post-content {
            margin-bottom: 15px;
            line-height: 1.6;
            color: #333;
            font-size: 15px;
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
            margin-bottom: 20px;
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
        
        .comments-section {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .comments-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .comment-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .comment-form input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
        }
        
        .comment-form button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .comment {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .comment-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .comment-content {
            flex: 1;
            background: #f0f2f5;
            padding: 10px 15px;
            border-radius: 15px;
        }
        
        .comment-user {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .comment-text {
            color: #333;
            font-size: 14px;
        }
        
        .comment-time {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
                <a href="friends.php">Friends</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="post-detail">
            <div class="post-header">
                <a href="profile.php?id=<?php echo $post['user_id']; ?>">
                    <img src="images/profile_pics/<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="Profile" class="post-avatar">
                </a>
                <div class="post-user-info">
                    <h3>
                        <a href="profile.php?id=<?php echo $post['user_id']; ?>">
                            <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>
                        </a>
                    </h3>
                    <span>@<?php echo htmlspecialchars($post['username']); ?> • <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></span>
                </div>
            </div>
            
            <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            
            <?php if (!empty($post['image'])): ?>
                <img src="images/post_images/<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-image">
            <?php endif; ?>
            
            <div class="post-actions">
                <button class="post-action" onclick="likePost(<?php echo $post['id']; ?>)">❤️ Like</button>
                <button class="post-action">💬 Comment</button>
                <button class="post-action">🔗 Share</button>
            </div>
            
            <div class="comments-section">
                <h3>Comments (<?php echo count($comments); ?>)</h3>
                
                <form class="comment-form" method="POST" action="comment.php">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <input type="text" name="content" placeholder="Write a comment..." required>
                    <button type="submit">Post</button>
                </form>
                
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <a href="profile.php?id=<?php echo $comment['user_id']; ?>">
                            <img src="images/profile_pics/<?php echo htmlspecialchars($comment['profile_pic']); ?>" alt="Profile" class="comment-avatar">
                        </a>
                        <div class="comment-content">
                            <div class="comment-user"><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></div>
                            <p class="comment-text"><?php echo htmlspecialchars($comment['content']); ?></p>
                            <div class="comment-time"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($comments)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No comments yet. Be the first to comment!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
