<?php
session_start();

// Image categories and their display labels
$captcha_categories = [
    'car' => ['🚗', 'Car'],
    'animal' => ['🐕', 'Dog'],
    'food' => ['🍕', 'Pizza'],
    'flower' => ['🌸', 'Flower'],
    'house' => ['🏠', 'House'],
    'phone' => ['📱', 'Phone'],
    'book' => ['📚', 'Book'],
    'star' => ['⭐', 'Star'],
    'heart' => ['❤️', 'Heart'],
    'sun' => ['☀️', 'Sun'],
    'tree' => ['🌳', 'Tree'],
    'bird' => ['🐦', 'Bird']
];

// If this is an AJAX request for getting the grid
if (isset($_GET['action']) && $_GET['action'] === 'get_grid') {
    header('Content-Type: application/json');
    
    // Select a random target category
    $categories = array_keys($captcha_categories);
    $target_category = $categories[array_rand($categories)];
    
    // Store the target in session
    $_SESSION['captcha_target'] = $target_category;
    $_SESSION['captcha_attempts'] = 0;
    
    // Get 8 other categories for distractors (not the target)
    $other_categories = array_diff($categories, [$target_category]);
    shuffle($other_categories);
    $distractors = array_slice($other_categories, 0, 8);
    
    // Combine target with distractors and shuffle
    $grid_items = array_merge([$target_category], $distractors);
    shuffle($grid_items);
    
    // Ensure we have exactly 9 items (3x3 grid)
    $grid_items = array_slice($grid_items, 0, 9);
    
    echo json_encode([
        'target' => $target_category,
        'target_emoji' => $captcha_categories[$target_category][0],
        'target_label' => $captcha_categories[$target_category][1],
        'grid' => $grid_items,
        'emojis' => $captcha_categories
    ]);
    exit();
}

// If this is a validation request
if (isset($_GET['action']) && $_GET['action'] === 'validate') {
    header('Content-Type: application/json');
    
    $selected = $_POST['selected'] ?? '';
    $target = $_SESSION['captcha_target'] ?? '';
    
    $_SESSION['captcha_attempts'] = ($_SESSION['captcha_attempts'] ?? 0) + 1;
    
    if ($selected === $target && $_SESSION['captcha_attempts'] <= 3) {
        $_SESSION['captcha_validated'] = true;
        echo json_encode(['success' => true, 'message' => 'Captcha validated!']);
    } else {
        $_SESSION['captcha_validated'] = false;
        echo json_encode(['success' => false, 'message' => 'Incorrect selection. Please try again.']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image CAPTCHA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .captcha-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
        }
        
        .captcha-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .captcha-header h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .captcha-instruction {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .target-emoji {
            font-size: 28px;
            display: inline-block;
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .captcha-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 20px 0;
            perspective: 1000px;
        }
        
        .captcha-item {
            aspect-ratio: 1;
            background: linear-gradient(145deg, #f0f0f0, #e0e0e0);
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
        }
        
        .captcha-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
            transition: left 0.5s;
            z-index: 1;
        }
        
        .captcha-item:hover::before {
            left: 100%;
        }
        
        .captcha-item:hover {
            transform: scale(1.15) rotateY(10deg) rotateX(10deg) translateZ(20px);
            box-shadow: 
                0 15px 40px rgba(102, 126, 234, 0.5),
                0 0 20px rgba(102, 126, 234, 0.3),
                inset 0 0 20px rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            z-index: 10;
        }
        
        .captcha-item .emoji {
            font-size: 40px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform-style: preserve-3d;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .captcha-item:hover .emoji {
            transform: scale(1.4) translateZ(30px) rotate(-5deg);
            filter: drop-shadow(0 8px 16px rgba(102, 126, 234, 0.4));
        }
        
        .captcha-item::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: 0;
            width: 100%;
            height: 50%;
            background: radial-gradient(ellipse at center, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
            transition: all 0.4s;
            opacity: 0;
        }
        
        .captcha-item:hover::after {
            bottom: -30%;
            opacity: 1;
        }
        
        .captcha-item.selected {
            border-color: #667eea;
            background: linear-gradient(145deg, #e8eaf6, #c5cae9);
            transform: scale(0.95) rotateY(5deg);
        }
        
        .captcha-item.selected .emoji {
            transform: scale(1.1);
        }
        
        .captcha-item.correct {
            animation: correctFlip 0.6s ease;
            border-color: #4caf50;
            background: linear-gradient(145deg, #e8f5e9, #c8e6c9);
            transform: scale(1.1);
        }
        
        .captcha-item.correct .emoji {
            animation: emojiCelebrate 0.6s ease;
        }
        
        .captcha-item.wrong {
            animation: wrongFlip 0.5s ease;
            border-color: #f44336;
            background: linear-gradient(145deg, #ffebee, #ffcdd2);
        }
        
        @keyframes correctFlip {
            0% { transform: scale(1) rotateY(0); }
            30% { transform: scale(1.2) rotateY(-15deg); }
            60% { transform: scale(1.15) rotateY(10deg); }
            100% { transform: scale(1.1) rotateY(0); }
        }
        
        @keyframes emojiCelebrate {
            0% { transform: scale(1); }
            25% { transform: scale(1.5) rotate(-15deg); }
            50% { transform: scale(1.3) rotate(15deg); }
            75% { transform: scale(1.4) rotate(-10deg); }
            100% { transform: scale(1.2); }
        }
        
        @keyframes wrongFlip {
            0%, 100% { transform: translateX(0) rotateY(0); }
            20% { transform: translateX(-15px) rotateY(-20deg); }
            40% { transform: translateX(10px) rotateY(15deg); }
            60% { transform: translateX(-10px) rotateY(-10deg); }
            80% { transform: translateX(5px) rotateY(5deg); }
        }
        
        .captcha-item:active {
            transform: scale(0.9);
        }
        
        .captcha-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .message {
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 14px;
            display: none;
        }
        
        .message.success {
            display: block;
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .message.error {
            display: block;
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .attempts {
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="captcha-container">
        <div class="captcha-header">
            <h2>🔒 Security Check</h2>
            <p class="captcha-instruction">
                Click on the <span class="target-emoji" id="targetEmoji">❓</span> 
                <strong id="targetLabel">loading...</strong>
            </p>
        </div>
        
        <div class="captcha-grid" id="captchaGrid">
            <!-- Grid items will be generated here -->
        </div>
        
        <div class="captcha-footer">
            <span class="attempts">Attempts: <span id="attempts">0</span>/3</span>
            <button class="refresh-btn" onclick="loadCaptcha()">
                🔄 New Challenge
            </button>
        </div>
        
        <div class="message" id="message"></div>
    </div>

    <script>
        let currentTarget = '';
        
        async function loadCaptcha() {
            try {
                const response = await fetch('image-captcha.php?action=get_grid');
                const data = await response.json();
                
                currentTarget = data.target;
                document.getElementById('targetEmoji').textContent = data.target_emoji;
                document.getElementById('targetLabel').textContent = data.target_label;
                document.getElementById('attempts').textContent = '0';
                document.getElementById('message').className = 'message';
                document.getElementById('message').textContent = '';
                
                const grid = document.getElementById('captchaGrid');
                grid.innerHTML = '';
                
                data.grid.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'captcha-item';
                    div.dataset.category = item;
                    div.innerHTML = `<span class="emoji">${data.emojis[item][0]}</span>`;
                    div.style.animationDelay = `${index * 0.1}s`;
                    div.onclick = () => selectItem(div, item);
                    grid.appendChild(div);
                });
            } catch (error) {
                console.error('Error loading captcha:', error);
            }
        }
        
        async function selectItem(element, category) {
            // Remove previous selections
            document.querySelectorAll('.captcha-item').forEach(el => {
                el.classList.remove('selected');
            });
            
            element.classList.add('selected');
            
            const formData = new FormData();
            formData.append('selected', category);
            
            try {
                const response = await fetch('image-captcha.php?action=validate', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                const attemptsEl = document.getElementById('attempts');
                const messageEl = document.getElementById('message');
                
                if (data.success) {
                    element.classList.add('correct');
                    messageEl.textContent = '✅ Verification successful!';
                    messageEl.className = 'message success';
                    
                    // Send message to parent window (iframe communication)
                    parent.postMessage({ captcha_valid: true }, '*');
                    
                    // Close the popup after a short delay
                    setTimeout(() => {
                        // Trigger closeCaptcha in parent
                        parent.closeCaptcha();
                    }, 1000);
                } else {
                    element.classList.add('wrong');
                    let attempts = parseInt(attemptsEl.textContent) + 1;
                    attemptsEl.textContent = attempts;
                    
                    messageEl.textContent = `❌ ${data.message}`;
                    messageEl.className = 'message error';
                    
                    if (attempts >= 3) {
                        setTimeout(() => {
                            loadCaptcha();
                        }, 1000);
                    }
                }
            } catch (error) {
                console.error('Error validating captcha:', error);
            }
        }
        
        // Load captcha on page load
        loadCaptcha();
    </script>
</body>
</html>
