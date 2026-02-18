<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_results = [];
$search_query = '';

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = trim($_GET['q']);
    $stmt = $conn->prepare("SELECT id, username, first_name, last_name, profile_pic, bio FROM users WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?) AND id != ?");
    $search_term = "%$search_query%";
    $stmt->bind_param("sssi", $search_term, $search_term, $search_term, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $search_results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Zenvia</title>
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
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .search-box form {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
        }
        
        .search-box button {
            padding: 12px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .search-results {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .search-results h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .result-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-item:hover {
            background: #f9f9f9;
        }
        
        .result-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .result-info {
            flex: 1;
        }
        
        .result-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        
        .result-username {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .result-bio {
            font-size: 13px;
            color: #888;
        }
        
        .btn-view {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
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
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="q" placeholder="Search for people..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        
        <div class="search-results">
            <h2>
                <?php if ($search_query): ?>
                    Search Results for "<?php echo htmlspecialchars($search_query); ?>"
                <?php else: ?>
                    Search for People
                <?php endif; ?>
            </h2>
            
            <?php if (!empty($search_results)): ?>
                <?php foreach ($search_results as $user): ?>
                    <div class="result-item">
                        <img src="images/profile_pics/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="result-avatar">
                        <div class="result-info">
                            <div class="result-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            <div class="result-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                            <?php if (!empty($user['bio'])): ?>
                                <div class="result-bio"><?php echo htmlspecialchars($user['bio']); ?></div>
                            <?php endif; ?>
                        </div>
                        <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn-view">View Profile</a>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($search_query): ?>
                <div class="no-results">
                    <p>No users found matching "<?php echo htmlspecialchars($search_query); ?>"</p>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <p>Enter a name or username to search for people</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
