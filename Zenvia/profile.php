<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get profile user_id from URL
$profile_id = isset($_GET['id']) ? $_GET['id'] : $user_id;

// Get profile user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$profile_result = $stmt->get_result();
$profile_user = $profile_result->fetch_assoc();
$stmt->close();

if (!$profile_user) {
    header("Location: index.php");
    exit();
}

// Get user's posts
$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$posts_result = $stmt->get_result();
$posts = $posts_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get friend count
$stmt = $conn->prepare("SELECT COUNT(*) as friend_count FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'");
$stmt->bind_param("ii", $profile_id, $profile_id);
$stmt->execute();
$friend_count_result = $stmt->get_result();
$friend_count = $friend_count_result->fetch_assoc()['friend_count'];
$stmt->close();

// Check if they are friends
$is_friend = false;
if ($profile_id != $user_id) {
    $stmt = $conn->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?) AND status = 'accepted'");
    $stmt->bind_param("iiii", $user_id, $profile_id, $profile_id, $user_id);
    $stmt->execute();
    $is_friend_result = $stmt->get_result();
    $is_friend = $is_friend_result->num_rows > 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?> - Zenvia</title>
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
        
        .profile-cover {
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }
        
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .profile-info {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-top: -100px;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            position: absolute;
            top: -75px;
            left: 30px;
        }
        
        .profile-details {
            padding-top: 80px;
        }
        
        .profile-name {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .profile-username {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .profile-bio {
            color: #333;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .profile-stats {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .profile-stat {
            text-align: center;
        }
        
        .profile-stat-count {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .profile-stat-label {
            font-size: 13px;
            color: #666;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background: #e4e6eb;
            color: #333;
            border: none;
        }
        
        .posts-section {
            margin-top: 20px;
        }
        
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
        
        .post-user-info h4 { font-size: 15px; color: #333; }
        .post-user-info span { font-size: 12px; color: #666; }
        
        .post-content { margin-bottom: 15px; line-height: 1.5; color: #333; }
        
        .post-image { width: 100%; border-radius: 8px; margin-bottom: 15px; }
        
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
    
    <div class="profile-cover"></div>
    
    <div class="profile-container">
        <div class="profile-info">
            <img src="images/profile_pics/<?php echo htmlspecialchars($profile_user['profile_pic']); ?>" alt="Profile" class="profile-avatar">
            
            <div class="profile-details">
                <h1 class="profile-name"><?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?></h1>
                <p class="profile-username">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
                
                <?php if (!empty($profile_user['bio'])): ?>
                    <p class="profile-bio"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                <?php endif; ?>
                
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-count"><?php echo count($posts); ?></div>
                        <div class="profile-stat-label">Posts</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-count"><?php echo $friend_count; ?></div>
                        <div class="profile-stat-label">Friends</div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <?php if ($profile_id == $user_id): ?>
                        <a href="edit-profile.php" class="btn btn-secondary">Edit Profile</a>
                    <?php elseif ($is_friend): ?>
                        <button class="btn btn-secondary">Friends</button>
                    <?php else: ?>
                        <button class="btn btn-primary" onclick="addFriend(<?php echo $profile_id; ?>)">Add Friend</button>
                    <?php endif; ?>
                    <a href="messages.php?id=<?php echo $profile_id; ?>" class="btn btn-primary">Message</a>
                </div>
            </div>
        </div>
        
        <div class="posts-section">
            <h2 style="margin-bottom: 20px; color: #333;">Posts</h2>
            
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="images/profile_pics/<?php echo htmlspecialchars($profile_user['profile_pic']); ?>" alt="Profile" class="post-avatar">
                        <div class="post-user-info">
                            <h4><?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?></h4>
                            <span>@<?php echo htmlspecialchars($profile_user['username']); ?> • <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($post['content'])): ?>
                        <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($post['image'])): ?>
                        <img src="images/post_images/<?php echo htmlspecialchars($post['image']); ?>" alt="Post image" class="post-image">
                    <?php endif; ?>
                    
                    <div class="post-actions">
                        <button class="post-action">❤️ Like</button>
                        <button class="post-action">💬 Comment</button>
                        <button class="post-action">🔗 Share</button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($posts)): ?>
                <div class="post">
                    <p style="text-align: center; color: #666;">No posts yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
