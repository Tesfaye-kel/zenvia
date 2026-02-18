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

// Handle comment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_comment') {
    header('Content-Type: application/json');
    
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $post_id, $content);
    
    if ($stmt->execute()) {
        $comment_id = $stmt->insert_id;
        
        // Create notification
        $stmt_notif = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt_notif->bind_param("i", $post_id);
        $stmt_notif->execute();
        $post_result = $stmt_notif->get_result();
        $post_owner = $stmt_notif->fetch_assoc();
        $stmt_notif->close();
        
        if ($post_owner && $post_owner['user_id'] != $user_id) {
            $notif_message = "commented on your post";
            $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, from_user_id, post_id, message) VALUES (?, 'comment', ?, ?, ?)");
            $stmt_notif->bind_param("iiis", $post_owner['user_id'], $user_id, $post_id, $notif_message);
            $stmt_notif->execute();
            $stmt_notif->close();
        }
        
        // Get the newly created comment with user info
        $stmt = $conn->prepare("SELECT comments.*, users.username, users.first_name, users.last_name, users.profile_pic 
                                FROM comments 
                                LEFT JOIN users ON comments.user_id = users.id 
                                WHERE comments.id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $comment = $result->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Comment added!',
            'comment' => $comment
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
    exit();
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_comment') {
    header('Content-Type: application/json');
    
    $comment_id = $_POST['comment_id'];
    
    // Check if the comment belongs to the user
    $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();
    $stmt->close();
    
    if ($comment && $comment['user_id'] == $user_id) {
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Comment deleted!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'You can only delete your own comments']);
    }
    exit();
}

// Get comments for a post
if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];
    
    $stmt = $conn->prepare("SELECT comments.*, users.username, users.first_name, users.last_name, users.profile_pic 
                           FROM comments 
                           LEFT JOIN users ON comments.user_id = users.id 
                           WHERE comments.post_id = ? 
                           ORDER BY comments.created_at ASC");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'comments' => $comments]);
    exit();
}

// Handle regular comment submission (non-AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $post_id, $content);
        
        if ($stmt->execute()) {
            // Create notification
            $stmt_notif = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt_notif->bind_param("i", $post_id);
            $stmt_notif->execute();
            $post_result = $stmt_notif->get_result();
            $post_owner = $stmt_notif->fetch_assoc();
            $stmt_notif->close();
            
            if ($post_owner && $post_owner['user_id'] != $user_id) {
                $notif_message = "commented on your post";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, type, from_user_id, post_id, message) VALUES (?, 'comment', ?, ?, ?)");
                $stmt_notif->bind_param("iiis", $post_owner['user_id'], $user_id, $post_id, $notif_message);
                $stmt_notif->execute();
                $stmt_notif->close();
            }
        }
        $stmt->close();
    }
    
    // Redirect back to the post or index
    if (isset($_POST['redirect'])) {
        header("Location: " . $_POST['redirect']);
    } else {
        header("Location: post.php?id=" . $post_id);
    }
    exit();
}
?>
