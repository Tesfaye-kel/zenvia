<?php
session_start();
require_once 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $first_name, $last_name);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Redirecting to login...";
                header("Refresh: 2; URL=login.php");
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="author" content="TESFAYEK">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Zenvia | Registration</title>
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
        body {
  background: #111;
}

.zenvia-app {
  font-size: 60px;
  font-weight: 800;
  font-family: 'Poppins', sans-serif;
  text-transform: lowercase;

  /* letters close together */
  letter-spacing: -2px;

  color: green;
  text-align:center;
  margin-bottom:30px;

  /* distortion effect */
  transform: skew(-8deg);
  display: inline-block;

  /* modern shadow */
  text-shadow: 
    2px 2px 0 #ff4d4d,
    4px 4px 10px rgba(0,0,0,0.5);
}

/* optional hover animation */
.zenvia-app:hover {
  transform: skew(0deg) scale(1.05);
  transition: 0.3s ease;
}       .app-container {
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
            margin-bottom: 15px;
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

        .form-row {
            display: flex;
            gap: 10px;
        }
        
        .form-row .input-group {
            flex: 1;
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
        
        .message.success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #7aff9a;
        }

        /* Responsive for mobile */
        @media (max-width: 480px) {
            .app-container {
                width: 90%;
            }
            .form-side {
                padding: 30px;
            }
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<div class="app-container">
    <div class="form-side">
   <h1 class="zenvia-app">zenvia</h1>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" id="register-form">
            <div class="form-row">
                <div class="input-group">
                    <input type="text" name="first_name" placeholder="First Name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    <span class="input-icon">👤</span>
                </div>
                <div class="input-group">
                    <input type="text" name="last_name" placeholder="Last Name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    <span class="input-icon">👤</span>
                </div>
            </div>
            
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                
            </div>
            
            <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <span class="input-icon">📧</span>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Password (min 6 chars)" required>
                <span class="input-icon">🔒</span>
            </div>
            
            <div class="input-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <span class="input-icon">🔐</span>
            </div>
            
            <button type="submit" class="btn">Get Started</button>
        </form>
        
        <div class="footer-link">
            Already a member? <a href="login.php">Log In</a>
        </div>
    </div>
</div>

</body>
</html>
