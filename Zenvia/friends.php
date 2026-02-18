<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle friend request actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $friend_id = $_POST['friend_id'];
    
    if ($_POST['action'] == 'accept') {
        $stmt = $conn->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = ? AND friend_id = ?");
        $stmt->bind_param("ii", $friend_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] == 'reject') {
        $stmt = $conn->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ?");
        $stmt->bind_param("ii", $friend_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] == 'unfriend') {
        $stmt = $conn->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['action'] == 'add_friend') {
        $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $user_id, $friend_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Get friends list
$friends_query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_pic, u.bio, f.status, f.created_at 
                 FROM friends f 
                 JOIN users u ON (f.friend_id = u.id AND f.user_id = $user_id) OR (f.user_id = u.id AND f.friend_id = $user_id)
                 WHERE f.status = 'accepted'";
$friends_result = $conn->query($friends_query);
$friends = $friends_result->fetch_all(MYSQLI_ASSOC);

// Get pending friend requests (where current user is the receiver)
$requests_query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_pic, f.id as friend_request_id, f.created_at 
                   FROM friends f 
                   JOIN users u ON f.user_id = u.id 
                   WHERE f.friend_id = $user_id AND f.status = 'pending'";
$requests_result = $conn->query($requests_query);
$friend_requests = $requests_result->fetch_all(MYSQLI_ASSOC);

// Get suggested friends
$suggestions_query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_pic, u.bio 
                      FROM users u 
                      WHERE u.id != $user_id 
                      AND u.id NOT IN (
                          SELECT friend_id FROM friends WHERE user_id = $user_id AND status = 'accepted'
                          UNION
                          SELECT user_id FROM friends WHERE friend_id = $user_id AND status = 'accepted'
                          UNION
                          SELECT friend_id FROM friends WHERE user_id = $user_id AND status = 'pending'
                      )
                      LIMIT 5";
$suggestions_result = $conn->query($suggestions_query);
$suggestions = $suggestions_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends - Zenvia</title>
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
            max-width: 900px;
            margin: 80px auto 20px;
            padding: 20px;
        }
        
        .friends-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .friends-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .friend-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        
        .friend-card:last-child {
            border-bottom: none;
        }
        
        .friend-card:hover {
            background: #f9f9f9;
        }
        
        .friend-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .friend-info {
            flex: 1;
        }
        
        .friend-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        
        .friend-name a {
            color: #333;
            text-decoration: none;
        }
        
        .friend-name a:hover {
            color: #667eea;
        }
        
        .friend-username {
            font-size: 14px;
            color: #666;
        }
        
        .friend-bio {
            font-size: 13px;
            color: #888;
            margin-top: 3px;
        }
        
        .friend-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-secondary {
            background: #e4e6eb;
            color: #333;
        }
        
        .btn-danger {
            background: #ffebee;
            color: #c62828;
        }
        
        .btn-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .request-count {
            background: #ff4757;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .tab {
            padding: 10px 20px;
            color: #666;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
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
                <a href="search.php">Search</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <!-- Friend Requests -->
        <?php if (!empty($friend_requests)): ?>
            <div class="friends-section">
                <h2>
                    Friend Requests 
                    <span class="request-count"><?php echo count($friend_requests); ?></span>
                </h2>
                
                <?php foreach ($friend_requests as $request): ?>
                    <div class="friend-card">
                        <img src="images/profile_pics/<?php echo htmlspecialchars($request['profile_pic']); ?>" alt="<?php echo htmlspecialchars($request['username']); ?>" class="friend-avatar">
                        <div class="friend-info">
                            <div class="friend-name">
                                <a href="profile.php?id=<?php echo $request['id']; ?>"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></a>
                            </div>
                            <div class="friend-username">@<?php echo htmlspecialchars($request['username']); ?></div>
                        </div>
                        <div class="friend-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="friend_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" name="action" value="accept" class="btn btn-success">Accept</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="friend_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-danger">Decline</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Friends List -->
        <div class="friends-section">
            <h2>Friends (<?php echo count($friends); ?>)</h2>
            
            <?php if (!empty($friends)): ?>
                <?php foreach ($friends as $friend): ?>
                    <div class="friend-card">
                        <img src="images/profile_pics/<?php echo htmlspecialchars($friend['profile_pic']); ?>" alt="<?php echo htmlspecialchars($friend['username']); ?>" class="friend-avatar">
                        <div class="friend-info">
                            <div class="friend-name">
                                <a href="profile.php?id=<?php echo $friend['id']; ?>"><?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?></a>
                            </div>
                            <div class="friend-username">@<?php echo htmlspecialchars($friend['username']); ?></div>
                            <?php if (!empty($friend['bio'])): ?>
                                <div class="friend-bio"><?php echo htmlspecialchars($friend['bio']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="friend-actions">
                            <a href="profile.php?id=<?php echo $friend['id']; ?>" class="btn btn-primary">View Profile</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="friend_id" value="<?php echo $friend['id']; ?>">
                                <button type="submit" name="action" value="unfriend" class="btn btn-danger">Unfriend</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>You don't have any friends yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Suggestions -->
        <?php if (!empty($suggestions)): ?>
            <div class="friends-section">
                <h2>People You May Know</h2>
                
                <?php foreach ($suggestions as $suggestion): ?>
                    <div class="friend-card">
                        <img src="images/profile_pics/<?php echo htmlspecialchars($suggestion['profile_pic']); ?>" alt="<?php echo htmlspecialchars($suggestion['username']); ?>" class="friend-avatar">
                        <div class="friend-info">
                            <div class="friend-name">
                                <a href="profile.php?id=<?php echo $suggestion['id']; ?>"><?php echo htmlspecialchars($suggestion['first_name'] . ' ' . $suggestion['last_name']); ?></a>
                            </div>
                            <div class="friend-username">@<?php echo htmlspecialchars($suggestion['username']); ?></div>
                            <?php if (!empty($suggestion['bio'])): ?>
                                <div class="friend-bio"><?php echo htmlspecialchars($suggestion['bio']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="friend-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="friend_id" value="<?php echo $suggestion['id']; ?>">
                                <button type="submit" name="action" value="add_friend" class="btn btn-primary">Add Friend</button>
                            </form>
                            <a href="profile.php?id=<?php echo $suggestion['id']; ?>" class="btn btn-secondary">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
