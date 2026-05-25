<?php
// api/chat_handler.php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'];

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_contacts':
            $contacts = [];

            if ($current_user_type === 'student') {
                // Students can chat with:
                // 1. Companies where they applied
                $stmt = $conn->prepare("
                    SELECT u.id, c.company_name AS name, u.profile_picture, 'company' AS type 
                    FROM users u
                    JOIN companies c ON u.id = c.user_id
                    WHERE c.id IN (
                        SELECT i.company_id 
                        FROM internships i
                        JOIN applications a ON i.id = a.internship_id
                        JOIN students s ON a.student_id = s.id
                        WHERE s.user_id = ?
                    )
                ");
                $stmt->execute([$current_user_id]);
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 2. Admins
                $stmt = $conn->prepare("SELECT id, 'System Administrator' AS name, profile_picture, 'admin' AS type FROM users WHERE user_type = 'admin'");
                $stmt->execute();
                $contacts = array_merge($contacts, $stmt->fetchAll(PDO::FETCH_ASSOC));

                // 3. Friends (other students)
                $stmt = $conn->prepare("
                    SELECT u.id, s.fullname AS name, u.profile_picture, 'student' AS type 
                    FROM users u
                    JOIN students s ON u.id = s.user_id
                    WHERE s.id IN (
                        SELECT friend_id FROM friends WHERE student_id = (SELECT id FROM students WHERE user_id = ?) AND status = 'accepted'
                        UNION
                        SELECT student_id FROM friends WHERE friend_id = (SELECT id FROM students WHERE user_id = ?) AND status = 'accepted'
                    )
                ");
                $stmt->execute([$current_user_id, $current_user_id]);
                $contacts = array_merge($contacts, $stmt->fetchAll(PDO::FETCH_ASSOC));

            } elseif ($current_user_type === 'company') {
                // Companies can chat with:
                // 1. Students who applied to their internships
                $stmt = $conn->prepare("
                    SELECT u.id, s.fullname AS name, u.profile_picture, 'student' AS type 
                    FROM users u
                    JOIN students s ON u.id = s.user_id
                    WHERE s.id IN (
                        SELECT a.student_id 
                        FROM applications a
                        JOIN internships i ON a.internship_id = i.id
                        JOIN companies c ON i.company_id = c.id
                        WHERE c.user_id = ?
                    )
                ");
                $stmt->execute([$current_user_id]);
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 2. Admins
                $stmt = $conn->prepare("SELECT id, 'System Administrator' AS name, profile_picture, 'admin' AS type FROM users WHERE user_type = 'admin'");
                $stmt->execute();
                $contacts = array_merge($contacts, $stmt->fetchAll(PDO::FETCH_ASSOC));

            } elseif ($current_user_type === 'admin') {
                // Admins can chat with EVERYONE
                $stmt = $conn->prepare("
                    SELECT u.id, 
                           CASE 
                               WHEN u.user_type = 'student' THEN (SELECT fullname FROM students WHERE user_id = u.id)
                               WHEN u.user_type = 'company' THEN (SELECT company_name FROM companies WHERE user_id = u.id)
                               ELSE 'System Administrator'
                           END AS name, 
                           u.profile_picture, 
                           u.user_type AS type 
                    FROM users u 
                    WHERE u.id != ?
                ");
                $stmt->execute([$current_user_id]);
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Remove duplicate contact rows by ID
            $unique_contacts = [];
            $seen_ids = [];
            foreach ($contacts as $c) {
                if (!in_array($c['id'], $seen_ids)) {
                    $seen_ids[] = $c['id'];
                    
                    // Fetch last message details
                    $p1 = min($current_user_id, (int)$c['id']);
                    $p2 = max($current_user_id, (int)$c['id']);
                    
                    $msgStmt = $conn->prepare("SELECT last_message, last_message_time FROM conversations WHERE participant1_id = ? AND participant2_id = ?");
                    $msgStmt->execute([$p1, $p2]);
                    $convo = $msgStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $c['last_message'] = $convo['last_message'] ?? 'No messages yet';
                    $c['last_message_time'] = $convo['last_message_time'] ?? '';
                    
                    // Count unread messages
                    $unreadStmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
                    $unreadStmt->execute([$c['id'], $current_user_id]);
                    $c['unread_count'] = (int)$unreadStmt->fetchColumn();
                    
                    $unique_contacts[] = $c;
                }
            }

            echo json_encode(['success' => true, 'contacts' => $unique_contacts]);
            break;

        case 'get_messages':
            $contact_id = (int)($_GET['contact_id'] ?? 0);
            if ($contact_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid contact ID']);
                exit();
            }

            // Mark incoming messages as read
            $readStmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
            $readStmt->execute([$contact_id, $current_user_id]);

            // Fetch full message logs
            $stmt = $conn->prepare("
                SELECT id, sender_id, receiver_id, message, created_at, is_read
                FROM messages
                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at ASC
            ");
            $stmt->execute([$current_user_id, $contact_id, $contact_id, $current_user_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        case 'send_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Invalid request method']);
                exit();
            }

            $contact_id = (int)($_POST['contact_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');

            if ($contact_id <= 0 || $message === '') {
                echo json_encode(['success' => false, 'error' => 'Invalid fields']);
                exit();
            }

            // Get target user type
            $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
            $stmt->execute([$contact_id]);
            $receiver_type = $stmt->fetchColumn();

            if (!$receiver_type) {
                echo json_encode(['success' => false, 'error' => 'Receiver not found']);
                exit();
            }

            // Insert into messages table
            $insStmt = $conn->prepare("
                INSERT INTO messages (sender_id, receiver_id, sender_type, receiver_type, message, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            $insStmt->execute([$current_user_id, $contact_id, $current_user_type, $receiver_type, $message]);

            // Update conversations last message
            $p1 = min($current_user_id, $contact_id);
            $p2 = max($current_user_id, $contact_id);

            // Check if conversation exists
            $checkConvo = $conn->prepare("SELECT id FROM conversations WHERE participant1_id = ? AND participant2_id = ?");
            $checkConvo->execute([$p1, $p2]);
            $convo_id = $checkConvo->fetchColumn();

            if ($convo_id) {
                $upConvo = $conn->prepare("UPDATE conversations SET last_message = ?, last_message_time = NOW() WHERE id = ?");
                $upConvo->execute([$message, $convo_id]);
            } else {
                $insConvo = $conn->prepare("
                    INSERT INTO conversations (participant1_id, participant2_id, last_message, last_message_time, created_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $insConvo->execute([$p1, $p2, $message]);
            }

            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
