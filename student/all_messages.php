<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name, c.section
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get total count of notifications
$count_query = "SELECT COUNT(*) as total FROM student_notifications 
                WHERE student_id = $student_id";
$count_result = $conn->query($count_query);
$total_notifications = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get all notifications with pagination
$messages_query = "SELECT sn.*, u.full_name as teacher_name, c.section as class_section
                   FROM student_notifications sn
                   LEFT JOIN users u ON sn.teacher_id = u.id
                   LEFT JOIN classes c ON sn.class_id = c.id
                   WHERE sn.student_id = $student_id
                   ORDER BY sn.created_at DESC
                   LIMIT $per_page OFFSET $offset";
$messages = $conn->query($messages_query);

// Handle mark as read
if (isset($_POST['mark_as_read'])) {
    $notification_id = (int)$_POST['notification_id'];
    $update_query = "UPDATE student_notifications SET is_read = 1 
                     WHERE id = $notification_id AND student_id = $student_id";
    $conn->query($update_query);
    header("Location: all_messages.php?page=$page");
    exit();
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $update_query = "UPDATE student_notifications SET is_read = 1 
                     WHERE student_id = $student_id";
    $conn->query($update_query);
    header("Location: all_messages.php");
    exit();
}

// Get unread count
$unread_query = "SELECT COUNT(*) as unread FROM student_notifications 
                 WHERE student_id = $student_id AND is_read = 0";
$unread_result = $conn->query($unread_query);
$unread_count = $unread_result->fetch_assoc()['unread'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Messages - Student Portal</title>
    <link rel="stylesheet" href="../assets/style.css">
      <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        .message-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .message-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .message-actions {
            display: flex;
            gap: 10px;
        }

        .message-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .message-card {
            background: white;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .message-card:hover {
            transform: translateX(5px);
        }

        .message-card.unread {
            background: #e7f3ff;
            border-left-color: #ffc107;
        }

        .message-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .message-from {
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .message-time {
            font-size: 12px;
            color: #666;
        }

        .message-content {
            color: #555;
            line-height: 1.6;
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            white-space: pre-wrap;
        }

        .message-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
            flex-wrap: wrap;
            gap: 10px;
        }

        .email-badge {
            background: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
        }

        .new-badge {
            background: #ffc107;
            color: #000;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
        }

        .mark-read-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .mark-read-btn:hover {
            background: #0056b3;
        }

        .no-messages {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-messages p:first-child {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #007bff;
            transition: background 0.3s;
        }

        .pagination a:hover {
            background: #007bff;
            color: white;
        }

        .pagination .active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .filter-section {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-section form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>📬 All Messages - Student Portal</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-info">🏠 Dashboard</a>
            <span>👨‍🎓 <?php echo htmlspecialchars($student['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="message-header">
            <div>
                <h2>
                    📬 All Messages
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge"><?php echo $unread_count; ?> Unread</span>
                    <?php else: ?>
                        <span style="background: #28a745; color: white; border-radius: 50%; padding: 5px 10px; font-size: 14px;">✅ All Read</span>
                    <?php endif; ?>
                </h2>
                <p style="color: #666; margin-top: 5px;">Total Messages: <strong><?php echo $total_notifications; ?></strong></p>
            </div>
            <div class="message-actions">
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-warning" onclick="return confirm('Mark all messages as read?')">
                            ✓ Mark All Read
                        </button>
                    </form>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">← Back</a>
            </div>
        </div>

        <div class="message-container">
            <?php if ($messages && $messages->num_rows > 0): ?>
                <?php while ($message = $messages->fetch_assoc()): ?>
                    <div class="message-card <?php echo $message['is_read'] == 0 ? 'unread' : ''; ?>">
                        <div class="message-header-row">
                            <div>
                                <span class="message-from">
                                    👨‍🏫 <?php echo htmlspecialchars($message['teacher_name']); ?>
                                </span>
                                <?php if ($message['class_section']): ?>
                                    <span style="color: #666; font-size: 14px; margin-left: 10px;">
                                        (<?php echo htmlspecialchars($message['class_section']); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="message-time">
                                <?php 
                                $date = strtotime($message['created_at']);
                                $today_start = strtotime('today');
                                $yesterday_start = strtotime('yesterday');
                                
                                if ($date >= $today_start) {
                                    echo 'Today, ' . date('g:i A', $date);
                                } elseif ($date >= $yesterday_start) {
                                    echo 'Yesterday, ' . date('g:i A', $date);
                                } else {
                                    echo date('d M Y, g:i A', $date);
                                }
                                ?>
                            </span>
                        </div>

                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>

                        <div class="message-footer">
                            <span>
                                📅 <?php echo date('d M Y', strtotime($message['notification_date'])); ?>
                            </span>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php if ($message['email_sent'] == 1): ?>
                                    <span class="email-badge">✉️ Email Sent</span>
                                <?php endif; ?>
                                <?php if ($message['is_read'] == 0): ?>
                                    <span class="new-badge">🆕 New</span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notification_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" name="mark_as_read" class="mark-read-btn">
                                            ✓ Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="all_messages.php?page=1">« First</a>
                            <a href="all_messages.php?page=<?php echo $page - 1; ?>">‹ Previous</a>
                        <?php else: ?>
                            <span class="disabled">« First</span>
                            <span class="disabled">‹ Previous</span>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        if ($start > 1) echo '<span>...</span>';
                        
                        for ($i = $start; $i <= $end; $i++) {
                            if ($i == $page) {
                                echo '<span class="active">' . $i . '</span>';
                            } else {
                                echo '<a href="all_messages.php?page=' . $i . '">' . $i . '</a>';
                            }
                        }
                        
                        if ($end < $total_pages) echo '<span>...</span>';
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="all_messages.php?page=<?php echo $page + 1; ?>">Next ›</a>
                            <a href="all_messages.php?page=<?php echo $total_pages; ?>">Last »</a>
                        <?php else: ?>
                            <span class="disabled">Next ›</span>
                            <span class="disabled">Last »</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-messages">
                    <p>📭</p>
                    <h3>No Messages Yet</h3>
                    <p>You don't have any messages from teachers yet.</p>
                    <p style="margin-top: 20px;">
                        <a href="index.php" class="btn btn-primary">← Back to Dashboard</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>


     <!-- Compact Footer -->
    <div style="background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2a3254 100%); position: relative; overflow: hidden;">
        
        <!-- Animated Top Border -->
        <div style="height: 2px; background: linear-gradient(90deg, #4a9eff, #00d4ff, #4a9eff, #00d4ff); background-size: 200% 100%;"></div>
        
        <!-- Main Footer Container -->
        <div style="max-width: 1000px; margin: 0 auto; padding: 30px 20px 20px;">
            
            <!-- Developer Section -->
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px 20px; border-radius: 15px; border: 1px solid rgba(74, 158, 255, 0.15); text-align: center; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);">
                
                <!-- Title -->
                <p style="color: #ffffff; font-size: 14px; margin: 0 0 12px; font-weight: 500; letter-spacing: 0.5px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">✨ Designed & Developed by</p>
                
                <!-- Company Link -->
                <a href="https://himanshufullstackdeveloper.github.io/techyugsoftware/" style="display: inline-block; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 8px 24px; border: 2px solid #4a9eff; border-radius: 30px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.2), rgba(0, 212, 255, 0.2)); box-shadow: 0 3px 12px rgba(74, 158, 255, 0.3); margin-bottom: 15px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    🚀 Techyug Software Pvt. Ltd.
                </a>
                
                <!-- Divider -->
                <div style="width: 50%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); margin: 15px auto;"></div>
                
                <!-- Team Label -->
                <p style="color: #888; font-size: 10px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">💼 Development Team</p>
                
                <!-- Developer Badges -->
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
                    
                    <!-- Developer 1 -->
                    <a href="https://himanshufullstackdeveloper.github.io/portfoilohimanshu/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Himanshu Patil</span>
                    </a>
                    
                    <!-- Developer 2 -->
                    <a href="https://devpranaypanore.github.io/Pranaypanore-live-.html/" style="color: #ffffff; font-size: 13px; text-decoration: none; padding: 8px 16px; background: linear-gradient(135deg, rgba(74, 158, 255, 0.25), rgba(0, 212, 255, 0.25)); border-radius: 20px; border: 1px solid rgba(74, 158, 255, 0.4); display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(74, 158, 255, 0.2); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        <span style="font-size: 16px;">👨‍💻</span>
                        <span style="font-weight: 600;">Pranay Panore</span>
                    </a>
                </div>
                
                <!-- Role Tags -->
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Full Stack</span>
                    <span style="color: #00d4ff; font-size: 10px; padding: 4px 12px; background: rgba(0, 212, 255, 0.1); border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">UI/UX</span>
                    <span style="color: #4a9eff; font-size: 10px; padding: 4px 12px; background: rgba(74, 158, 255, 0.1); border-radius: 12px; border: 1px solid rgba(74, 158, 255, 0.3); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">Database</span>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
                
                <!-- Copyright -->
                <p style="color: #888; font-size: 12px; margin: 0 0 10px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">© 2025 NIT AMMS. All rights reserved.</p>
                
                <!-- Made With Love -->
                <p style="color: #666; font-size: 11px; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    Made with <span style="color: #ff4757; font-size: 14px;">❤️</span> by Techyug Software
                </p>
                
                <!-- Social Links -->
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 10px;">
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">📧</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">🌐</a>
                    <a href="#" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: rgba(74, 158, 255, 0.1); border: 1px solid rgba(74, 158, 255, 0.3); border-radius: 50%; color: #4a9eff; text-decoration: none; font-size: 14px;">💼</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>