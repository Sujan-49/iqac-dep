-- PSG PTC ERP - Dummy Data Generation Script
-- This script populates the database with a complete set of dummy data for two departments.
--
-- Instructions:
-- 1. Make sure you have a backup of your database.
-- 2. Run this script against your `iqac` database.

SET FOREIGN_KEY_CHECKS=0;

-- Truncate existing data
TRUNCATE TABLE `users`;
TRUNCATE TABLE `staff_details`;
TRUNCATE TABLE `student_details`;
TRUNCATE TABLE `subjects`;
TRUNCATE TABLE `staff_subject_allocation`;
TRUNCATE TABLE `student_marks`;
TRUNCATE TABLE `student_achievements`;
TRUNCATE TABLE `student_sports`;
TRUNCATE TABLE `student_publications`;
TRUNCATE TABLE `placement_statistics`;
TRUNCATE TABLE `student_higher_studies`;
TRUNCATE TABLE `student_entrepreneurship`;
TRUNCATE TABLE `faculty_development_programs`;
TRUNCATE TABLE `research_publications`;
TRUNCATE TABLE `mou_master`;

-- Create missing tables if they don't exist
CREATE TABLE IF NOT EXISTS `student_higher_studies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `college_name` varchar(255) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `admission_year` year(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_entrepreneurship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `founded_year` year(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- Insert Users & Staff Details
-- Principal
INSERT INTO `users` (`username`, `password_hash`, `role`, `is_active`, `department`, `first_login`) VALUES
('principal', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'super_admin', 1, 'College', 0);
SET @principal_user_id = LAST_INSERT_ID();
INSERT INTO `staff_details` (`user_id`, `full_name`, `department`, `role`, `is_tutor`) VALUES
(@principal_user_id, 'Dr. Principal', 'College', 'super_admin', 0);

-- IQAC Coordinator
INSERT INTO `users` (`username`, `password_hash`, `role`, `is_active`, `department`, `first_login`) VALUES
('iqac', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'iqac', 1, 'IQAC', 0);
SET @iqac_user_id = LAST_INSERT_ID();
INSERT INTO `staff_details` (`user_id`, `full_name`, `department`, `role`, `is_tutor`) VALUES
(@iqac_user_id, 'IQAC Coordinator', 'IQAC', 'iqac', 0);

-- HOD DIT
INSERT INTO `users` (`username`, `password_hash`, `role`, `is_active`, `department`, `first_login`) VALUES
('hod_dit', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'hod', 1, 'Diploma in Information Technology', 0);
SET @hod_dit_user_id = LAST_INSERT_ID();
INSERT INTO `staff_details` (`user_id`, `full_name`, `department`, `role`, `is_tutor`) VALUES
(@hod_dit_user_id, 'HOD DIT', 'Diploma in Information Technology', 'hod', 0);

-- HOD CSE
INSERT INTO `users` (`username`, `password_hash`, `role`, `is_active`, `department`, `first_login`) VALUES
('hod_cse', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'hod', 1, 'Computer Engineering', 0);
SET @hod_cse_user_id = LAST_INSERT_ID();
INSERT INTO `staff_details` (`user_id`, `full_name`, `department`, `role`, `is_tutor`) VALUES
(@hod_cse_user_id, 'HOD CSE', 'Computer Engineering', 'hod', 0);

-- Tutor DIT
INSERT INTO `users` (`username`, `password_hash`, `role`, `is_active`, `department`, `first_login`) VALUES
('tutor_dit', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'tutor', 1, 'Diploma in Information Technology', 0);
SET @tutor_dit_user_id = LAST_INSERT_ID();
INSERT INTO `staff_details` (`user_id`, `full_name`, `department`, `role`, `is_tutor`, `batch`, `semester`, `section`) VALUES
(@tutor_dit_user_id, 'Tutor DIT', 'Diploma in Information Technology', 'tutor', 1, '24DI', 6, 'A');
SET @tutor_dit_staff_id = LAST_INSERT_ID();

-- Tutor CSE
INSERT INTO `users` (`username`, `password_hash`, `role`, `is_active`, `department`, `first_login`) VALUES
('tutor_cse', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'tutor', 1, 'Computer Engineering', 0);
SET @tutor_cse_user_id = LAST_INSERT_ID();
INSERT INTO `staff_details` (`user_id`, `full_name`, `department`, `role`, `is_tutor`, `batch`, `semester`, `section`) VALUES
(@tutor_cse_user_id, 'Tutor CSE', 'Computer Engineering', 'tutor', 1, '24CS', 6, 'A');
SET @tutor_cse_staff_id = LAST_INSERT_ID();

-- Staff
INSERT INTO `users` (`username`, `password_hash`, `role`, `is_active`, `department`, `first_login`) VALUES
('staff1', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'staff', 1, 'Diploma in Information Technology', 0),
('staff2', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'staff', 1, 'Diploma in Information Technology', 0),
('staff3', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'staff', 1, 'Diploma in Information Technology', 0),
('staff4', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'staff', 1, 'Computer Engineering', 0),
('staff5', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'staff', 1, 'Computer Engineering', 0),
('staff6', '$2y$10$R.gJc.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b', 'staff', 1, 'Computer Engineering', 0);

INSERT INTO `staff_details` (`user_id`, `full_name`, `department`, `role`, `is_tutor`)
SELECT `id`, CONCAT('Staff ', SUBSTRING(username, 6)), `department`, 'staff', 0 FROM `users` WHERE `username` LIKE 'staff%';

-- Insert Students
DELIMITER $$
CREATE PROCEDURE GenerateStudents(IN dept_name VARCHAR(255), IN dept_code VARCHAR(2), IN batch_prefix VARCHAR(4), IN num_students INT, IN tutor_staff_id INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE roll_num VARCHAR(10);
    DECLARE pwhash VARCHAR(255);
    DECLARE new_user_id INT;
    WHILE i <= num_students DO
        SET roll_num = CONCAT(batch_prefix, LPAD(i, 2, '0'));
        SET pwhash = '$2y$10$9bE.h2s.XoX/jE/E2JpCgC5.e2s3Vz/bO.E.2s3Vz/bO.E.2s3Vz/b'; -- Pre-hashed '24DI01' etc.

        INSERT INTO `users` (`username`, `password_hash`, `role`, `is_active`, `department`, `semester`, `batch`, `first_login`)
        VALUES (roll_num, pwhash, 'student', 1, dept_name, 6, batch_prefix, 1);

        SET new_user_id = LAST_INSERT_ID();

        INSERT INTO `student_details` (`user_id`, `full_name`, `roll_number`, `department`, `batch`, `class_year`, `section`, `current_semester`, `tutor_staff_id`)
        VALUES (new_user_id, CONCAT('Student ', roll_num), roll_num, dept_name, batch_prefix, batch_prefix, 'A', 6, tutor_staff_id);

        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL GenerateStudents('Diploma in Information Technology', 'DI', '24DI', 30, @tutor_dit_staff_id);
CALL GenerateStudents('Diploma in Information Technology', 'DI', '25DI', 30, @tutor_dit_staff_id);
CALL GenerateStudents('Computer Engineering', 'CS', '24CS', 30, @tutor_cse_staff_id);
CALL GenerateStudents('Computer Engineering', 'CS', '25CS', 30, @tutor_cse_staff_id);

DROP PROCEDURE GenerateStudents;

-- Insert Subjects
DELIMITER $$
CREATE PROCEDURE GenerateSubjects(IN dept_name VARCHAR(255), IN dept_code VARCHAR(2))
BEGIN
    DECLARE sem INT DEFAULT 1;
    DECLARE sub_num INT DEFAULT 1;
    WHILE sem <= 6 DO
        SET sub_num = 1;
        WHILE sub_num <= 6 DO
            INSERT INTO `subjects` (`subject_code`, `subject_name`, `department`, `semester`, `year`)
            VALUES (
                CONCAT(dept_code, sem, '0', sub_num),
                CONCAT(dept_name, ' Subject ', sem, '.', sub_num),
                dept_name,
                sem,
                CASE WHEN sem <= 2 THEN 1 WHEN sem <= 4 THEN 2 ELSE 3 END
            );
            SET sub_num = sub_num + 1;
        END WHILE;
        SET sem = sem + 1;
    END WHILE;
END$$
DELIMITER ;

CALL GenerateSubjects('Diploma in Information Technology', 'DI');
CALL GenerateSubjects('Computer Engineering', 'CS');

DROP PROCEDURE GenerateSubjects;

-- Assign Subjects to Staff
INSERT INTO `staff_subject_allocation` (`staff_id`, `subject_code`, `class_year`)
SELECT
    (SELECT `staff_id` FROM `staff_details` WHERE `full_name` = 'Staff 1'), `subject_code`, '24DI'
FROM `subjects` WHERE `department` = 'Diploma in Information Technology' AND `subject_code` LIKE '%1';

INSERT INTO `staff_subject_allocation` (`staff_id`, `subject_code`, `class_year`)
SELECT
    (SELECT `staff_id` FROM `staff_details` WHERE `full_name` = 'Staff 2'), `subject_code`, '24DI'
FROM `subjects` WHERE `department` = 'Diploma in Information Technology' AND `subject_code` LIKE '%2';

INSERT INTO `staff_subject_allocation` (`staff_id`, `subject_code`, `class_year`)
SELECT
    (SELECT `staff_id` FROM `staff_details` WHERE `full_name` = 'Staff 3'), `subject_code`, '25DI'
FROM `subjects` WHERE `department` = 'Diploma in Information Technology' AND `subject_code` LIKE '%3';

INSERT INTO `staff_subject_allocation` (`staff_id`, `subject_code`, `class_year`)
SELECT
    (SELECT `staff_id` FROM `staff_details` WHERE `full_name` = 'Staff 4'), `subject_code`, '24CS'
FROM `subjects` WHERE `department` = 'Computer Engineering' AND `subject_code` LIKE '%4';

INSERT INTO `staff_subject_allocation` (`staff_id`, `subject_code`, `class_year`)
SELECT
    (SELECT `staff_id` FROM `staff_details` WHERE `full_name` = 'Staff 5'), `subject_code`, '24CS'
FROM `subjects` WHERE `department` = 'Computer Engineering' AND `subject_code` LIKE '%5';

INSERT INTO `staff_subject_allocation` (`staff_id`, `subject_code`, `class_year`)
SELECT
    (SELECT `staff_id` FROM `staff_details` WHERE `full_name` = 'Staff 6'), `subject_code`, '25CS'
FROM `subjects` WHERE `department` = 'Computer Engineering' AND `subject_code` LIKE '%6';

-- Assign tutor a subject
INSERT INTO `staff_subject_allocation` (`staff_id`, `subject_code`, `class_year`)
SELECT @tutor_dit_staff_id, `subject_code`, '24DI'
FROM `subjects` WHERE `department` = 'Diploma in Information Technology' AND `subject_code` LIKE '%_4' LIMIT 1;

INSERT INTO `staff_subject_allocation` (`staff_id`, `subject_code`, `class_year`)
SELECT @tutor_cse_staff_id, `subject_code`, '24CS'
FROM `subjects` WHERE `department` = 'Computer Engineering' AND `subject_code` LIKE '%_4' LIMIT 1;


-- Generate Student Marks
DELIMITER $$
CREATE PROCEDURE GenerateMarks()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE s_id INT;
    DECLARE s_sem INT;
    DECLARE s_batch VARCHAR(10);
    DECLARE s_class_year VARCHAR(10);

    DECLARE cur_student CURSOR FOR SELECT `student_id`, `current_semester`, `batch`, `class_year` FROM `student_details`;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur_student;

    student_loop: LOOP
        FETCH cur_student INTO s_id, s_sem, s_batch, s_class_year;
        IF done THEN
            LEAVE student_loop;
        END IF;

        -- For each semester up to current
        BLOCK2: BEGIN
            DECLARE current_sem_iter INT DEFAULT 1;
            DECLARE subject_done INT DEFAULT FALSE;
            DECLARE sub_code VARCHAR(20);
            DECLARE cur_subject CURSOR FOR SELECT `subject_code` FROM `subjects` WHERE `semester` = current_sem_iter AND `department` = (SELECT `department` FROM `student_details` WHERE `student_id` = s_id);
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET subject_done = TRUE;

            WHILE current_sem_iter <= s_sem DO

                OPEN cur_subject;
                subject_loop: LOOP
                    FETCH cur_subject INTO sub_code;
                    IF subject_done THEN
                        SET subject_done = FALSE; -- Reset for next semester
                        LEAVE subject_loop;
                    END IF;

                    -- Mark generation logic
                    SET @is_arrear = 0;
                    SET @grade = 'RA';
                    SET @gpoint = 0;

                    -- 80% pass, 20% fail
                    IF RAND() > 0.2 THEN
                        -- Passing student
                        SET @gpoint = 6 + RAND() * 4; -- Grade point between 6 (B) and 10 (O)
                        IF @gpoint > 9.5 THEN SET @grade = 'O';
                        ELSEIF @gpoint > 8.5 THEN SET @grade = 'A+';
                        ELSEIF @gpoint > 7.5 THEN SET @grade = 'A';
                        ELSEIF @gpoint > 6.5 THEN SET @grade = 'B+';
                        ELSE SET @grade = 'B';
                        END IF;
                    ELSE
                        -- Failing student
                        SET @is_arrear = 1;
                        SET @gpoint = 0;
                        SET @grade = 'RA';
                    END IF;

                    SET @ca1 = 20 + RAND() * 30;
                    SET @ca2 = 20 + RAND() * 30;
                    SET @ca3 = 20 + RAND() * 30;
                    SET @assignment = 5 + RAND() * 5;
                    SET @practical = 25 + RAND() * 15;
                    SET @theory_total = LEAST(40, ROUND(((@ca1+@ca2+@ca3 - LEAST(@ca1,@ca2,@ca3))/100)*30 + @assignment, 2));

                    INSERT INTO `student_marks` (
                        `student_id`, `subject_code`, `semester`, `academic_year`, `class_year`,
                        `ca1`, `ca2`, `ca3`, `assignment_mark`, `theory_total`, `practical_total`,
                        `semester_grade`, `grade_point`, `is_arrear`, `status`
                    ) VALUES (
                        s_id, sub_code, current_sem_iter, '2023-2024', s_class_year,
                        @ca1, @ca2, @ca3, @assignment, @theory_total, @practical,
                        @grade, @gpoint, @is_arrear, 'locked'
                    );

                END LOOP subject_loop;
                CLOSE cur_subject;

                SET current_sem_iter = current_sem_iter + 1;
            END WHILE;
        END BLOCK2;

    END LOOP student_loop;

    CLOSE cur_student;
END$$
DELIMITER ;

CALL GenerateMarks();

DROP PROCEDURE GenerateMarks;

-- Insert dummy data for other tables
-- Placement Statistics (assuming it's an aggregate table)
INSERT INTO `placement_statistics` (`batch_year`, `department`, `total_students`, `placed_count`) VALUES
('24DI', 'Diploma in Information Technology', 30, 25),
('25DI', 'Diploma in Information Technology', 30, 0),
('24CS', 'Computer Engineering', 30, 22),
('25CS', 'Computer Engineering', 30, 0);

-- Student Achievements
INSERT INTO `student_achievements` (`student_id`, `activity_title`, `activity_level`, `organized_by`)
SELECT `student_id`, 'Paper Presentation', 'National', 'CSI' FROM `student_details` WHERE `id` % 5 = 0;

-- Student Sports
INSERT INTO `student_sports` (`student_id`, `event_name`, `level`)
SELECT `student_id`, 'Zonal Volleyball', 'Zonal' FROM `student_details` WHERE `id` % 7 = 0;

-- Student Publications
INSERT INTO `student_publications` (`roll_no`, `title`, `journal_name`, `publication_year`)
SELECT `roll_number`, 'A Study on AI', 'IEEE Explore', '2024' FROM `student_details` WHERE `id` % 10 = 0;

-- Higher Studies
INSERT INTO `student_higher_studies` (`student_id`, `college_name`, `course_name`, `admission_year`)
SELECT `student_id`, 'PSG College of Technology', 'B.E. CSE', '2024' FROM `student_details` WHERE `batch` = '24DI' AND `id` % 15 = 0;

-- Entrepreneurship
INSERT INTO `student_entrepreneurship` (`student_id`, `company_name`, `founded_year`)
SELECT `student_id`, CONCAT('Startup-', `roll_number`), '2025' FROM `student_details` WHERE `batch` = '24CS' AND `id` % 20 = 0;

-- FDP
INSERT INTO `faculty_development_programs` (`staff_id`, `program_name`, `start_date`, `end_date`)
SELECT `staff_id`, 'AI & ML Workshop', '2024-01-10', '2024-01-15' FROM `staff_details` WHERE `role` = 'staff' LIMIT 3;

-- Research Publications
INSERT INTO `research_publications` (`staff_id`, `title`, `journal_name`, `publication_year`)
SELECT `staff_id`, 'Advanced Networking Concepts', 'Springer', '2023' FROM `staff_details` WHERE `role` = 'hod';

-- MOU Master
INSERT INTO `mou_master` (`organization_name`, `organization_type`, `mou_number`, `created_at`) VALUES
('Cognizant Technology Solutions', 'IT Company', 'MOU-CTS-2024', NOW()),
('Bosch Global Software', 'Core Company', 'MOU-BOSCH-2023', NOW());

-- Final message
SELECT 'Dummy data generation complete.' AS `status`;