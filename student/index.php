<?php
require_once '../db.php';
checkRole(['student']);

$student_id = $_SESSION['user_id'];

// Get student info with class section details
$student_query = "SELECT s.*, d.dept_name, c.class_name, c.section, c.year as class_year, c.semester as class_semester
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Get unread notifications count
$unread_count_query = "SELECT COUNT(*) as unread FROM student_notifications 
                       WHERE student_id = $student_id AND is_read = 0";
$unread_result = $conn->query($unread_count_query);
$unread_count = $unread_result->fetch_assoc()['unread'];

// Get recent notifications (last 10)
$notifications_query = "SELECT sn.*, u.full_name as teacher_name, c.section as class_section
                        FROM student_notifications sn
                        LEFT JOIN users u ON sn.teacher_id = u.id
                        LEFT JOIN classes c ON sn.class_id = c.id
                        WHERE sn.student_id = $student_id
                        ORDER BY sn.created_at DESC
                        LIMIT 10";
$notifications = $conn->query($notifications_query);

// Get today's attendance with subject
$today = date('Y-m-d');
$today_query = "SELECT sa.*, sub.subject_name, sub.subject_code
                FROM student_attendance sa
                LEFT JOIN subjects sub ON sa.subject_id = sub.id
                WHERE sa.student_id = $student_id AND sa.attendance_date = '$today'";
$today_attendance = $conn->query($today_query);

// Get current month statistics
$current_month = date('Y-m');
$month_stats_query = "SELECT 
                      COUNT(*) as total_days,
                      SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                      SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                      SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                      FROM student_attendance
                      WHERE student_id = $student_id 
                      AND DATE_FORMAT(attendance_date, '%Y-%m') = '$current_month'";
$month_stats_result = $conn->query($month_stats_query);
$month_stats = $month_stats_result->fetch_assoc();

$total_days = $month_stats['total_days'];
$attendance_percentage = $total_days > 0 ? round(($month_stats['present'] / $total_days) * 100, 2) : 0;

// Get overall statistics
$overall_stats_query = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                        FROM student_attendance
                        WHERE student_id = $student_id";
$overall_stats_result = $conn->query($overall_stats_query);
$overall_stats = $overall_stats_result->fetch_assoc();

$overall_total = $overall_stats['total_days'];
$overall_percentage = $overall_total > 0 ? round(($overall_stats['present'] / $overall_total) * 100, 2) : 0;

// Get recent attendance
$recent_query = "SELECT * FROM student_attendance 
                 WHERE student_id = $student_id 
                 ORDER BY attendance_date DESC LIMIT 10";
$recent_attendance = $conn->query($recent_query);

// Display class section with proper formatting
$section_names = [
    'Civil' => '🗿️ Civil Engineering',
    'Mechanical' => '⚙️ Mechanical Engineering',
    'CSE-A' => '💻 Computer Science - A',
    'CSE-B' => '💻 Computer Science - B',
    'Electrical' => '⚡ Electrical Engineering'
];

$display_section = isset($section_names[$student['section']]) ? 
                   $section_names[$student['section']] : 
                   htmlspecialchars($student['section'] ?? $student['class_name']);

// Inspirational quotes array
$inspirational_quotes = [
    ["quote" => "Success is not final, failure is not fatal: it is the courage to continue that counts.", "author" => "Winston Churchill"],
    ["quote" => "Education is the most powerful weapon which you can use to change the world.", "author" => "Nelson Mandela"],
    ["quote" => "The future belongs to those who believe in the beauty of their dreams.", "author" => "Eleanor Roosevelt"],
    ["quote" => "Your time is limited, don't waste it living someone else's life.", "author" => "Steve Jobs"],
    ["quote" => "The only way to do great work is to love what you do.", "author" => "Steve Jobs"],
    ["quote" => "Don't watch the clock; do what it does. Keep going.", "author" => "Sam Levenson"],
    ["quote" => "Believe you can and you're halfway there.", "author" => "Theodore Roosevelt"],
    ["quote" => "The expert in anything was once a beginner.", "author" => "Helen Hayes"],
    ["quote" => "Learning never exhausts the mind.", "author" => "Leonardo da Vinci"],
    ["quote" => "Strive for progress, not perfection.", "author" => "Unknown"]
];

// Select a random quote
$daily_quote = $inspirational_quotes[array_rand($inspirational_quotes)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        .notification-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        .notification-card {
            background: white;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .notification-card:hover {
            transform: translateX(5px);
        }
        
        .notification-card.unread {
            background: #e7f3ff;
            border-left-color: #ffc107;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .notification-from {
            font-weight: bold;
            color: #333;
        }
        
        .notification-date {
            font-size: 12px;
            color: #666;
        }
        
        .notification-message {
            color: #555;
            line-height: 1.6;
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        
        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .email-sent-badge {
            background: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
        }
        
        .no-notifications {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .notifications-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        /* Inspirational Quote Styles */
        .inspiration-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .inspiration-container::before {
            content: "✨";
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 60px;
            opacity: 0.2;
        }

        .quote-content {
            position: relative;
            z-index: 1;
        }

        .quote-text {
            font-size: 22px;
            font-style: italic;
            color: white;
            line-height: 1.6;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            font-weight: 500;
        }

        .quote-text::before {
            content: """;
            font-size: 40px;
            color: rgba(255,255,255,0.3);
            margin-right: 5px;
        }

        .quote-text::after {
            content: """;
            font-size: 40px;
            color: rgba(255,255,255,0.3);
            margin-left: 5px;
        }

        .quote-author {
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            text-align: right;
            font-weight: 600;
        }

        .quote-author::before {
            content: "— ";
        }

        .motivation-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: white;
        }

        .motivation-header h3 {
            margin: 0;
            font-size: 20px;
            color: white;
        }
    </style>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Student Portal</h1>
        </div>
        <div class="user-info">
            <a href="profile.php" class="btn btn-info">👤 My Profile</a>
            <span>👨‍🎓 <?php echo htmlspecialchars($student['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <!-- INSPIRATIONAL QUOTE SECTION -->
        <div class="inspiration-container">
            <div class="motivation-header">
                <span style="font-size: 28px;">💡</span>
                <h3>Daily Inspiration</h3>
            </div>
            <div class="quote-content">
                <p class="quote-text"><?php echo htmlspecialchars($daily_quote['quote']); ?></p>
                <p class="quote-author"><?php echo htmlspecialchars($daily_quote['author']); ?></p>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h2>👤 Student Profile</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                <div><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></div>
                <div><strong>Department:</strong> <?php echo htmlspecialchars($student['dept_name']); ?></div>
                <div><strong>Class/Section:</strong> <?php echo $display_section; ?></div>
                <div><strong>Year:</strong> <?php echo $student['year']; ?></div>
                <div><strong>Semester:</strong> <?php echo $student['semester']; ?></div>
                <div><strong>Admission Year:</strong> <?php echo htmlspecialchars($student['admission_year']); ?></div>
                <div>
                    <strong>Status:</strong> 
                    <?php if ($student['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <p>💡 <strong>Tip:</strong> Click "👤 My Profile" above to view and upload your profile photo!</p>
            </div>
        </div>

        <!-- MESSAGES/NOTIFICATIONS SECTION -->
        <div class="notifications-container">
            <h2>
                📬 Messages from Teachers 
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?php echo $unread_count; ?> New</span>
                <?php endif; ?>
            </h2>
            
            <?php if ($notifications && $notifications->num_rows > 0): ?>
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification-card <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>">
                        <div class="notification-header">
                            <div>
                                <span class="notification-from">
                                    👨‍🏫 <?php echo htmlspecialchars($notification['teacher_name']); ?>
                                </span>
                                <?php if ($notification['class_section']): ?>
                                    <span style="color: #666; font-size: 14px;">
                                        (<?php echo htmlspecialchars($notification['class_section']); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-date">
                                <?php 
                                $date = strtotime($notification['created_at']);
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
                            </div>
                        </div>
                        
                        <div class="notification-message">
                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                        </div>
                        
                        <div class="notification-footer">
                            <span>
                                📅 Date: <?php echo date('d M Y', strtotime($notification['notification_date'])); ?>
                            </span>
                            <?php if ($notification['email_sent'] == 1): ?>
                                <span class="email-sent-badge">✉️ Email Sent</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($notification['is_read'] == 0): ?>
                            <div style="margin-top: 10px;">
                                <span style="background: #ffc107; color: #000; padding: 3px 10px; border-radius: 15px; font-size: 11px;">
                                    🆕 New Message
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="all_messages.php" class="btn btn-primary">📬 View All Messages</a>
                </div>
            <?php else: ?>
                <div class="no-notifications">
                    <p style="font-size: 48px;">📭</p>
                    <p style="font-size: 18px; color: #666;">No messages yet</p>
                    <p style="font-size: 14px; color: #999;">Your teachers will send you attendance-related messages here</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($today_attendance && $today_attendance->num_rows > 0): ?>
            <?php $today_record = $today_attendance->fetch_assoc(); ?>
            <div class="alert alert-success">
                ✅ Today's Attendance: <strong><?php echo strtoupper($today_record['status']); ?></strong>
                <?php if ($today_record['subject_name']): ?>
                    <br>Subject: <?php echo htmlspecialchars($today_record['subject_name']); ?> (<?php echo htmlspecialchars($today_record['subject_code']); ?>)
                <?php endif; ?>
                <?php if ($today_record['remarks']): ?>
                    <br>Remarks: <?php echo htmlspecialchars($today_record['remarks']); ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ Attendance not marked yet for today
            </div>
        <?php endif; ?>

        <h3>📊 Attendance Statistics</h3>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📅 This Month</h3>
                <div class="stat-value"><?php echo $total_days; ?></div>
                <p>Total Classes</p>
            </div>
            
            <div class="stat-card">
                <h3>✅ Present</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $month_stats['present']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ Absent</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $month_stats['absent']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>⏰ Late</h3>
                <div class="stat-value" style="color: #ffc107;"><?php echo $month_stats['late']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>📈 Attendance %</h3>
                <div class="stat-value" style="color: <?php echo $attendance_percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                    <?php echo $attendance_percentage; ?>%
                </div>
            </div>
        </div>

        <div class="stats-grid" style="margin-top: 20px;">
            <div class="stat-card">
                <h3>📊 Overall Statistics</h3>
                <p><strong>Total Days:</strong> <?php echo $overall_total; ?></p>
                <p><strong>Present:</strong> <span style="color: #28a745;"><?php echo $overall_stats['present']; ?></span></p>
                <p><strong>Absent:</strong> <span style="color: #dc3545;"><?php echo $overall_stats['absent']; ?></span></p>
                <p><strong>Late:</strong> <span style="color: #ffc107;"><?php echo $overall_stats['late']; ?></span></p>
                <p><strong>Overall %:</strong> 
                    <span style="color: <?php echo $overall_percentage >= 75 ? '#28a745' : '#dc3545'; ?>; font-size: 20px; font-weight: bold;">
                        <?php echo $overall_percentage; ?>%
                    </span>
                </p>
            </div>
        </div>

        <div class="table-container" style="margin-top: 30px;">
            <h3>📝 Recent Attendance Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_attendance->num_rows > 0): ?>
                        <?php while ($record = $recent_attendance->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
                            <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                if ($record['status'] === 'present') $status_class = 'badge-success';
                                elseif ($record['status'] === 'absent') $status_class = 'badge-danger';
                                else $status_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo strtoupper($record['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No attendance records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="attendance_report.php" class="btn btn-primary">📊 View Detailed Report</a>  <br>
            <a href="today_attendance.php" class="btn btn-success">📅 Today's Attendance</a>
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