<?php
session_start();

// Include PDO database connection
require_once 'includes/db.php';

// Check if user is logged in (session-based)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch currently logged-in user's data using PDO with prepared statement
$stmt = $conn->prepare("SELECT username, email, profile_pic, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If user not found, redirect to login
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Zenvia</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7f9;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.06);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            border: 1px solid #e8e9eb;
        }

        .profile-header {
            background: linear-gradient(135deg, #14a800 0%, #0d6a00 100%);
            padding: 40px 30px 70px;
            text-align: center;
            position: relative;
        }

        .profile-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid #ffffff;
            background: #f5f7f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: -65px;
            font-size: 52px;
            color: #14a800;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-body {
            padding: 80px 30px 30px;
        }

        .profile-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-title h1 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .profile-title .user-id {
            color: #999;
            font-size: 14px;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-label {
            display: block;
            color: #667eea;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .info-value {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            color: #333;
            font-size: 16px;
        }

        .info-value.email {
            font-size: 14px;
        }

        .logout-btn {
            display: block;
            width: 100%;
            background: #ff4757;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            margin-top: 20px;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .logout-btn:hover {
            background: #ff6b7a;
            transform: translateY(-2px);
        }

        .home-link {
            display: block;
            text-align: center;
            color: #667eea;
            text-decoration: none;
            margin-top: 15px;
            font-size: 14px;
        }

        .home-link:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .profile-card {
                border-radius: 15px;
            }

            .profile-header {
                padding: 30px 20px 50px;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                bottom: -50px;
                font-size: 40px;
            }

            .profile-body {
                padding: 60px 20px 20px;
            }

            .profile-title h1 {
                font-size: 20px;
            }

            .info-value {
                padding: 12px;
                font-size: 14px;
            }

            .info-value.email {
                font-size: 13px;
            }

            .logout-btn {
                padding: 12px;
                font-size: 14px;
            }
        }

        @media (max-width: 320px) {
            .profile-avatar {
                width: 80px;
                height: 80px;
                bottom: -40px;
                font-size: 32px;
            }

            .profile-body {
                padding: 50px 15px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php 
                // Display profile picture or fallback to first letter of username
                $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : '';
                $upload_dir = dirname(__DIR__) . '/images/profile_pics/';
                if (!empty($profile_pic) && file_exists($upload_dir . $profile_pic)) {
                    echo '<img src="images/profile_pics/' . htmlspecialchars($profile_pic) . '" alt="Profile Picture" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">';
                } else {
                    echo strtoupper(htmlspecialchars($user['username'][0])); 
                }
                ?>
            </div>
        </div>
        
        <div class="profile-body">
            <div class="profile-title">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <span class="user-id">User ID: #<?php echo htmlspecialchars($user_id); ?></span>
            </div>

            <div class="info-group">
                <label class="info-label">Username</label>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['username']); ?>
                </div>
            </div>

            <div class="info-group">
                <label class="info-label">Email Address</label>
                <div class="info-value email">
                    <?php echo htmlspecialchars($user['email']); ?>
                </div>
            </div>

            <a href="logout.php" class="logout-btn">Logout</a>
            <a href="index.php" class="home-link">← Back to Home</a>
        </div>
    </div>
</body>
</html>
