<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Zenvia'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="icon" href="images/logo.png" type="image/png">
    
    <!-- Theme initialization script - runs before page renders to prevent flash -->
    <script>
        // Check localStorage for saved theme preference
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
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
                    <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                        <!-- Moon icon (shown in light mode) -->
                        <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <!-- Sun icon (shown in dark mode) -->
                        <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                    <a href="logout.php">Logout</a>
                </nav>
            <?php else: ?>
                <nav class="header-nav">
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                    <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                        <!-- Moon icon (shown in light mode) -->
                        <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <!-- Sun icon (shown in dark mode) -->
                        <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                </nav>
            <?php endif; ?>
        </div>
    </header>
