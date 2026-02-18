<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle like/unlike action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $post_id = $_POST['post_id'];
    
    if ($_POST['action'] == 'like') {
        // Check if already liked
        $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Add like
            $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $post_id);
            
            if ($stmt->execute()) {
                // Create notification
                $stmt_notif = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
                $stmt_notif->bind_param("i", $post_id);
                $stmt_notif->execute();
                $post_result = $stmt_notif->get_result();
                $post_owner = $stmt_notif->fetch_assoc();
                $stmt_notif->close();
                
                if ($post_owner && $post_owner['user_id'] != $user_id) {
                    $notif_message = "liked your post";
                    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, from_user_id, post_id, message) VALUES (?, 'like', ?, ?, ?)");
                    $stmt_notif->bind_param("iiis", $post_owner['user_id'], $user_id, $post_id, $notif_message);
                    $stmt_notif->execute();
                    $stmt_notif->close();
                }
                
                echo json_encode(['success' => true, 'message' => 'Liked!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to like']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Already liked']);
        }
        $stmt->close();
        
    } elseif ($_POST['action'] == 'unlike') {
        // Remove like
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $user_id, $post_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Unliked!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unlike']);
        }
        $stmt->close();
    }
    exit();
}

// Get likes count for a post
if (isset($_GET['post_id']) && isset($_GET['action']) && $_GET['action'] == 'count') {
    $post_id = $_GET['post_id'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'count' => $row['like_count']]);
    exit();
}

// Check if user has liked a post
if (isset($_GET['post_id']) && isset($_GET['action']) && $_GET['action'] == 'check') {
    $post_id = $_GET['post_id'];
    
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $liked = $result->num_rows > 0;
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'liked' => $liked]);
    exit();
}
?>
