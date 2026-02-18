<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Zenvia'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="icon" href="images/logo.png" type="image/png">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">Zenvia</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="header-search">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" placeholder="Search Zenvia...">
                    </form>
                </div>
                
                <nav class="header-nav">
                    <a href="index.php">Home</a>
                    <a href="profile.php">Profile</a>
                    <a href="friends.php">Friends</a>
                    <a href="logout.php">Logout</a>
                </nav>
            <?php else: ?>
                <nav class="header-nav">
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>
