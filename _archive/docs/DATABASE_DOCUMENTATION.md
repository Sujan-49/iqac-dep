# DATABASE DOCUMENTATION & ER DIAGRAM

## Complete Database Schema

### DATABASE 1: `iqac`

#### Table Specifications

##### **students**
```
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    age INT,
    dob DATE,
    gender VARCHAR(10),
    father_name VARCHAR(100),
    mother_name VARCHAR(100),
    address TEXT,
    father_occupation VARCHAR(100),
    mother_occupation VARCHAR(100),
    father_income DECIMAL(15,2),
    admission_date DATE,
    batch_year VARCHAR(20),
    student_mobile VARCHAR(15),
    email_id VARCHAR(255),
    father_mobile VARCHAR(15),
    student_photo VARCHAR(255),
    father_photo VARCHAR(255),
    mother_photo VARCHAR(255),
    entry_type VARCHAR(50),
    management_quota VARCHAR(50),
    counselling_quota VARCHAR(50),
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Relationships:**
- ← One-to-Many → academic_details
- ← One-to-Many → achievement_details
- ← One-to-Many → non_academic_details
- ← One-to-Many → semester_marks
- ← One-to-Many → higher_studies
- ← One-to-Many → student_projects
- ← One-to-Many → industry_visits
- ← One-to-Many → student_participation
- ← One-to-Many → student_sports

---

##### **academic_details**
```
CREATE TABLE academic_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    institution_type ENUM('school','college') NOT NULL,
    institution_name VARCHAR(50),
    total_marks INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    year_completed VARCHAR(4) NOT NULL,
    roll_number VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
```

**Indexes:**
- FK: student_id

**Notes:**
- CASCADE DELETE when student is deleted
- Auto-update timestamp on modification
- Tracks prior education (school/college)

---

##### **achievement_details**
```
CREATE TABLE achievement_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    achievement_title VARCHAR(150),
    description TEXT,
    date_awarded DATE,
    certificate_path VARCHAR(255),
    photo_path VARCHAR(255),
    achievement_level VARCHAR(50),
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

**Achievement Levels:**
- National Level
- State Level
- District Level
- Local Level

---

##### **non_academic_details**
```
CREATE TABLE non_academic_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    organization_name VARCHAR(255) NOT NULL,
    role VARCHAR(100) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

**Activity Types Examples:**
- Club Participation
- Volunteering
- Cultural Programs
- Sports Activities (organized)
- Community Service

---

##### **semester_marks**
```
CREATE TABLE semester_marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    semester_number INT NOT NULL,
    register_number VARCHAR(50),
    subjects LONGTEXT JSON,
    pass_fail VARCHAR(10),
    grade_or_avg VARCHAR(10),
    mark_sheet_path VARCHAR(255),
    total_marks INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

**JSON Structure for subjects:**
```json
{
  "subjects": [
    {
      "code": "C101",
      "name": "Programming",
      "marks": 85,
      "pass_fail": "pass"
    },
    ...
  ]
}
```

---

##### **achievement_mark** (Alternative marks table)
```
CREATE TABLE achievement_mark (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    semester_number INT NOT NULL,
    register_number VARCHAR(50),
    subject1_code VARCHAR(20), subject1_name VARCHAR(100),
    subject1_marks INT, subject1_pass_fail VARCHAR(10),
    subject2_code VARCHAR(20), subject2_name VARCHAR(100),
    subject2_marks INT, subject2_pass_fail VARCHAR(10),
    ... (continues for subjects 3-9)
    subject9_code VARCHAR(20), subject9_name VARCHAR(100),
    subject9_marks INT, subject9_pass_fail VARCHAR(10),
    grade_or_avg VARCHAR(10),
    mark_sheet_path VARCHAR(255),
    total_marks INT,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

**Supports:** 9 subjects per semester

---

##### **higher_studies**
```
CREATE TABLE higher_studies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(100),
    previous_roll_no VARCHAR(50),
    current_roll_no VARCHAR(50),
    college_name VARCHAR(255),
    quota_type VARCHAR(50),
    percentage DECIMAL(5,2),
    file_path VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

**Purpose:** Track students pursuing further education

---

##### **student_projects**
```
CREATE TABLE student_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    project_title VARCHAR(255),
    project_type VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

**Project Types:**
- Academic Project
- Internship Project
- Research Project
- Industry Project

---

##### **student_publications**
```
CREATE TABLE student_publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(50),
    student_name VARCHAR(100),
    paper_title VARCHAR(255),
    journal_conference VARCHAR(255),
    issn_isbn VARCHAR(50),
    date_of_publication DATE
);
```

**Purpose:** Track research publications

---

##### **industry_visits**
```
CREATE TABLE industry_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    roll_number VARCHAR(50),
    course_name VARCHAR(255),
    subject_name VARCHAR(255),
    company_name VARCHAR(255),
    visit_duration VARCHAR(50),
    evidence_file VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

---

##### **student_participation**
```
CREATE TABLE student_participation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255),
    event_place VARCHAR(255),
    participation_date DATE,
    level VARCHAR(50),
    certificate_copy VARCHAR(255)
);
```

**Levels:**
- Local
- State
- National
- International

---

##### **student_sports**
```
CREATE TABLE student_sports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    sport_name VARCHAR(100),
    level VARCHAR(50),
    position VARCHAR(100),
    year_participated VARCHAR(4),
    sports_certificate VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

---

##### **curriculum_gaps**
```
CREATE TABLE curriculum_gaps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50),
    additional_content TEXT,
    action_taken TEXT,
    date DATE,
    resource_person VARCHAR(255),
    mode VARCHAR(100),
    no_of_students INT,
    relevance_to_POs_PSOs TEXT
);
```

**Purpose:** Track curriculum improvements and gaps

---

##### **technical_events**
```
CREATE TABLE technical_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    organized_by VARCHAR(255),
    event_date DATE NOT NULL,
    number_of_students_participated INT,
    description TEXT,
    file_upload VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

##### **semester_details**
```
CREATE TABLE semester_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester_name VARCHAR(50),
    year VARCHAR(10)
);
```

**Examples:**
- id=1, semester_name="Semester 1", year="2023-24"
- id=2, semester_name="Semester 2", year="2023-24"

---

##### **staff_details**
```
CREATE TABLE staff_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    designation VARCHAR(50),
    department VARCHAR(50),
    address TEXT,
    qualification VARCHAR(20),
    mobile VARCHAR(20),
    email VARCHAR(50),
    picture_path VARCHAR(255)
);
```

---

##### **staff_academic_details**
```
CREATE TABLE staff_academic_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    degree VARCHAR(100),
    university VARCHAR(255),
    year_of_passing INT,
    percentage DECIMAL(5,2),
    FOREIGN KEY (staff_id) REFERENCES staff_details(id)
);
```

---

##### **staff_non_academic_details**
```
CREATE TABLE staff_non_academic_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    activity_type VARCHAR(100),
    organization_name VARCHAR(255),
    role VARCHAR(100),
    duration VARCHAR(50),
    remarks TEXT,
    FOREIGN KEY (staff_id) REFERENCES staff_details(id)
);
```

---

##### **staff_work_experience**
```
CREATE TABLE staff_work_experience (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    organization VARCHAR(255),
    position VARCHAR(100),
    duration VARCHAR(50),
    FOREIGN KEY (staff_id) REFERENCES staff_details(id)
);
```

---

##### **users** (Referenced but not explicitly defined)
```
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','staff','admin','tutor','hod','super_admin'),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### DATABASE 2: `mou`

#### Table Specifications

##### **mou_files**
```
CREATE TABLE mou_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(255) NOT NULL,
    year VARCHAR(10) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    image VARCHAR(255),
    company VARCHAR(255),
    status VARCHAR(255),
    staff_id VARCHAR(50),
    sign_date DATE
);
```

**Status Values:**
- Pending
- In Progress
- Completed

---

##### **institutions**
```
CREATE TABLE institutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    type ENUM('school','college')
);
```

---

##### **staff**
```
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(50) NOT NULL,
    position VARCHAR(50) NOT NULL,
    mou_file_id INT,
    password VARCHAR(255),
    FOREIGN KEY (mou_file_id) REFERENCES mou_files(id)
);
```

---

##### **sachievement_details**
```
CREATE TABLE sachievement_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT,
    achievement_title VARCHAR(255),
    description TEXT,
    date_awarded DATE,
    FOREIGN KEY (staff_id) REFERENCES staff_details(id)
);
```

---

##### **snon_academic_details**
```
CREATE TABLE snon_academic_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT,
    activity_type VARCHAR(100),
    organization_name VARCHAR(255),
    role VARCHAR(100),
    duration VARCHAR(50),
    remarks TEXT,
    FOREIGN KEY (staff_id) REFERENCES staff_details(id)
);
```

---

## VISUAL ER DIAGRAM

```
╔════════════════════════════════════════════════════════════════════════════╗
║                         DATABASE: iqac                                     ║
╚════════════════════════════════════════════════════════════════════════════╝

                         ┌─────────────────────────────┐
                         │       students              │
                         ├─────────────────────────────┤
                         │ PK: id                      │
                         │ first_name                  │
                         │ last_name                   │
                         │ email_id                    │
                         │ batch_year                  │
                         │ admission_date              │
                         │ student_mobile              │
                         │ student_photo               │
                         │ father_income               │
                         └──────────┬──────────────────┘
                                    │
                 ┌──────────────────┼──────────────────┬────────────────┬────────────────┐
                 │                  │                  │                │                │
                 │ 1:M              │ 1:M              │ 1:M            │ 1:M            │
                 │                  │                  │                │                │
    ┌────────────▼──────────┐  ┌───▼──────────────┐  ┌─▼──────────────┐ ┌─▼──────────────┐
    │ academic_details      │  │ achievement_     │  │ non_academic_  │ │ semester_      │
    ├───────────────────────┤  │ details          │  │ details        │ │ marks          │
    │ PK: id                │  ├──────────────────┤  ├────────────────┤ ├────────────────┤
    │ FK: student_id        │  │ PK: id           │  │ PK: id         │ │ PK: id         │
    │ institution_type      │  │ FK: student_id   │  │ FK: student_id │ │ FK: student_id │
    │ total_marks           │  │ achievement_     │  │ activity_type  │ │ semester_      │
    │ percentage            │  │ title            │  │ organization   │ │ number         │
    │ year_completed        │  │ description      │  │ role           │ │ subjects (JSON)│
    │ roll_number           │  │ date_awarded     │  │ duration       │ │ pass_fail      │
    │ institution_name      │  │ certificate_path │  │ remarks        │ │ total_marks    │
    └───────────────────────┘  │ achievement_     │  └────────────────┘ └────────────────┘
                                │ level            │
                                └──────────────────┘

    ┌─────────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌────────────────┐
    │ higher_studies  │  │ student_projects │  │ student_          │  │ industry_      │
    ├─────────────────┤  ├──────────────────┤  │ publications      │  │ visits         │
    │ PK: id          │  │ PK: id           │  ├──────────────────┤  ├────────────────┤
    │ FK: student_id  │  │ FK: student_id   │  │ PK: id           │  │ PK: id         │
    │ college_name    │  │ project_title    │  │ roll_no          │  │ FK: student_id │
    │ quota_type      │  │ project_type     │  │ student_name     │  │ company_name   │
    │ percentage      │  │ remarks          │  │ paper_title      │  │ visit_duration │
    │ file_path       │  └──────────────────┘  │ journal_conf     │  │ evidence_file  │
    └─────────────────┘                        │ issn_isbn        │  └────────────────┘
                                               │ date_of_pub      │
                                               └──────────────────┘

    ┌──────────────────┐  ┌───────────────────┐  ┌──────────────────┐
    │ student_         │  │ student_          │  │ curriculum_      │
    │ participation    │  │ sports            │  │ gaps             │
    ├──────────────────┤  ├───────────────────┤  ├──────────────────┤
    │ PK: id           │  │ PK: id            │  │ PK: id           │
    │ event_name       │  │ FK: student_id    │  │ course_code      │
    │ event_place      │  │ sport_name        │  │ additional_      │
    │ participation_   │  │ level             │  │ content          │
    │ date             │  │ position          │  │ action_taken     │
    │ level            │  │ year_participated │  │ date             │
    │ certificate_copy │  │ sports_cert       │  │ resource_person  │
    └──────────────────┘  └───────────────────┘  └──────────────────┘

    ┌──────────────────┐  ┌──────────────────────┐  ┌────────────────┐
    │ technical_events │  │ semester_details     │  │ achievement_   │
    ├──────────────────┤  ├──────────────────────┤  │ mark           │
    │ PK: id           │  │ PK: id               │  ├────────────────┤
    │ event_name       │  │ semester_name        │  │ PK: id         │
    │ organized_by     │  │ year                 │  │ FK: student_id │
    │ event_date       │  └──────────────────────┘  │ subject1-9     │
    │ participants     │                            │ (code,name,    │
    │ description      │                            │  marks,pass)   │
    │ file_upload      │                            │ grade_or_avg   │
    └──────────────────┘                            │ total_marks    │
                                                    └────────────────┘

    ┌─────────────────────┐  ┌──────────────────────┐  ┌─────────────────────┐
    │ staff_details       │  │ staff_academic_      │  │ staff_non_academic_ │
    ├─────────────────────┤  │ details              │  │ details             │
    │ PK: id              │  ├──────────────────────┤  ├─────────────────────┤
    │ first_name          │  │ PK: id               │  │ PK: id              │
    │ last_name           │  │ FK: staff_id         │  │ FK: staff_id        │
    │ designation         │  │ degree               │  │ activity_type       │
    │ department          │  │ university           │  │ organization_name   │
    │ address             │  │ year_of_passing      │  │ role                │
    │ qualification       │  │ percentage           │  │ duration            │
    │ mobile              │  └──────────────────────┘  │ remarks             │
    │ email               │                            └─────────────────────┘
    │ picture_path        │
    └─────────────────────┘
           │
           │ 1:M
           │
    ┌──────▼─────────────────┐
    │ staff_work_experience  │
    ├────────────────────────┤
    │ PK: id                 │
    │ FK: staff_id           │
    │ organization           │
    │ position               │
    │ duration               │
    └────────────────────────┘

╔════════════════════════════════════════════════════════════════════════════╗
║                         DATABASE: mou                                      ║
╚════════════════════════════════════════════════════════════════════════════╝

                         ┌─────────────────────────────┐
                         │    mou_files                │
                         ├─────────────────────────────┤
                         │ PK: id                      │
                         │ department                  │
                         │ year                        │
                         │ company                     │
                         │ filepath                    │
                         │ image                       │
                         │ status                      │
                         │ staff_id                    │
                         │ sign_date                   │
                         │ upload_date                 │
                         └──────────┬──────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
                    │ FK            │ FK            │
                    │               │               │
    ┌───────────────▼──────┐  ┌─────▼──────────┐  ┌──▼──────────────────┐
    │ staff                │  │ institutions   │  │ sachievement_      │
    ├──────────────────────┤  ├────────────────┤  │ details            │
    │ PK: id               │  │ PK: id         │  ├────────────────────┤
    │ name                 │  │ name           │  │ PK: id             │
    │ email (UNIQUE)       │  │ type (enum)    │  │ FK: staff_id       │
    │ department           │  └────────────────┘  │ achievement_title  │
    │ position             │                      │ description        │
    │ mou_file_id (FK)     │                      │ date_awarded       │
    │ password             │                      └────────────────────┘
    └──────────────────────┘
                │
                │ FK
                │
    ┌───────────▼──────────────────┐
    │ snon_academic_details        │
    ├──────────────────────────────┤
    │ PK: id                       │
    │ FK: staff_id                 │
    │ activity_type                │
    │ organization_name            │
    │ role                         │
    │ duration                     │
    │ remarks                      │
    └──────────────────────────────┘

```

## Key Relationships Summary

### One-to-Many (1:M)
- students → academic_details
- students → achievement_details
- students → non_academic_details
- students → semester_marks
- students → higher_studies
- students → student_projects
- students → industry_visits
- students → student_participation
- students → student_sports
- staff_details → staff_academic_details
- staff_details → staff_non_academic_details
- staff_details → staff_work_experience
- mou_files → staff

### Foreign Key Constraints
- **ON DELETE CASCADE:** academic_details → students (deleting student removes academic records)
- **ON DELETE SET NULL:** Most other relationships

---

## Data Integrity Rules

1. **Student Records:**
   - Email should be unique across system
   - Student mobile should follow phone format
   - Batch year must be numeric (1, 2, 3, etc.)

2. **Marks Entry:**
   - Total marks calculated from subjects sum
   - Pass/fail status must be binary (pass/fail)
   - Percentage should be 0-100

3. **File Paths:**
   - All file paths relative to web root
   - Images must be JPEG/PNG
   - Documents must be PDF/DOC format

4. **Status Fields:**
   - MOU status: Pending, In Progress, Completed
   - Achievement level: National, State, District, Local
   - Gender: M/F/Other (if implemented)

---

## Query Examples

### Find All Students with Achievements
```sql
SELECT s.first_name, s.last_name, a.achievement_title, a.date_awarded
FROM students s
LEFT JOIN achievement_details a ON s.id = a.student_id
ORDER BY s.last_name;
```

### Get Academic Performance by Batch Year
```sql
SELECT 
    s.batch_year,
    COUNT(DISTINCT s.id) AS total_students,
    AVG(ad.percentage) AS avg_percentage,
    MAX(ad.percentage) AS max_percentage,
    MIN(ad.percentage) AS min_percentage
FROM students s
LEFT JOIN academic_details ad ON s.id = ad.student_id
GROUP BY s.batch_year;
```

### Calculate Semester Success Rate
```sql
SELECT 
    s.batch_year,
    sm.semester_number,
    COUNT(DISTINCT s.id) AS total_students,
    SUM(CASE WHEN sm.pass_fail = 'pass' THEN 1 ELSE 0 END) AS passed_students,
    ROUND(SUM(CASE WHEN sm.pass_fail = 'pass' THEN 1 ELSE 0 END) / 
          COUNT(DISTINCT s.id) * 100, 2) AS success_percentage
FROM students s
LEFT JOIN semester_marks sm ON s.id = sm.student_id
GROUP BY s.batch_year, sm.semester_number;
```

### Find Staff with Most MOUs
```sql
SELECT 
    s.name,
    s.department,
    COUNT(m.id) AS mou_count,
    GROUP_CONCAT(DISTINCT m.company) AS companies
FROM staff s
LEFT JOIN mou_files m ON s.id = m.staff_id
GROUP BY s.id
ORDER BY mou_count DESC;
```

---

END OF DATABASE DOCUMENTATION
