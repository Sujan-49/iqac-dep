<?php
declare(strict_types=1);

function iqac_menu_items(string $role): array
{
    $role = iqac_normalize_role($role);
    $hasTutorAccess = in_array($role, ['staff', 'tutor'], true)
        && !empty($_SESSION['is_tutor'])
        && trim((string)($_SESSION['batch'] ?? '')) !== ''
        && trim((string)($_SESSION['semester'] ?? '')) !== ''
        && trim((string)($_SESSION['section'] ?? '')) !== ''
        && (int)($_SESSION['tutor_assigned_students'] ?? 0) > 0;
    $all = [
        'dashboard' => ['Dashboard', 'academic_erp.php'],
        'student_dashboard' => ['Dashboard', 'dashboard_student.php'],
        'staff_dashboard' => ['Dashboard', 'dashboard_staff.php'],
        'tutor_dashboard' => ['Dashboard', 'dashboard_tutor.php'],
        'admin_dashboard' => ['Dashboard', 'dashboard_admin.php'],
        'super_dashboard' => ['Dashboard', 'dashboard_super.php'],
        'profile' => ['Profile Completion', 'std_index.php'],
        'admission_status' => ['Application Status', 'admission_status.php'],
        'my_marks' => ['My Marks', 'marks_entry.php'],
        'marks' => ['Semester Marks', 'marks_entry.php'],
        'achievements' => ['Achievements', 'std_achiev.php'],
        'sports' => ['Sports', 'std_sports_details.php'],
        'publications' => ['Publications', 'std_publication.php'],
        'industry' => ['Industry Visit', 'std_indus.php'],
        'higher_studies' => ['Higher Studies', 'std_higher.php'],
        'applications' => ['Applications', 'applications.php'],
        'student_forms' => ['Student Forms', 'erp_forms.php?scope=student'],
        'notifications' => ['Notifications', 'student_dashboard.php#notifications'],
        'erp_notifications' => ['Notifications', 'academic_erp.php#notifications'],
        'assigned_subjects' => ['My Subjects', 'marks_entry.php'],
        'marks_verification' => ['Marks Entry', 'marks_entry.php'],
        'attendance_entry' => ['Attendance', 'semester_details.php'],
        'staff_reports' => ['Reports', 'nba_report.php?report_type=teacher'],
        'gallery_upload' => ['Gallery Upload', 'upload.php'],
        'department_uploads' => ['Department Upload', 'upload.php'],
        'exports' => ['Exports', 'nba_report.php'],
        'staff_forms' => ['Staff Forms', 'erp_forms.php?scope=staff'],
        'tutor_panel' => ['Tutor Panel', 'dashboard_staff.php#tutor-panel'],
        'student_records' => ['Student Records', 'view_std.php'],
        'batch_reports' => ['Batch Reports', 'nba_report.php?report_type=batch'],
        'attendance_analytics' => ['Attendance Analytics', 'nba_report.php?report_type=batch'],
        'internal_analytics' => ['Internal Analytics', 'marks_entry.php'],
        'success_rate' => ['Success Rate', 'successrate.php'],
        'mentor_forms' => ['Mentor Forms', 'erp_forms.php?scope=student'],
        'batch_subject_analysis' => ['Batch Subject Analysis', 'nba_report.php?report_type=subject'],
        'student_management' => ['Students', 'std_upload.php'],
        'staff_management' => ['Staff', 'staff_upload.php'],
        'tutor_management' => ['Tutor Assignment', 'admin_controls.php'],
        'subject_management' => ['Subjects', 'admin_controls.php'],
        'batch_management' => ['Batch Management', 'admin_controls.php'],
        'semester_management' => ['Semester Management', 'admin_controls.php'],
        'promotion' => ['Promotion System', 'admin_controls.php'],
        'bulk_operations' => ['Bulk Operations', 'admin_controls.php'],
        'admin_forms' => ['Admin Forms', 'erp_forms.php?scope=admin'],
        'reports' => ['Reports', 'successrate.php'],
        'nba_reports' => ['NBA Reports', 'nba_report.php?report_type=nba'],
        'gallery_management' => ['Gallery Management', 'gallery.php'],
        'upload_management' => ['Upload Management', 'upload.php'],
        'mou_module' => ['MOU Module', 'mou_index.php'],
        'audit_logs' => ['Audit Logs', 'academic_erp.php#notifications'],
        'system_settings' => ['System Settings', 'admin_controls.php'],
        'password_resets' => ['Password Resets', 'password_resets.php'],
        'password' => ['Change Password', 'password_change.php'],
        'logout' => ['Logout', 'logout.php'],
    ];

    $effectiveRole = $hasTutorAccess ? 'tutor' : $role;
    $keys = match ($effectiveRole) {
        'student' => ['student_dashboard','profile','my_marks','marks','achievements','sports','publications','industry','higher_studies','applications','student_forms','notifications','password','logout'],
        'staff' => ['staff_dashboard','assigned_subjects','marks_verification','attendance_entry','staff_forms','staff_reports','gallery_upload','department_uploads','erp_notifications','password','logout'],
        'tutor' => ['tutor_dashboard','assigned_subjects','marks_verification','attendance_entry','staff_forms','staff_reports','gallery_upload','department_uploads','erp_notifications','tutor_panel','student_records','batch_reports','attendance_analytics','internal_analytics','success_rate','mentor_forms','batch_subject_analysis','password','logout'],
        'hod', 'iqac' => ['admin_dashboard','student_management','staff_management','subject_management','tutor_management','batch_management','semester_management','promotion','bulk_operations','admin_forms','form_reviews','staff_forms','reports','nba_reports','gallery_management','upload_management','mou_module','applications','audit_logs','erp_notifications','system_settings','password','logout'],
        'admin' => ['admin_dashboard','student_management','staff_management','subject_management','tutor_management','batch_management','semester_management','promotion','bulk_operations','admin_forms','form_reviews','staff_forms','reports','nba_reports','gallery_management','upload_management','mou_module','applications','audit_logs','erp_notifications','password_resets','system_settings','password','logout'],
        'super_admin' => ['super_dashboard','student_management','staff_management','subject_management','tutor_management','batch_management','semester_management','promotion','bulk_operations','admin_forms','form_reviews','staff_forms','reports','nba_reports','gallery_management','upload_management','mou_module','applications','audit_logs','erp_notifications','password_resets','system_settings','password','logout'],
        default => ['dashboard','password','logout'],
    };

    return array_intersect_key($all, array_flip($keys));
}

function iqac_menu_item_icon(string $key): string
{
    return match ($key) {
        'dashboard', 'student_dashboard', 'staff_dashboard', 'tutor_dashboard', 'admin_dashboard', 'super_dashboard' => 'fa-house',
        'profile' => 'fa-id-card',
        'admission_status' => 'fa-file-signature',
        'my_marks', 'marks', 'marks_verification' => 'fa-pen-to-square',
        'achievements' => 'fa-trophy',
        'sports' => 'fa-volleyball',
        'publications', 'staff_publications' => 'fa-newspaper',
        'industry' => 'fa-industry',
        'higher_studies' => 'fa-graduation-cap',
        'applications' => 'fa-envelope-open-text',
        'student_forms', 'staff_forms', 'admin_forms', 'mentor_forms' => 'fa-file-invoice',
        'notifications', 'erp_notifications' => 'fa-bell',
        'assigned_subjects', 'subject_management' => 'fa-book',
        'attendance_entry', 'attendance_analytics' => 'fa-calendar-check',
        'staff_reports', 'batch_reports', 'nba_reports', 'iqac_reports', 'reports' => 'fa-chart-pie',
        'gallery_upload', 'gallery_management' => 'fa-images',
        'department_uploads', 'upload_management' => 'fa-cloud-arrow-up',
        'mou_module' => 'fa-handshake',
        'tutor_panel', 'tutor_management' => 'fa-user-tie',
        'student_records', 'student_management' => 'fa-users',
        'staff_management' => 'fa-users-gear',
        'batch_management' => 'fa-folder-open',
        'semester_management' => 'fa-timeline',
        'promotion' => 'fa-angles-up',
        'bulk_operations' => 'fa-database',
        'form_reviews' => 'fa-check-double',
        'audit_logs' => 'fa-receipt',
        'system_settings' => 'fa-sliders',
        'password_resets' => 'fa-key',
        'password' => 'fa-lock',
        'logout' => 'fa-right-from-bracket',
        default => 'fa-circle-dot'
    };
}

function iqac_get_sidebar_structure(string $role, bool $hasTutorAccess): array
{
    $role = iqac_normalize_role($role);
    $effectiveRole = $hasTutorAccess ? 'tutor' : $role;

    return match ($effectiveRole) {
        'student' => [
            'Dashboard' => ['icon' => 'fa-house', 'keys' => ['student_dashboard']],
            'Academics' => ['icon' => 'fa-graduation-cap', 'keys' => ['my_marks', 'marks']],
            'Student Activities' => ['icon' => 'fa-star', 'keys' => ['profile', 'achievements', 'sports', 'publications', 'industry', 'higher_studies']],
            'Applications & Forms' => ['icon' => 'fa-file-invoice', 'keys' => ['applications', 'student_forms', 'notifications']],
            'Account' => ['icon' => 'fa-user-gear', 'keys' => ['password', 'logout']]
        ],
        'staff' => [
            'Dashboard' => ['icon' => 'fa-house', 'keys' => ['staff_dashboard']],
            'Students' => ['icon' => 'fa-user-graduate', 'keys' => ['student_records', 'attendance_entry', 'staff_reports']],
            'Academics' => ['icon' => 'fa-book-open', 'keys' => ['assigned_subjects', 'marks_verification', 'semester_management', 'internal_analytics', 'batch_subject_analysis']],
            'Faculty' => ['icon' => 'fa-chalkboard-user', 'keys' => ['staff_fdp', 'staff_publications', 'staff_forms']],
            'Reports' => ['icon' => 'fa-chart-simple', 'keys' => ['batch_reports', 'success_rate', 'performance_reports', 'subject_reports']],
            'Gallery & Uploads' => ['icon' => 'fa-image', 'keys' => ['gallery_upload', 'department_uploads', 'erp_notifications']],
            'Account' => ['icon' => 'fa-user-gear', 'keys' => ['password', 'logout']]
        ],
        'tutor' => [
            'Dashboard' => ['icon' => 'fa-house', 'keys' => ['tutor_dashboard']],
            'Batch Management' => ['icon' => 'fa-users-gear', 'keys' => ['student_records', 'tutor_panel', 'attendance_entry', 'mentor_forms']],
            'Analytics' => ['icon' => 'fa-chart-line', 'keys' => ['batch_reports', 'batch_subject_analysis', 'internal_analytics', 'success_rate']],
            'Reports' => ['icon' => 'fa-file-signature', 'keys' => ['staff_reports', 'nba_reports', 'iqac_reports']],
            'Uploads' => ['icon' => 'fa-cloud-arrow-up', 'keys' => ['gallery_upload', 'department_uploads', 'erp_notifications']],
            'Account' => ['icon' => 'fa-user-gear', 'keys' => ['password', 'logout']]
        ],
        'hod' => [
            'Dashboard' => ['icon' => 'fa-house', 'keys' => ['admin_dashboard']],
            'Department' => ['icon' => 'fa-building', 'keys' => ['student_management', 'staff_management', 'subject_management', 'tutor_management']],
            'Analytics' => ['icon' => 'fa-chart-line', 'keys' => ['reports', 'academic_performance', 'attendance', 'department_statistics']],
            'Reports' => ['icon' => 'fa-file-invoice', 'keys' => ['department_reports', 'nba_reports', 'iqac_reports', 'exports']],
            'Administration' => ['icon' => 'fa-sliders', 'keys' => ['staff_forms', 'semester_management', 'batch_management', 'promotion', 'bulk_operations', 'admin_forms', 'form_reviews', 'gallery_management', 'upload_management', 'mou_module', 'applications', 'audit_logs', 'erp_notifications', 'system_settings']],
            'Account' => ['icon' => 'fa-user-gear', 'keys' => ['password', 'logout']]
        ],
        'iqac' => [
            'Dashboard' => ['icon' => 'fa-house', 'keys' => ['admin_dashboard']],
            'IQAC' => ['icon' => 'fa-clipboard-list', 'keys' => ['admin_forms', 'nba_reports', 'academic_performance', 'reports']],
            'Reports' => ['icon' => 'fa-file-lines', 'keys' => ['department_reports', 'batch_reports', 'subject_reports', 'iqac_reports', 'exports']],
            'Administration' => ['icon' => 'fa-sliders', 'keys' => ['semester_management', 'batch_management', 'promotion', 'bulk_operations', 'form_reviews', 'gallery_management', 'upload_management', 'mou_module', 'applications', 'audit_logs', 'erp_notifications', 'system_settings']],
            'Account' => ['icon' => 'fa-user-gear', 'keys' => ['password', 'logout']]
        ],
        'admin' => [
            'Dashboard' => ['icon' => 'fa-house', 'keys' => ['admin_dashboard']],
            'Users' => ['icon' => 'fa-users', 'keys' => ['student_management', 'staff_management', 'tutor_management', 'hod_management', 'iqac_management']],
            'Academic' => ['icon' => 'fa-graduation-cap', 'keys' => ['subject_management', 'semester_management', 'batch_management', 'promotion', 'bulk_operations', 'admin_forms', 'form_reviews', 'staff_forms']],
            'Reports' => ['icon' => 'fa-chart-bar', 'keys' => ['reports', 'batch_reports', 'nba_reports', 'iqac_reports']],
            'Uploads' => ['icon' => 'fa-cloud-upload-alt', 'keys' => ['gallery_management', 'upload_management', 'mou_module']],
            'Administration' => ['icon' => 'fa-cogs', 'keys' => ['applications', 'audit_logs', 'erp_notifications', 'password_resets', 'system_settings']],
            'Account' => ['icon' => 'fa-user-gear', 'keys' => ['password', 'logout']]
        ],
        'super_admin' => [
            'Dashboard' => ['icon' => 'fa-house', 'keys' => ['super_dashboard']],
            'User Management' => ['icon' => 'fa-users', 'keys' => ['student_management', 'staff_management', 'tutor_management', 'hod_management', 'iqac_management']],
            'Academic Management' => ['icon' => 'fa-graduation-cap', 'keys' => ['subject_management', 'semester_management', 'batch_management', 'promotion', 'bulk_operations', 'admin_forms', 'form_reviews', 'staff_forms']],
            'Department Management' => ['icon' => 'fa-building', 'keys' => ['department_management', 'mou_module']],
            'Reports & Analytics' => ['icon' => 'fa-chart-bar', 'keys' => ['reports', 'batch_reports', 'nba_reports', 'iqac_reports']],
            'Upload Center' => ['icon' => 'fa-cloud-upload-alt', 'keys' => ['gallery_management', 'upload_management']],
            'Security' => ['icon' => 'fa-shield-halved', 'keys' => ['applications', 'audit_logs', 'password_resets']],
            'System Settings' => ['icon' => 'fa-cogs', 'keys' => ['system_settings', 'erp_notifications']],
            'Database' => ['icon' => 'fa-database', 'keys' => ['database_management']],
            'Account' => ['icon' => 'fa-user-gear', 'keys' => ['password', 'logout']]
        ],
        default => [
            'Dashboard' => ['icon' => 'fa-house', 'keys' => ['dashboard']],
            'Account' => ['icon' => 'fa-user-gear', 'keys' => ['password', 'logout']]
        ]
    };
}

function iqac_render_sidebar(array $user, string $active = ''): void
{
    $roleLabel = iqac_has_tutor_access($user) ? 'TUTOR' : strtoupper(iqac_effective_role($user));
    
    // Inject Font Awesome for sidebar icons
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />';
    
    // Inject isolated CSS styling targeting only the sidebar and its collapsed grid layouts
    echo '<style>
.erp-layout {
  display: grid !important;
  grid-template-columns: var(--sidebar-width, 280px) 1fr !important;
  transition: grid-template-columns 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.erp-layout.sidebar-collapsed {
  --sidebar-width: 78px !important;
}
.erp-sidebar {
  background: linear-gradient(180deg, #1e3a8a 0%, #162f73 52%, #10245a 100%) !important;
  color: #fff !important;
  padding: 24px 14px !important;
  position: sticky !important;
  top: 0 !important;
  height: 100vh !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  transition: padding 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  display: flex !important;
  flex-direction: column !important;
  box-sizing: border-box !important;
  border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.erp-sidebar.collapsed {
  padding: 24px 8px !important;
  overflow: visible !important;
}
.erp-sidebar .logo-row {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding-bottom: 20px !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.16) !important;
  margin-bottom: 16px !important;
  flex-shrink: 0 !important;
  transition: all 0.2s ease !important;
}
.collapsed .logo-row {
  justify-content: center !important;
  padding-bottom: 12px !important;
}
.erp-sidebar .logo-box {
  width: 40px !important;
  height: 40px !important;
  display: grid !important;
  place-items: center !important;
  background: #fff !important;
  color: #1e3a8a !important;
  font-weight: 800 !important;
  border-radius: 8px !important;
  border-bottom: 3px solid #d4af37 !important;
  flex-shrink: 0 !important;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}
.erp-sidebar .logo-text {
  transition: opacity 0.2s ease !important;
  white-space: nowrap !important;
  line-height: 1.2 !important;
}
.erp-sidebar .logo-text span {
  font-size: 11px !important;
  color: #d4af37 !important;
  font-weight: bold !important;
}
.collapsed .logo-text {
  display: none !important;
}

/* Sidebar Search */
.sidebar-search-container {
  position: relative !important;
  margin-bottom: 16px !important;
  flex-shrink: 0 !important;
}
.collapsed .sidebar-search-container {
  display: none !important;
}
.sidebar-search-container .search-icon {
  position: absolute !important;
  left: 12px !important;
  top: 50% !important;
  transform: translateY(-50%) !important;
  color: rgba(255, 255, 255, 0.6) !important;
  font-size: 14px !important;
}
.sidebar-search-container input {
  width: 100% !important;
  background: rgba(255, 255, 255, 0.08) !important;
  border: 1px solid rgba(255, 255, 255, 0.16) !important;
  color: #fff !important;
  border-radius: 6px !important;
  padding: 10px 12px 10px 36px !important;
  font-size: 13px !important;
  box-sizing: border-box !important;
  outline: none !important;
  transition: all 0.2s ease !important;
}
.sidebar-search-container input:focus {
  border-color: #d4af37 !important;
  background: rgba(255, 255, 255, 0.12) !important;
}

/* Sidebar Toggle */
.sidebar-toggle {
  position: absolute !important;
  right: -12px !important;
  top: 32px !important;
  width: 24px !important;
  height: 24px !important;
  background: #d4af37 !important;
  color: #1e293b !important;
  border: none !important;
  border-radius: 50% !important;
  cursor: pointer !important;
  display: grid !important;
  place-items: center !important;
  font-size: 11px !important;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
  z-index: 10001 !important;
  transition: transform 0.25s ease !important;
  outline: none !important;
}
.collapsed .sidebar-toggle {
  transform: rotate(180deg) !important;
}

/* Sidebar Navigation Items */
.sidebar-nav {
  display: flex !important;
  flex-direction: column !important;
  gap: 4px !important;
  flex-grow: 1 !important;
  overflow-y: auto !important;
}
.sidebar-nav::-webkit-scrollbar {
  width: 4px !important;
}
.sidebar-nav::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.15) !important;
  border-radius: 2px !important;
}

/* Direct link items */
.nav-item-direct a {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 12px 14px !important;
  color: rgba(255, 255, 255, 0.8) !important;
  text-decoration: none !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  border-radius: 8px !important;
  transition: all 0.15s ease !important;
  white-space: nowrap !important;
  border: 1px solid transparent !important;
}
.nav-item-direct a:hover {
  background: rgba(255, 255, 255, 0.14) !important;
  border-color: rgba(212, 175, 55, 0.28) !important;
  color: #fff !important;
}
.nav-item-direct a.active {
  background: rgba(255, 255, 255, 0.14) !important;
  border-color: rgba(212, 175, 55, 0.28) !important;
  color: #fff !important;
  border-left: 3px solid #d4af37 !important;
}

/* Category structure (Accordion) */
.nav-category {
  position: relative !important;
  display: flex !important;
  flex-direction: column !important;
}
.category-header {
  width: 100% !important;
  background: none !important;
  border: none !important;
  color: rgba(255, 255, 255, 0.8) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  padding: 12px 14px !important;
  cursor: pointer !important;
  border-radius: 8px !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  transition: all 0.15s ease !important;
  white-space: nowrap !important;
  outline: none !important;
  text-align: left !important;
}
.category-header:hover {
  background: rgba(255, 255, 255, 0.14) !important;
  color: #fff !important;
}
.category-header .header-left {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
}
.category-header .chevron-icon {
  font-size: 11px !important;
  transition: transform 0.2s ease !important;
  color: rgba(255, 255, 255, 0.6) !important;
}
.nav-category.open .category-header {
  color: #fff !important;
}
.nav-category.open .category-header .chevron-icon {
  transform: rotate(180deg) !important;
}
.nav-category.open .category-header .nav-icon {
  color: #d4af37 !important;
}

/* Submenu container */
.category-submenu {
  max-height: 0 !important;
  overflow: hidden !important;
  transition: max-height 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease !important;
  opacity: 0 !important;
  display: flex !important;
  flex-direction: column !important;
  padding-left: 38px !important;
  gap: 2px !important;
}
.nav-category.open .category-submenu {
  max-height: 500px !important;
  opacity: 1 !important;
  padding-top: 4px !important;
  padding-bottom: 8px !important;
}

.submenu-link {
  padding: 8px 12px !important;
  color: rgba(255, 255, 255, 0.7) !important;
  text-decoration: none !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  border-radius: 6px !important;
  transition: all 0.15s ease !important;
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  white-space: nowrap !important;
}
.submenu-link::before {
  content: "•" !important;
  color: rgba(255, 255, 255, 0.3) !important;
  font-size: 14px !important;
}
.submenu-link:hover {
  color: #fff !important;
}
.submenu-link:hover::before {
  color: #d4af37 !important;
}
.submenu-link.active {
  color: #fff !important;
  font-weight: 600 !important;
  background: rgba(255, 255, 255, 0.08) !important;
}
.submenu-link.active::before {
  color: #d4af37 !important;
}

/* Tooltip on hover in collapsed mode */
.collapsed [data-tooltip] {
  position: relative !important;
}
.collapsed [data-tooltip]::after {
  content: attr(data-tooltip) !important;
  position: absolute !important;
  left: calc(100% + 14px) !important;
  top: 50% !important;
  transform: translateY(-50%) scale(0.95) !important;
  background: #1e293b !important;
  color: #fff !important;
  padding: 6px 12px !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  border-radius: 6px !important;
  white-space: nowrap !important;
  opacity: 0 !important;
  pointer-events: none !important;
  transition: all 0.15s ease !important;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06) !important;
  z-index: 10002 !important;
}
.collapsed [data-tooltip]:hover::after {
  opacity: 1 !important;
  transform: translateY(-50%) scale(1) !important;
}

/* Collapsed submenu popover */
.collapsed .nav-category:hover .category-submenu {
  display: flex !important;
  position: absolute !important;
  left: 70px !important;
  top: 0 !important;
  background: #10245a !important; 
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  border-radius: 8px !important;
  padding: 8px 10px !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
  z-index: 10003 !important;
  min-width: 200px !important;
  max-height: none !important;
  opacity: 1 !important;
}
.collapsed .nav-category .category-submenu {
  display: none !important;
}
.collapsed .nav-category:hover .category-submenu .submenu-link {
  display: flex !important;
}

/* Collapsed Sidebar adjustments */
.collapsed .nav-text,
.collapsed .chevron-icon {
  display: none !important;
}
.collapsed .category-header .header-left,
.collapsed .nav-item-direct a {
  justify-content: center !important;
  width: 100% !important;
}
.collapsed .nav-icon {
  font-size: 18px !important;
  margin: 0 !important;
}
.collapsed .category-header,
.collapsed .nav-item-direct a {
  padding: 14px 0 !important;
}

/* Responsive adjustment */
@media (max-width: 980px) {
  .erp-layout {
    grid-template-columns: 1fr !important;
  }
  .erp-sidebar {
    height: auto !important;
    position: relative !important;
    padding: 16px !important;
  }
  .sidebar-toggle {
    display: none !important;
  }
}
</style>';
    
    echo '<aside class="erp-sidebar" id="erp-sidebar">';
    
    // Collapsible toggle button
    echo '<button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle Sidebar"><i class="fas fa-chevron-left"></i></button>';
    
    // Logo block
    echo '<div class="logo-row">';
    echo '<div class="logo-box">PTC</div>';
    echo '<div class="logo-text"><strong>PSG PTC ERP</strong><br><span>' . htmlspecialchars($roleLabel) . '</span></div>';
    echo '</div>';
    
    // Search menu
    echo '<div class="sidebar-search-container">';
    echo '<i class="fas fa-search search-icon"></i>';
    echo '<input type="text" id="sidebar-search" placeholder="Search menu..." autocomplete="off">';
    echo '</div>';
    
    // Navigation container
    echo '<nav class="sidebar-nav">';
    
    // Fetch authorized items
    $menuItems = iqac_menu_items($user['role']);
    
    // Get structured categories for the current user's role
    $hasTutorAccess = iqac_has_tutor_access($user);
    $structure = iqac_get_sidebar_structure($user['role'], $hasTutorAccess);
    
    $processedKeys = [];
    
    foreach ($structure as $categoryName => $catInfo) {
        $iconClass = $catInfo['icon'];
        $keys = $catInfo['keys'];
        
        // Find authorized items for this category
        $categoryItems = [];
        foreach ($keys as $key) {
            if (isset($menuItems[$key])) {
                $categoryItems[$key] = $menuItems[$key];
                $processedKeys[] = $key;
            }
        }
        
        if (empty($categoryItems)) {
            continue;
        }
        
        // If the category contains only one key and it's a Dashboard direct link
        if (count($categoryItems) === 1 && str_contains(strtolower($categoryName), 'dashboard')) {
            $key = array_key_first($categoryItems);
            [$label, $href] = $categoryItems[$key];
            $isLinkActive = ($active !== '' && str_contains($href, $active));
            $activeClass = $isLinkActive ? ' active' : '';
            
            echo '<div class="nav-item-direct" data-tooltip="' . htmlspecialchars($label) . '">';
            echo '<a class="' . $activeClass . '" href="' . htmlspecialchars($href) . '">';
            echo '<i class="fas ' . htmlspecialchars($iconClass) . ' nav-icon"></i>';
            echo '<span class="nav-text">' . htmlspecialchars($label) . '</span>';
            echo '</a>';
            echo '</div>';
        } else {
            // Render Accordion Category
            echo '<div class="nav-category" data-category="' . htmlspecialchars($categoryName) . '" data-tooltip="' . htmlspecialchars($categoryName) . '">';
            echo '<button class="category-header">';
            echo '<div class="header-left">';
            echo '<i class="fas ' . htmlspecialchars($iconClass) . ' nav-icon"></i>';
            echo '<span class="nav-text">' . htmlspecialchars($categoryName) . '</span>';
            echo '</div>';
            echo '<i class="fas fa-chevron-down chevron-icon"></i>';
            echo '</button>';
            
            echo '<div class="category-submenu">';
            foreach ($categoryItems as $key => $item) {
                [$label, $href] = $item;
                $isLinkActive = ($active !== '' && str_contains($href, $active));
                $activeClass = $isLinkActive ? ' active' : '';
                $badge = str_contains(strtolower($label), 'notification') ? ' <span class="nav-badge" data-notification-count hidden>0</span>' : '';
                
                echo '<a class="submenu-link' . $activeClass . '" href="' . htmlspecialchars($href) . '">';
                echo htmlspecialchars($label) . $badge;
                echo '</a>';
            }
            echo '</div>'; // category-submenu
            echo '</div>'; // nav-category
        }
    }
    
    // Fallback: If there are any items that weren't assigned a category in the structure, show them in a General group
    $remainingItems = [];
    foreach ($menuItems as $key => $item) {
        if (!in_array($key, $processedKeys, true)) {
            $remainingItems[$key] = $item;
        }
    }
    
    if (!empty($remainingItems)) {
        echo '<div class="nav-category" data-category="General" data-tooltip="General">';
        echo '<button class="category-header">';
        echo '<div class="header-left">';
        echo '<i class="fas fa-circle-dot nav-icon"></i>';
        echo '<span class="nav-text">General</span>';
        echo '</div>';
        echo '<i class="fas fa-chevron-down chevron-icon"></i>';
        echo '</button>';
        echo '<div class="category-submenu">';
        foreach ($remainingItems as $key => $item) {
            [$label, $href] = $item;
            $isLinkActive = ($active !== '' && str_contains($href, $active));
            $activeClass = $isLinkActive ? ' active' : '';
            $badge = str_contains(strtolower($label), 'notification') ? ' <span class="nav-badge" data-notification-count hidden>0</span>' : '';
            
            echo '<a class="submenu-link' . $activeClass . '" href="' . htmlspecialchars($href) . '">';
            echo htmlspecialchars($label) . $badge;
            echo '</a>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    echo '</nav>';
    
    // JS Logic for Toggle, Accordion, State Persistence, and Search
    echo '<script>
(function () {
  var layout = document.querySelector(".erp-layout");
  var sidebar = document.getElementById("erp-sidebar");
  var toggleBtn = document.getElementById("sidebar-toggle");
  
  if (layout && sidebar && toggleBtn) {
    // 1. Restore Sidebar Toggle State
    var isCollapsed = localStorage.getItem("sidebar_collapsed") === "true";
    if (isCollapsed) {
      layout.classList.add("sidebar-collapsed");
      sidebar.classList.add("collapsed");
    }
    
    toggleBtn.addEventListener("click", function () {
      var collapsed = layout.classList.toggle("sidebar-collapsed");
      sidebar.classList.toggle("collapsed");
      localStorage.setItem("sidebar_collapsed", collapsed);
      
      if (collapsed) {
        document.querySelectorAll(".nav-category").forEach(function (c) {
          c.classList.remove("open");
        });
      } else {
        restoreAccordionState();
      }
    });
  }
  
  // 2. Accordion Logic
  var categories = document.querySelectorAll(".nav-category");
  categories.forEach(function (cat) {
    var header = cat.querySelector(".category-header");
    header.addEventListener("click", function (e) {
      e.preventDefault();
      if (sidebar.classList.contains("collapsed")) return;
      
      var isAlreadyOpen = cat.classList.contains("open");
      
      categories.forEach(function (c) {
        c.classList.remove("open");
      });
      
      if (!isAlreadyOpen) {
        cat.classList.add("open");
        localStorage.setItem("sidebar_active_category", cat.getAttribute("data-category"));
      } else {
        localStorage.removeItem("sidebar_active_category");
      }
    });
  });
  
  function restoreAccordionState() {
    if (sidebar.classList.contains("collapsed")) return;
    
    var activeCatName = localStorage.getItem("sidebar_active_category");
    if (activeCatName) {
      var activeCat = document.querySelector(".nav-category[data-category=\'" + activeCatName + "\']");
      if (activeCat) {
        activeCat.classList.add("open");
        return;
      }
    }
    
    var activeLink = document.querySelector(".submenu-link.active");
    if (activeLink) {
      var parentCat = activeLink.closest(".nav-category");
      if (parentCat) {
        parentCat.classList.add("open");
        return;
      }
    }
    
    var firstCat = document.querySelector(".nav-category");
    if (firstCat) {
      firstCat.classList.add("open");
    }
  }
  
  restoreAccordionState();
  
  // 3. Search Filter Logic
  var searchInput = document.getElementById("sidebar-search");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      var filter = searchInput.value.toLowerCase().trim();
      var directLinks = document.querySelectorAll(".nav-item-direct");
      var categoryGroups = document.querySelectorAll(".nav-category");
      
      if (filter === "") {
        directLinks.forEach(function (item) { item.style.display = ""; });
        categoryGroups.forEach(function (cat) {
          cat.style.display = "";
          cat.classList.remove("open");
          cat.querySelectorAll(".submenu-link").forEach(function (link) {
            link.style.display = "";
          });
        });
        restoreAccordionState();
        return;
      }
      
      directLinks.forEach(function (item) {
        var text = item.querySelector(".nav-text").textContent.toLowerCase();
        item.style.display = text.indexOf(filter) > -1 ? "" : "none";
      });
      
      categoryGroups.forEach(function (cat) {
        var hasVisibleLink = false;
        var submenuLinks = cat.querySelectorAll(".submenu-link");
        
        submenuLinks.forEach(function (link) {
          var text = link.textContent.toLowerCase();
          if (text.indexOf(filter) > -1) {
            link.style.display = "";
            hasVisibleLink = true;
          } else {
            link.style.display = "none";
          }
        });
        
        if (hasVisibleLink) {
          cat.style.display = "";
          cat.classList.add("open");
        } else {
          cat.style.display = "none";
          cat.classList.remove("open");
        }
      });
    });
  }

  // 4. Notification Polling
  var badge = document.querySelector("[data-notification-count]");
  if (badge) {
    function pollNotifications() {
      fetch("api/notifications_poll.php", {credentials: "same-origin"})
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
          if (!data || typeof data.unread_count === "undefined") return;
          badge.textContent = data.unread_count;
          badge.hidden = Number(data.unread_count) <= 0;
        })
        .catch(function () {});
    }
    pollNotifications();
    setInterval(pollNotifications, 15000);
  }
})();
</script>';
    echo '</aside>';
}
?>
