<?php
declare(strict_types=1);

function erp_form_catalog(): array
{
    return [
        'student' => [
            'personal_update' => ['Personal Information Update Request', ['Phone number change', 'Address change', 'Parent mobile update', 'Email update'], true],
            'scholarship' => ['Scholarship Application', ['Scholarship Type', 'Annual Income', 'Remarks'], true],
            'placement_registration' => ['Placement Registration', ['Company', 'Skill Set', 'CGPA', 'Certifications', 'Status'], true],
            'internship_registration' => ['Internship Registration', ['Company Name', 'Duration', 'Location', 'Guide Name'], true],
            'club_registration' => ['Club / Association Registration', ['Club Name', 'Role', 'Experience', 'Achievements'], false],
            'event_participation' => ['Event Participation Form', ['Event Name', 'College', 'Date', 'Type'], true],
            'alumni_interaction' => ['Alumni Interaction Form', ['Event', 'Speaker', 'Feedback'], false],
            'mentor_meeting' => ['Mentor Meeting Form', ['Issue Type', 'Remarks', 'Meeting Date', 'Status'], false],
            'lab_equipment_issue' => ['Lab Equipment Issue Report', ['Lab', 'Equipment', 'Issue Description'], true],
            'student_feedback' => ['Student Feedback Form', ['Faculty', 'Subject', 'Rating', 'Comments'], false],
        ],
        'staff' => [
            'faculty_achievement' => ['Faculty Achievement Entry', ['Conference', 'Workshop', 'Patent', 'Publication', 'Award'], true],
            'fdp_entry' => ['FDP Entry Form', ['Program', 'Institute', 'Duration'], true],
            'industry_visit_report' => ['Industry Visit Report', ['Company', 'Students Count', 'Faculty Count'], true],
            'department_activity' => ['Department Activity Form', ['Seminar', 'Workshop', 'Guest Lecture', 'Hackathon'], true],
            'research_activity' => ['Research Activity Form', ['Publication', 'Patent', 'Project', 'Funding'], true],
            'placement_coordination' => ['Placement Coordination Form', ['Company', 'Students Selected', 'Package', 'Department'], false],
            'student_counseling' => ['Student Counseling Record', ['Student', 'Issue', 'Action Taken', 'Follow-up Date'], false],
            'lab_maintenance' => ['Lab Maintenance Request', ['Equipment', 'Issue', 'Priority', 'Remarks'], true],
        ],
        'admin' => [
            'student_promotion' => ['Student Promotion Form', ['Student / Batch', 'From Semester', 'To Semester', 'Remarks'], false],
            'semester_creation' => ['Semester Creation Form', ['Semester', 'Academic Year', 'Status'], false],
            'batch_creation' => ['Batch Creation Form', ['Batch', 'Department', 'Year', 'Section'], false],
            'tutor_assignment' => ['Tutor Assignment Form', ['Tutor', 'Department', 'Batch', 'Section'], false],
            'subject_assignment' => ['Subject Assignment Form', ['Teacher', 'Department', 'Semester', 'Batch', 'Subject'], false],
            'bulk_student_import' => ['Bulk Student Import Form', ['Import Type', 'Department', 'Batch', 'Remarks'], true],
            'bulk_staff_import' => ['Bulk Staff Import Form', ['Import Type', 'Department', 'Remarks'], true],
            'department_creation' => ['Department Creation Form', ['Department Name', 'Code', 'HOD'], false],
            'notification_creation' => ['Notification Creation Form', ['Audience', 'Title', 'Message'], false],
            'erp_announcement' => ['ERP Announcement Form', ['Audience', 'Title', 'Announcement'], false],
        ],
    ];
}

function erp_form_allowed_scopes(string $role): array
{
    return match (iqac_normalize_role($role)) {
        'student' => ['student'],
        'staff' => ['staff'],
        'tutor' => ['student', 'staff'],
        'hod', 'iqac', 'admin', 'super_admin' => ['student', 'staff', 'admin'],
        default => [],
    };
}
?>
