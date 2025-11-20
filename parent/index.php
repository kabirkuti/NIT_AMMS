<?php
require_once '../db.php';
checkRole(['parent']);

$parent_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_id'];

// Get parent info
$parent_query = "SELECT * FROM parents WHERE id = $parent_id";
$parent = $conn->query($parent_query)->fetch_assoc();

// Get student info
$student_query = "SELECT s.*, d.dept_name, c.class_name 
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  WHERE s.id = $student_id";
$student = $conn->query($student_query)->fetch_assoc();

// Get today's attendance
$today = date('Y-m-d');
$today_query = "SELECT * FROM student_attendance 
                WHERE student_id = $student_id AND attendance_date = '$today'";
$today_attendance = $conn->query($today_query)->fetch_assoc();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
      <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Parent Portal</h1>
        </div>
        <div class="user-info">
            <a href="profile.php" class="btn btn-info">👤 My Profile</a>
            <span>👨‍👩‍👦 <?php echo htmlspecialchars($parent['parent_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h2>👤 Child's Profile</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                <div><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></div>
                <div><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></div>
                <div><strong>Department:</strong> <?php echo htmlspecialchars($student['dept_name']); ?></div>
                <div><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name']); ?></div>
                <div><strong>Year:</strong> <?php echo $student['year']; ?> | <strong>Semester:</strong> <?php echo $student['semester']; ?></div>
            </div>
            <div style="margin-top: 15px;">
                <p>💡 <strong>Tip:</strong> Click "👤 My Profile" above to view and upload your profile photo!</p>
            </div>
        </div>

        <?php if ($today_attendance): ?>
            <div class="alert alert-<?php echo $today_attendance['status'] === 'present' ? 'success' : 'warning'; ?>">
                <?php if ($today_attendance['status'] === 'present'): ?>
                    ✅ Your child was marked <strong>PRESENT</strong> today
                <?php elseif ($today_attendance['status'] === 'absent'): ?>
                    ❌ Your child was marked <strong>ABSENT</strong> today
                <?php else: ?>
                    ⏰ Your child was marked <strong>LATE</strong> today
                <?php endif; ?>
                
                <?php if ($today_attendance['remarks']): ?>
                    <br><strong>Remarks:</strong> <?php echo htmlspecialchars($today_attendance['remarks']); ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ Attendance not marked yet for today
            </div>
        <?php endif; ?>

        <h3>📊 Child's Attendance Statistics</h3>
        
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
                
                <?php if ($overall_percentage < 75): ?>
                    <div class="alert alert-warning" style="margin-top: 10px;">
                        ⚠️ Warning: Attendance below 75%
                    </div>
                <?php endif; ?>
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
            <a href="attendance_report.php" class="btn btn-primary">📊 View Detailed Report</a>
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