<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Get statistics
$stats = [];

// Total departments
$result = $conn->query("SELECT COUNT(*) as count FROM departments");
$stats['departments'] = $result->fetch_assoc()['count'];

// Total HODs
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'hod' AND is_active = 1");
$stats['hods'] = $result->fetch_assoc()['count'];

// Total teachers
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND is_active = 1");
$stats['teachers'] = $result->fetch_assoc()['count'];

// Total students
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE is_active = 1");
$stats['students'] = $result->fetch_assoc()['count'];

// Total classes
$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['classes'] = $result->fetch_assoc()['count'];

// Total parents
$result = $conn->query("SELECT COUNT(*) as count FROM parents");
$stats['parents'] = $result->fetch_assoc()['count'];

// Today's attendance
$result = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = CURDATE()");
$stats['today_attendance'] = $result->fetch_assoc()['count'];

// Recent activities
$recent_query = "SELECT sa.*, s.full_name as student_name, s.roll_number, 
                 c.class_name, u.full_name as teacher_name
                 FROM student_attendance sa
                 JOIN students s ON sa.student_id = s.id
                 JOIN classes c ON sa.class_id = c.id
                 JOIN users u ON sa.marked_by = u.id
                 ORDER BY sa.marked_at DESC LIMIT 10";
$recent_activities = $conn->query($recent_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
     <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Admin Panel</h1>
        </div>
        <div class="user-info">
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <h2>📊 Dashboard Overview</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🏢 Departments</h3>
                <div class="stat-value"><?php echo $stats['departments']; ?></div>
                <a href="manage_departments.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>👔 HODs</h3>
                <div class="stat-value"><?php echo $stats['hods']; ?></div>
                <a href="manage_hod.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>👨‍🏫 Teachers</h3>
                <div class="stat-value"><?php echo $stats['teachers']; ?></div>
                <a href="manage_teachers.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>👨‍🎓 Students</h3>
                <div class="stat-value"><?php echo $stats['students']; ?></div>
                <a href="manage_students.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>📚 Classes</h3>
                <div class="stat-value"><?php echo $stats['classes']; ?></div>
                <a href="manage_classes.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>👨‍👩‍👦 Parents</h3>
                <div class="stat-value"><?php echo $stats['parents']; ?></div>
                <a href="manage_parents.php" class="btn btn-info btn-sm">Manage</a>
            </div>
            
            <div class="stat-card">
                <h3>📝 Today's Attendance</h3>
                <div class="stat-value"><?php echo $stats['today_attendance']; ?></div>
                <a href="view_attendance_reports.php" class="btn btn-info btn-sm">View Reports</a>
            </div>
        </div>

        <div class="table-container">
            <h3>🕒 Recent Attendance Activities</h3>
            
            <?php if ($recent_activities->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Student</th>
                            <th>Roll Number</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Marked By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($activity['marked_at'])); ?></td>
                            <td><?php echo htmlspecialchars($activity['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($activity['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($activity['class_name']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                if ($activity['status'] === 'present') $status_class = 'badge-success';
                                elseif ($activity['status'] === 'absent') $status_class = 'badge-danger';
                                else $status_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo strtoupper($activity['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($activity['teacher_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No recent attendance activities</div>
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
                
               
            </div>
        </div>
    </div>
  
</body>
</html>