<?php
session_start();
require_once 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT id, username, email, password, first_name, last_name FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                
                // Update last login
                $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
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
    <title>Login - Zenvia</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="icon" href="images/logo.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        
        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #1a1a2e;
            position: relative;
        }

        /* Main Container for the Form */
        .app-container {
            width: 400px;
            background: #2d2d44;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: relative;
            z-index: 1;
        }

        /* The Form */
        .form-side {
            padding: 40px;
            background: #2d2d44;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-side h2 {
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 1.8rem;
            color: #fff;
            text-align: center;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group input {
            width: 100%;
            padding: 14px 15px;
            padding-left: 40px;
            border-radius: 8px;
            border: 1px solid #444;
            background: #1a1a2e;
            color: #fff;
            outline: none;
            font-size: 0.95rem;
        }
        
        .input-group input::placeholder {
            color: #888;
        }
        
        .input-group input:focus {
            border-color: #666;
        }
        
        .input-group .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 1rem;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #4a4a6a;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: #5a5a7a;
        }

        .footer-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #888;
        }
        
        .footer-link a {
            color: #aaa;
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer-link a:hover {
            color: #fff;
        }

        /* Error/Success Messages */
        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .message.error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #ff6b7a;
        }

        /* Responsive for mobile */
        @media (max-width: 480px) {
            .app-container {
                width: 90%;
            }
            .form-side {
                padding: 30px;
            }
        }
    </style>
</head>
<body>

<div class="app-container">
    <div class="form-side">
        <h2>Welcome Back</h2>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" id="login-form">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username or Email" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <span class="input-icon">👤</span>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
                <span class="input-icon">🔒</span>
            </div>
            
            <button type="submit" class="btn">Log In</button>
        </form>
        
        <div class="footer-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

</body>
</html>
