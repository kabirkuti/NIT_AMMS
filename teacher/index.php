<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();

// Get all classes assigned to this teacher (grouped by section)
$classes_query = "SELECT c.id, c.class_name, c.section, c.year, c.semester, c.academic_year,
                  d.dept_name, d.id as dept_id,
                  COUNT(DISTINCT s.id) as student_count
                  FROM classes c
                  JOIN departments d ON c.department_id = d.id
                  LEFT JOIN students s ON (s.class_id = c.id OR 
                                          (c.section = 'Civil' AND s.class_id IN (SELECT id FROM classes WHERE section = 'Civil')) OR
                                          (c.section = 'IT' AND s.class_id IN (SELECT id FROM classes WHERE section = 'IT')) OR
                                          (c.section = 'Mechanical' AND s.class_id IN (SELECT id FROM classes WHERE section = 'Mechanical')) OR
                                          (c.section = 'Electrical' AND s.class_id IN (SELECT id FROM classes WHERE section = 'Electrical')) OR
                                          (c.section = 'CSE-A' AND s.class_id IN (SELECT id FROM classes WHERE section = 'CSE-A')) OR
                                          (c.section = 'CSE-B' AND s.class_id IN (SELECT id FROM classes WHERE section = 'CSE-B'))
                                          ) AND s.is_active = 1
                  WHERE c.teacher_id = ?
                  GROUP BY c.id, c.class_name, c.section, c.year, c.semester, c.academic_year, d.dept_name, d.id
                  ORDER BY c.section, c.year, c.semester";

$stmt = $conn->prepare($classes_query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$classes = $stmt->get_result();

// Get today's attendance stats
$today = date('Y-m-d');
$stats_query = "SELECT 
                COUNT(DISTINCT sa.student_id) as marked_today,
                SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_today,
                SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_today
                FROM student_attendance sa
                WHERE sa.marked_by = ? AND sa.attendance_date = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("is", $user['id'], $today);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/teacher.css">
      <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT AMMS - Teacher Portal</h1>
        </div>
        <div class="user-info">
            <a href="profile.php" class="btn btn-info">👤 My Profile</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($_GET['success']) && $_GET['success'] === 'attendance_saved'): ?>
            <div class="alert alert-success" style="padding: 20px; background: #d4edda; border-left: 5px solid #28a745; margin-bottom: 20px; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; color: #155724;">✅ Attendance Saved Successfully!</h3>
                <p style="margin: 5px 0; color: #155724;">
                    <strong><?php echo isset($_GET['count']) ? intval($_GET['count']) : 0; ?></strong> students marked for 
                    <strong><?php echo isset($_GET['date']) ? date('d M Y', strtotime($_GET['date'])) : 'today'; ?></strong>
                </p>
                <?php if (isset($_GET['summary_updated']) && intval($_GET['summary_updated']) > 0): ?>
                    <p style="margin: 5px 0; color: #155724; font-size: 14px;">
                        📊 Summary updated for <strong><?php echo intval($_GET['summary_updated']); ?></strong> students
                    </p>
                <?php endif; ?>
                <?php if (isset($_GET['errors']) && intval($_GET['errors']) > 0): ?>
                    <p style="margin: 5px 0; color: #856404;">
                        ⚠️ <strong><?php echo intval($_GET['errors']); ?></strong> errors occurred during save
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <h2>📊 Today's Summary - <?php echo date('d M Y'); ?></h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📝 ATTENDANCE MARKED TODAY</h3>
                <div class="stat-value"><?php echo $stats['marked_today'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ PRESENT</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $stats['present_today'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ ABSENT</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $stats['absent_today'] ?? 0; ?></div>
            </div>
        </div>

        <div class="table-container">
            <h3>📚 Select Class to Mark Attendance</h3>
            
            <?php if ($classes->num_rows > 0): ?>
                <div class="class-selection-grid">
                    <?php while ($class = $classes->fetch_assoc()): ?>
                        <div class="class-card">
                            <h3><?php echo htmlspecialchars($class['section']); ?></h3>
                            <div class="class-info">
                                <div class="info-item">
                                    <span>📖 Class:</span>
                                    <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>🏢 Department:</span>
                                    <strong><?php echo htmlspecialchars($class['dept_name']); ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>📅 Year:</span>
                                    <strong><?php echo $class['year']; ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>📆 Semester:</span>
                                    <strong><?php echo $class['semester']; ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>👥 Students:</span>
                                    <strong><?php echo $class['student_count']; ?></strong>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <a href="mark_attendance.php?class_id=<?php echo $class['id']; ?>&section=<?php echo urlencode($class['section']); ?>" 
                                   class="btn btn-primary" style="flex: 1;">
                                    📝 Mark Attendance
                                </a>
                                <a href="view_attendance.php?class_id=<?php echo $class['id']; ?>&section=<?php echo urlencode($class['section']); ?>" 
                                   class="btn btn-info" style="flex: 1;">
                                    📊 View Reports
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ No classes assigned to you yet. Please contact the administrator.
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <h3>ℹ️ Instructions</h3>
            <div style="background: #e3f2fd; padding: 20px; border-radius: 10px;">
                <ul style="list-style-position: inside; line-height: 2;">
                    <li>Select a class/section to mark attendance for today</li>
                    <li>You can see students from all sections you teach</li>
                    <li>Each section card shows the number of enrolled students</li>
                    <li>Mark attendance before the end of the day</li>
                    <li>You can view and edit attendance reports anytime</li>
                    <li>Click "👤 My Profile" to view and update your profile photo</li>
                </ul>
            </div>
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