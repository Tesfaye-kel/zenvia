<?php
/**
 * Zenvia Social Network - Utility Functions
 */

/**
 * Get user by ID
 */
function getUserById($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Get user by username or email
 */
function getUserByCredentials($conn, $username) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Get user's friends
 */
function getFriends($conn, $user_id) {
    $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_pic, u.bio, f.status, f.created_at 
              FROM friends f 
              JOIN users u ON (f.friend_id = u.id AND f.user_id = ?) OR (f.user_id = u.id AND f.friend_id = ?)
              WHERE f.status = 'accepted'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $friends = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $friends;
}

/**
 * Get friend requests
 */
function getFriendRequests($conn, $user_id) {
    $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_pic, f.id as friend_request_id, f.created_at 
              FROM friends f 
              JOIN users u ON f.user_id = u.id 
              WHERE f.friend_id = ? AND f.status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $requests;
}

/**
 * Check if two users are friends
 */
function isFriend($conn, $user_id, $other_user_id) {
    $stmt = $conn->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?) AND status = 'accepted'");
    $stmt->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_friend = $result->num_rows > 0;
    $stmt->close();
    return $is_friend;
}

/**
 * Get user's posts
 */
function getUserPosts($conn, $user_id, $limit = 50) {
    $stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $posts;
}

/**
 * Get feed posts (friends' posts)
 */
function getFeedPosts($conn, $user_id, $limit = 50) {
    $query = "SELECT posts.*, users.username, users.first_name, users.last_name, users.profile_pic 
              FROM posts 
              LEFT JOIN users ON posts.user_id = users.id 
              LEFT JOIN friends ON (posts.user_id = friends.friend_id AND friends.user_id = ?) OR (posts.user_id = friends.user_id AND friends.friend_id = ?)
              WHERE posts.user_id = ? OR friends.status = 'accepted'
              GROUP BY posts.id
              ORDER BY posts.created_at DESC 
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $posts;
}

/**
 * Get post by ID
 */
function getPostById($conn, $post_id) {
    $stmt = $conn->prepare("SELECT posts.*, users.username, users.first_name, users.last_name, users.profile_pic 
                           FROM posts 
                           LEFT JOIN users ON posts.user_id = users.id 
                           WHERE posts.id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();
    return $post;
}

/**
 * Get post likes count
 */
function getLikesCount($conn, $post_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

/**
 * Check if user liked a post
 */
function hasLiked($conn, $user_id, $post_id) {
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $liked = $result->num_rows > 0;
    $stmt->close();
    return $liked;
}

/**
 * Get post comments
 */
function getPostComments($conn, $post_id) {
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
    return $comments;
}

/**
 * Get notifications
 */
function getNotifications($conn, $user_id, $limit = 20) {
    $stmt = $conn->prepare("SELECT n.*, u.username, u.first_name, u.last_name, u.profile_pic 
                           FROM notifications n 
                           LEFT JOIN users u ON n.from_user_id = u.id 
                           WHERE n.user_id = ? 
                           ORDER BY n.created_at DESC 
                           LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}

/**
 * Get unread notifications count
 */
function getUnreadNotificationsCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

/**
 * Search users
 */
function searchUsers($conn, $query, $exclude_user_id) {
    $stmt = $conn->prepare("SELECT id, username, first_name, last_name, profile_pic, bio 
                           FROM users 
                           WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?) 
                           AND id != ? 
                           LIMIT 20");
    $search_term = "%$query%";
    $stmt->bind_param("sssi", $search_term, $search_term, $search_term, $exclude_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $users;
}

/**
 * Format relative time
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Sanitize output
 */
function sanitize($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
