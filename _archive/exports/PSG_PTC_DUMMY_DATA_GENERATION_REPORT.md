# PSG PTC ERP Dummy Data Generation Report

Generated: 2026-06-02

## Scope

Created realistic PSG PTC ERP test data for validation.

Safety rules followed:

- No existing data was deleted.
- No existing students were overwritten.
- Records were inserted only when missing.
- No existing production records were intentionally modified or removed.
- No new roles were invented.

Compatibility note:

- `student_details` did not originally contain `gender`, `blood_group`, or `email`.
- These nullable columns were added non-destructively with `ADD COLUMN IF NOT EXISTS` so requested student profile data could be stored.

## Students Created

Total students created: 240.

| Batch | Department | Semester | Section | Students | Status |
| --- | --- | ---: | --- | ---: | --- |
| 24DI | Diploma Information Technology | 4 | A | 60 | approved |
| 24CS | Computer Engineering | 4 | A | 60 | approved |
| 24EE | Electrical and Electronics Engineering | 4 | A | 60 | approved |
| 24ME | Mechanical Engineering | 4 | A | 60 | approved |

Student login format:

- Username = Register Number
- Password = Register Number

Validated examples:

- `24DI01 / 24DI01`: PASS
- `24CS01 / 24CS01`: PASS
- `24EE01 / 24EE01`: PASS
- `24ME01 / 24ME01`: PASS

Generated student fields:

- Register number / roll number
- Name
- Gender
- Blood group
- Community
- Mobile number
- Parent mobile
- Address
- Email
- Current semester
- Batch
- Section
- Department
- Approval status

## Staff Created

Total staff created: 12.

| Department | Staff Created |
| --- | --- |
| Diploma Information Technology | `staff_it_1`, `staff_it_2`, `staff_it_3` |
| Computer Engineering | `staff_cs_1`, `staff_cs_2`, `staff_cs_3` |
| Electrical and Electronics Engineering | `staff_ee_1`, `staff_ee_2`, `staff_ee_3` |
| Mechanical Engineering | `staff_me_1`, `staff_me_2`, `staff_me_3` |

Staff login format:

- Username = staff code
- Password = staff code

Validated examples:

- `staff_it_1 / staff_it_1`: PASS
- `staff_cs_1 / staff_cs_1`: PASS
- `staff_ee_1 / staff_ee_1`: PASS
- `staff_me_1 / staff_me_1`: PASS

## Tutors Assigned

Total tutor assignments: 240 student mappings.

| Batch | Tutor |
| --- | --- |
| 24DI | staff_it_1 |
| 24CS | staff_cs_1 |
| 24EE | staff_ee_1 |
| 24ME | staff_me_1 |

Validation:

- Each batch has exactly one tutor.
- Each generated student has the correct `tutor_staff_id`.

## Subjects Created

Total subjects created: 24.

| Department | Subjects |
| --- | ---: |
| Diploma Information Technology | 6 |
| Computer Engineering | 6 |
| Electrical and Electronics Engineering | 6 |
| Mechanical Engineering | 6 |

Example IT subjects:

- 24DI401 - Programming in Java
- 24DI402 - Database Management System
- 24DI403 - Computer Networks
- 24DI404 - Web Technology
- 24DI405 - Operating Systems
- 24DI406 - Mini Project

## Subject Allocations

Total subject allocations created: 24.

Allocation pattern:

- Subject 1 and 4 -> department tutor/faculty 1
- Subject 2 and 5 -> faculty 2
- Subject 3 and 6 -> faculty 3

Validation:

- All allocations use `subjects.subject_code`.
- All allocations use class year `2nd Year`.
- No duplicate allocation was created.

## Marks Generated

Total marks generated: 1,440.

Calculation fields populated:

- CA1
- CA2
- CA3
- Best two
- Converted to 30
- Assignment
- Theory total
- Cycle 1 execution
- Cycle 1 test
- Cycle 2 record
- Cycle 2 test
- Practical total
- Semester grade
- Grade point
- Arrear flag
- Verification/lock status

Department summary:

| Department | Marks | Pass Records | Arrear Records | Avg Theory | Avg Practical |
| --- | ---: | ---: | ---: | ---: | ---: |
| Diploma Information Technology | 360 | 330 | 30 | 32.66 | 33.52 |
| Computer Engineering | 360 | 330 | 30 | 32.66 | 33.52 |
| Electrical and Electronics Engineering | 360 | 330 | 30 | 32.66 | 33.52 |
| Mechanical Engineering | 360 | 330 | 30 | 32.66 | 33.52 |

Grade distribution:

| Grade | Records |
| --- | ---: |
| O | 120 |
| A+ | 360 |
| B+ | 360 |
| B | 240 |
| C | 240 |
| RA | 120 |

## NBA Report Test Data

The generated data supports:

- Pass percentage
- Fail percentage
- Arrear report
- Subject analysis
- Faculty analysis
- Department analysis
- Batch analysis
- Achievement/sports/publication sections can still be expanded later if richer non-marks test data is required.

## Final Validation

| Item | Result |
| --- | --- |
| Students created | 240 |
| Staff created | 12 |
| Tutors assigned | 240 student mappings |
| Subjects created | 24 |
| Subject allocations created | 24 |
| Marks generated | 1,440 |
| Existing students overwritten | 0 |
| Existing records deleted | 0 |
| Login validation | PASS for sampled student/staff accounts |

Dummy data generation status: PASS.
