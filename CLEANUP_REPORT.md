# PSG PTC ERP – Hosting Cleanup Report

This report details the project cleanup performed to ensure the portal is clean, optimized, and ready for production hosting.

## Hosting Readiness Checklist
- [x] Reverted all global layout & topbar changes (unmodified original theme maintained)
- [x] Collapsible left-sidebar styling and accordion JS logic fully isolated inside `include/navigation.php`
- [x] Unreferenced static files and duplicate CSS/JS assets archived
- [x] No duplicate routes or double function declarations in runtime
- [x] No debug var_dumps, print_r, or temporary validation echoes in production code
- [x] Kept only the latest production database and template word files
- [x] Verification scripts validated (dashboard logins, marks entry, and navigation are fully functional)

## Files Moved to Archive (`_archive/`)

| File Name | Original Location | New Archive Location | Reason |
| :--- | :--- | :--- | :--- |
| DATABASE_DOCUMENTATION.md | `DATABASE_DOCUMENTATION.md` | `_archive/docs/DATABASE_DOCUMENTATION.md` | Unused DOCS file / development asset |
| DIT_IQAC_2023_2024_Edit3-feb2025-update (2).docx | `DIT_IQAC_2023_2024_Edit3-feb2025-update (2).docx` | `_archive/docs/DIT_IQAC_2023_2024_Edit3-feb2025-update (2).docx` | Unused DOCS file / development asset |
| EXECUTIVE_SUMMARY.md | `EXECUTIVE_SUMMARY.md` | `_archive/docs/EXECUTIVE_SUMMARY.md` | Unused DOCS file / development asset |
| FILE_DEPENDENCY_MAP.md | `FILE_DEPENDENCY_MAP.md` | `_archive/docs/FILE_DEPENDENCY_MAP.md` | Unused DOCS file / development asset |
| ROLE_PERMISSION_MATRIX.md | `ROLE_PERMISSION_MATRIX.md` | `_archive/docs/ROLE_PERMISSION_MATRIX.md` | Unused DOCS file / development asset |
| db.php | `db.php` | `_archive/old_php/db.php` | Unused OLD_PHP file / development asset |
| delete.php | `delete.php` | `_archive/old_php/delete.php` | Unused OLD_PHP file / development asset |
| home.php | `home.php` | `_archive/old_php/home.php` | Unused OLD_PHP file / development asset |
| scriptdept.js | `scriptdept.js` | `_archive/old_assets/scriptdept.js` | Unused OLD_ASSETS file / development asset |
| seed.sql | `seed.sql` | `_archive/old_sql/seed.sql` | Unused OLD_SQL file / development asset |
| show_db.php | `show_db.php` | `_archive/debug/show_db.php` | Unused DEBUG file / development asset |
| staff_index.php | `staff_index.php` | `_archive/old_php/staff_index.php` | Unused OLD_PHP file / development asset |
| std_reg.php | `std_reg.php` | `_archive/old_php/std_reg.php` | Unused OLD_PHP file / development asset |
| stlogin.php | `stlogin.php` | `_archive/old_php/stlogin.php` | Unused OLD_PHP file / development asset |
| styledepartments.css | `styledepartments.css` | `_archive/old_assets/styledepartments.css` | Unused OLD_ASSETS file / development asset |
| stylehome.css | `stylehome.css` | `_archive/old_assets/stylehome.css` | Unused OLD_ASSETS file / development asset |
| styleupload.css | `styleupload.css` | `_archive/old_assets/styleupload.css` | Unused OLD_ASSETS file / development asset |
| styleview.css | `styleview.css` | `_archive/old_assets/styleview.css` | Unused OLD_ASSETS file / development asset |
| view_ach.php | `view_ach.php` | `_archive/old_php/view_ach.php` | Unused OLD_PHP file / development asset |
| desktop.ini | `Screenshots\desktop.ini` | `_archive/screenshots/desktop.ini` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0002.jpg | `Screenshots\IMG-20250713-WA0002.jpg` | `_archive/screenshots/IMG-20250713-WA0002.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0003.jpg | `Screenshots\IMG-20250713-WA0003.jpg` | `_archive/screenshots/IMG-20250713-WA0003.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0004.jpg | `Screenshots\IMG-20250713-WA0004.jpg` | `_archive/screenshots/IMG-20250713-WA0004.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0005.jpg | `Screenshots\IMG-20250713-WA0005.jpg` | `_archive/screenshots/IMG-20250713-WA0005.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0006.jpg | `Screenshots\IMG-20250713-WA0006.jpg` | `_archive/screenshots/IMG-20250713-WA0006.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0007.jpg | `Screenshots\IMG-20250713-WA0007.jpg` | `_archive/screenshots/IMG-20250713-WA0007.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0008.jpg | `Screenshots\IMG-20250713-WA0008.jpg` | `_archive/screenshots/IMG-20250713-WA0008.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0009.jpg | `Screenshots\IMG-20250713-WA0009.jpg` | `_archive/screenshots/IMG-20250713-WA0009.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0010.jpg | `Screenshots\IMG-20250713-WA0010.jpg` | `_archive/screenshots/IMG-20250713-WA0010.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0011.jpg | `Screenshots\IMG-20250713-WA0011.jpg` | `_archive/screenshots/IMG-20250713-WA0011.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0012.jpg | `Screenshots\IMG-20250713-WA0012.jpg` | `_archive/screenshots/IMG-20250713-WA0012.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0013.jpg | `Screenshots\IMG-20250713-WA0013.jpg` | `_archive/screenshots/IMG-20250713-WA0013.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0014.jpg | `Screenshots\IMG-20250713-WA0014.jpg` | `_archive/screenshots/IMG-20250713-WA0014.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0015.jpg | `Screenshots\IMG-20250713-WA0015.jpg` | `_archive/screenshots/IMG-20250713-WA0015.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0016.jpg | `Screenshots\IMG-20250713-WA0016.jpg` | `_archive/screenshots/IMG-20250713-WA0016.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0017.jpg | `Screenshots\IMG-20250713-WA0017.jpg` | `_archive/screenshots/IMG-20250713-WA0017.jpg` | Unused SCREENSHOTS file / development asset |
| IMG-20250713-WA0018.jpg | `Screenshots\IMG-20250713-WA0018.jpg` | `_archive/screenshots/IMG-20250713-WA0018.jpg` | Unused SCREENSHOTS file / development asset |
| desktop.ini | `Screenshots\Saved Pictures\desktop.ini` | `_archive/screenshots/desktop_1.ini` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-04-28 191751.png | `Screenshots\Screenshot 2025-04-28 191751.png` | `_archive/screenshots/Screenshot 2025-04-28 191751.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-04-29 151824.png | `Screenshots\Screenshot 2025-04-29 151824.png` | `_archive/screenshots/Screenshot 2025-04-29 151824.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-04-30 025000.png | `Screenshots\Screenshot 2025-04-30 025000.png` | `_archive/screenshots/Screenshot 2025-04-30 025000.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-05-09 193101.png | `Screenshots\Screenshot 2025-05-09 193101.png` | `_archive/screenshots/Screenshot 2025-05-09 193101.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-05-09 193110.png | `Screenshots\Screenshot 2025-05-09 193110.png` | `_archive/screenshots/Screenshot 2025-05-09 193110.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-05-14 105226.png | `Screenshots\Screenshot 2025-05-14 105226.png` | `_archive/screenshots/Screenshot 2025-05-14 105226.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-05-14 111245.png | `Screenshots\Screenshot 2025-05-14 111245.png` | `_archive/screenshots/Screenshot 2025-05-14 111245.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-05-14 111256.png | `Screenshots\Screenshot 2025-05-14 111256.png` | `_archive/screenshots/Screenshot 2025-05-14 111256.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-05-28 122841.png | `Screenshots\Screenshot 2025-05-28 122841.png` | `_archive/screenshots/Screenshot 2025-05-28 122841.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-04 152441.png | `Screenshots\Screenshot 2025-06-04 152441.png` | `_archive/screenshots/Screenshot 2025-06-04 152441.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-10 194007.png | `Screenshots\Screenshot 2025-06-10 194007.png` | `_archive/screenshots/Screenshot 2025-06-10 194007.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 102107.png | `Screenshots\Screenshot 2025-06-22 102107.png` | `_archive/screenshots/Screenshot 2025-06-22 102107.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 102626.png | `Screenshots\Screenshot 2025-06-22 102626.png` | `_archive/screenshots/Screenshot 2025-06-22 102626.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 102921.png | `Screenshots\Screenshot 2025-06-22 102921.png` | `_archive/screenshots/Screenshot 2025-06-22 102921.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 102929.png | `Screenshots\Screenshot 2025-06-22 102929.png` | `_archive/screenshots/Screenshot 2025-06-22 102929.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 110433.png | `Screenshots\Screenshot 2025-06-22 110433.png` | `_archive/screenshots/Screenshot 2025-06-22 110433.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 162433.png | `Screenshots\Screenshot 2025-06-22 162433.png` | `_archive/screenshots/Screenshot 2025-06-22 162433.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 163653.png | `Screenshots\Screenshot 2025-06-22 163653.png` | `_archive/screenshots/Screenshot 2025-06-22 163653.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 165613.png | `Screenshots\Screenshot 2025-06-22 165613.png` | `_archive/screenshots/Screenshot 2025-06-22 165613.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 170313.png | `Screenshots\Screenshot 2025-06-22 170313.png` | `_archive/screenshots/Screenshot 2025-06-22 170313.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 170318.png | `Screenshots\Screenshot 2025-06-22 170318.png` | `_archive/screenshots/Screenshot 2025-06-22 170318.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 170502.png | `Screenshots\Screenshot 2025-06-22 170502.png` | `_archive/screenshots/Screenshot 2025-06-22 170502.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 170726.png | `Screenshots\Screenshot 2025-06-22 170726.png` | `_archive/screenshots/Screenshot 2025-06-22 170726.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 170838.png | `Screenshots\Screenshot 2025-06-22 170838.png` | `_archive/screenshots/Screenshot 2025-06-22 170838.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 171001.png | `Screenshots\Screenshot 2025-06-22 171001.png` | `_archive/screenshots/Screenshot 2025-06-22 171001.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 173159.png | `Screenshots\Screenshot 2025-06-22 173159.png` | `_archive/screenshots/Screenshot 2025-06-22 173159.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 174648.png | `Screenshots\Screenshot 2025-06-22 174648.png` | `_archive/screenshots/Screenshot 2025-06-22 174648.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 174659.png | `Screenshots\Screenshot 2025-06-22 174659.png` | `_archive/screenshots/Screenshot 2025-06-22 174659.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 174848.png | `Screenshots\Screenshot 2025-06-22 174848.png` | `_archive/screenshots/Screenshot 2025-06-22 174848.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 175122.png | `Screenshots\Screenshot 2025-06-22 175122.png` | `_archive/screenshots/Screenshot 2025-06-22 175122.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-22 175152.png | `Screenshots\Screenshot 2025-06-22 175152.png` | `_archive/screenshots/Screenshot 2025-06-22 175152.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-23 185231.png | `Screenshots\Screenshot 2025-06-23 185231.png` | `_archive/screenshots/Screenshot 2025-06-23 185231.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-23 192820.png | `Screenshots\Screenshot 2025-06-23 192820.png` | `_archive/screenshots/Screenshot 2025-06-23 192820.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-23 215532.png | `Screenshots\Screenshot 2025-06-23 215532.png` | `_archive/screenshots/Screenshot 2025-06-23 215532.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-23 215607.png | `Screenshots\Screenshot 2025-06-23 215607.png` | `_archive/screenshots/Screenshot 2025-06-23 215607.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-23 215801.png | `Screenshots\Screenshot 2025-06-23 215801.png` | `_archive/screenshots/Screenshot 2025-06-23 215801.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-23 220533.png | `Screenshots\Screenshot 2025-06-23 220533.png` | `_archive/screenshots/Screenshot 2025-06-23 220533.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-06-23 220626.png | `Screenshots\Screenshot 2025-06-23 220626.png` | `_archive/screenshots/Screenshot 2025-06-23 220626.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-03 134446.png | `Screenshots\Screenshot 2025-07-03 134446.png` | `_archive/screenshots/Screenshot 2025-07-03 134446.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-03 134718.png | `Screenshots\Screenshot 2025-07-03 134718.png` | `_archive/screenshots/Screenshot 2025-07-03 134718.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-03 135554.png | `Screenshots\Screenshot 2025-07-03 135554.png` | `_archive/screenshots/Screenshot 2025-07-03 135554.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-03 142920.png | `Screenshots\Screenshot 2025-07-03 142920.png` | `_archive/screenshots/Screenshot 2025-07-03 142920.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-03 143116.png | `Screenshots\Screenshot 2025-07-03 143116.png` | `_archive/screenshots/Screenshot 2025-07-03 143116.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-03 143324.png | `Screenshots\Screenshot 2025-07-03 143324.png` | `_archive/screenshots/Screenshot 2025-07-03 143324.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-04 193322.png | `Screenshots\Screenshot 2025-07-04 193322.png` | `_archive/screenshots/Screenshot 2025-07-04 193322.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-04 203502.png | `Screenshots\Screenshot 2025-07-04 203502.png` | `_archive/screenshots/Screenshot 2025-07-04 203502.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-05 150056.png | `Screenshots\Screenshot 2025-07-05 150056.png` | `_archive/screenshots/Screenshot 2025-07-05 150056.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-08 204129.png | `Screenshots\Screenshot 2025-07-08 204129.png` | `_archive/screenshots/Screenshot 2025-07-08 204129.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-10 140445.png | `Screenshots\Screenshot 2025-07-10 140445.png` | `_archive/screenshots/Screenshot 2025-07-10 140445.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-10 142715.png | `Screenshots\Screenshot 2025-07-10 142715.png` | `_archive/screenshots/Screenshot 2025-07-10 142715.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 191029.png | `Screenshots\Screenshot 2025-07-13 191029.png` | `_archive/screenshots/Screenshot 2025-07-13 191029.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 191527.png | `Screenshots\Screenshot 2025-07-13 191527.png` | `_archive/screenshots/Screenshot 2025-07-13 191527.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 211329.png | `Screenshots\Screenshot 2025-07-13 211329.png` | `_archive/screenshots/Screenshot 2025-07-13 211329.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 211352.png | `Screenshots\Screenshot 2025-07-13 211352.png` | `_archive/screenshots/Screenshot 2025-07-13 211352.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 211406.png | `Screenshots\Screenshot 2025-07-13 211406.png` | `_archive/screenshots/Screenshot 2025-07-13 211406.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 223531.png | `Screenshots\Screenshot 2025-07-13 223531.png` | `_archive/screenshots/Screenshot 2025-07-13 223531.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 223545.png | `Screenshots\Screenshot 2025-07-13 223545.png` | `_archive/screenshots/Screenshot 2025-07-13 223545.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 223558.png | `Screenshots\Screenshot 2025-07-13 223558.png` | `_archive/screenshots/Screenshot 2025-07-13 223558.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 224007.png | `Screenshots\Screenshot 2025-07-13 224007.png` | `_archive/screenshots/Screenshot 2025-07-13 224007.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 224357.png | `Screenshots\Screenshot 2025-07-13 224357.png` | `_archive/screenshots/Screenshot 2025-07-13 224357.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 225118.png | `Screenshots\Screenshot 2025-07-13 225118.png` | `_archive/screenshots/Screenshot 2025-07-13 225118.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-13 225316.png | `Screenshots\Screenshot 2025-07-13 225316.png` | `_archive/screenshots/Screenshot 2025-07-13 225316.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-18 092145.png | `Screenshots\Screenshot 2025-07-18 092145.png` | `_archive/screenshots/Screenshot 2025-07-18 092145.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-18 092312.png | `Screenshots\Screenshot 2025-07-18 092312.png` | `_archive/screenshots/Screenshot 2025-07-18 092312.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-18 092607.png | `Screenshots\Screenshot 2025-07-18 092607.png` | `_archive/screenshots/Screenshot 2025-07-18 092607.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-18 092722.png | `Screenshots\Screenshot 2025-07-18 092722.png` | `_archive/screenshots/Screenshot 2025-07-18 092722.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-18 093146.png | `Screenshots\Screenshot 2025-07-18 093146.png` | `_archive/screenshots/Screenshot 2025-07-18 093146.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-18 094313.png | `Screenshots\Screenshot 2025-07-18 094313.png` | `_archive/screenshots/Screenshot 2025-07-18 094313.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 100143.png | `Screenshots\Screenshot 2025-07-19 100143.png` | `_archive/screenshots/Screenshot 2025-07-19 100143.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 122252.png | `Screenshots\Screenshot 2025-07-19 122252.png` | `_archive/screenshots/Screenshot 2025-07-19 122252.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 122339.png | `Screenshots\Screenshot 2025-07-19 122339.png` | `_archive/screenshots/Screenshot 2025-07-19 122339.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 123132.png | `Screenshots\Screenshot 2025-07-19 123132.png` | `_archive/screenshots/Screenshot 2025-07-19 123132.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 124507.png | `Screenshots\Screenshot 2025-07-19 124507.png` | `_archive/screenshots/Screenshot 2025-07-19 124507.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 131330.png | `Screenshots\Screenshot 2025-07-19 131330.png` | `_archive/screenshots/Screenshot 2025-07-19 131330.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 131344.png | `Screenshots\Screenshot 2025-07-19 131344.png` | `_archive/screenshots/Screenshot 2025-07-19 131344.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 131355.png | `Screenshots\Screenshot 2025-07-19 131355.png` | `_archive/screenshots/Screenshot 2025-07-19 131355.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 131845.png | `Screenshots\Screenshot 2025-07-19 131845.png` | `_archive/screenshots/Screenshot 2025-07-19 131845.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 131950.png | `Screenshots\Screenshot 2025-07-19 131950.png` | `_archive/screenshots/Screenshot 2025-07-19 131950.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 132435.png | `Screenshots\Screenshot 2025-07-19 132435.png` | `_archive/screenshots/Screenshot 2025-07-19 132435.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 132848.png | `Screenshots\Screenshot 2025-07-19 132848.png` | `_archive/screenshots/Screenshot 2025-07-19 132848.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 133044.png | `Screenshots\Screenshot 2025-07-19 133044.png` | `_archive/screenshots/Screenshot 2025-07-19 133044.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 133244.png | `Screenshots\Screenshot 2025-07-19 133244.png` | `_archive/screenshots/Screenshot 2025-07-19 133244.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 133738.png | `Screenshots\Screenshot 2025-07-19 133738.png` | `_archive/screenshots/Screenshot 2025-07-19 133738.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 134604.png | `Screenshots\Screenshot 2025-07-19 134604.png` | `_archive/screenshots/Screenshot 2025-07-19 134604.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 144400.png | `Screenshots\Screenshot 2025-07-19 144400.png` | `_archive/screenshots/Screenshot 2025-07-19 144400.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 144421.png | `Screenshots\Screenshot 2025-07-19 144421.png` | `_archive/screenshots/Screenshot 2025-07-19 144421.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 153847.png | `Screenshots\Screenshot 2025-07-19 153847.png` | `_archive/screenshots/Screenshot 2025-07-19 153847.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 154837.png | `Screenshots\Screenshot 2025-07-19 154837.png` | `_archive/screenshots/Screenshot 2025-07-19 154837.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 155004.png | `Screenshots\Screenshot 2025-07-19 155004.png` | `_archive/screenshots/Screenshot 2025-07-19 155004.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 155953.png | `Screenshots\Screenshot 2025-07-19 155953.png` | `_archive/screenshots/Screenshot 2025-07-19 155953.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 160200.png | `Screenshots\Screenshot 2025-07-19 160200.png` | `_archive/screenshots/Screenshot 2025-07-19 160200.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 160340.png | `Screenshots\Screenshot 2025-07-19 160340.png` | `_archive/screenshots/Screenshot 2025-07-19 160340.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 160415.png | `Screenshots\Screenshot 2025-07-19 160415.png` | `_archive/screenshots/Screenshot 2025-07-19 160415.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 160509.png | `Screenshots\Screenshot 2025-07-19 160509.png` | `_archive/screenshots/Screenshot 2025-07-19 160509.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 161940.png | `Screenshots\Screenshot 2025-07-19 161940.png` | `_archive/screenshots/Screenshot 2025-07-19 161940.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-19 165530.png | `Screenshots\Screenshot 2025-07-19 165530.png` | `_archive/screenshots/Screenshot 2025-07-19 165530.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-20 162055.png | `Screenshots\Screenshot 2025-07-20 162055.png` | `_archive/screenshots/Screenshot 2025-07-20 162055.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-20 164555.png | `Screenshots\Screenshot 2025-07-20 164555.png` | `_archive/screenshots/Screenshot 2025-07-20 164555.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-20 171105.png | `Screenshots\Screenshot 2025-07-20 171105.png` | `_archive/screenshots/Screenshot 2025-07-20 171105.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-22 111013.png | `Screenshots\Screenshot 2025-07-22 111013.png` | `_archive/screenshots/Screenshot 2025-07-22 111013.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-23 204248.png | `Screenshots\Screenshot 2025-07-23 204248.png` | `_archive/screenshots/Screenshot 2025-07-23 204248.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-23 204336.png | `Screenshots\Screenshot 2025-07-23 204336.png` | `_archive/screenshots/Screenshot 2025-07-23 204336.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-23 204443.png | `Screenshots\Screenshot 2025-07-23 204443.png` | `_archive/screenshots/Screenshot 2025-07-23 204443.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-23 204532.png | `Screenshots\Screenshot 2025-07-23 204532.png` | `_archive/screenshots/Screenshot 2025-07-23 204532.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 100939.png | `Screenshots\Screenshot 2025-07-24 100939.png` | `_archive/screenshots/Screenshot 2025-07-24 100939.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 211001.png | `Screenshots\Screenshot 2025-07-24 211001.png` | `_archive/screenshots/Screenshot 2025-07-24 211001.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 213211.png | `Screenshots\Screenshot 2025-07-24 213211.png` | `_archive/screenshots/Screenshot 2025-07-24 213211.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 213657.png | `Screenshots\Screenshot 2025-07-24 213657.png` | `_archive/screenshots/Screenshot 2025-07-24 213657.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 213735.png | `Screenshots\Screenshot 2025-07-24 213735.png` | `_archive/screenshots/Screenshot 2025-07-24 213735.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 213810.png | `Screenshots\Screenshot 2025-07-24 213810.png` | `_archive/screenshots/Screenshot 2025-07-24 213810.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 213857.png | `Screenshots\Screenshot 2025-07-24 213857.png` | `_archive/screenshots/Screenshot 2025-07-24 213857.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 213943.png | `Screenshots\Screenshot 2025-07-24 213943.png` | `_archive/screenshots/Screenshot 2025-07-24 213943.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 214017.png | `Screenshots\Screenshot 2025-07-24 214017.png` | `_archive/screenshots/Screenshot 2025-07-24 214017.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-24 214057.png | `Screenshots\Screenshot 2025-07-24 214057.png` | `_archive/screenshots/Screenshot 2025-07-24 214057.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 113714.png | `Screenshots\Screenshot 2025-07-27 113714.png` | `_archive/screenshots/Screenshot 2025-07-27 113714.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 120823.png | `Screenshots\Screenshot 2025-07-27 120823.png` | `_archive/screenshots/Screenshot 2025-07-27 120823.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 131251.png | `Screenshots\Screenshot 2025-07-27 131251.png` | `_archive/screenshots/Screenshot 2025-07-27 131251.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 132201.png | `Screenshots\Screenshot 2025-07-27 132201.png` | `_archive/screenshots/Screenshot 2025-07-27 132201.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 144450.png | `Screenshots\Screenshot 2025-07-27 144450.png` | `_archive/screenshots/Screenshot 2025-07-27 144450.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 145859.png | `Screenshots\Screenshot 2025-07-27 145859.png` | `_archive/screenshots/Screenshot 2025-07-27 145859.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 150020.png | `Screenshots\Screenshot 2025-07-27 150020.png` | `_archive/screenshots/Screenshot 2025-07-27 150020.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 150107.png | `Screenshots\Screenshot 2025-07-27 150107.png` | `_archive/screenshots/Screenshot 2025-07-27 150107.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 151752.png | `Screenshots\Screenshot 2025-07-27 151752.png` | `_archive/screenshots/Screenshot 2025-07-27 151752.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 151814.png | `Screenshots\Screenshot 2025-07-27 151814.png` | `_archive/screenshots/Screenshot 2025-07-27 151814.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 152133.png | `Screenshots\Screenshot 2025-07-27 152133.png` | `_archive/screenshots/Screenshot 2025-07-27 152133.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 153709.png | `Screenshots\Screenshot 2025-07-27 153709.png` | `_archive/screenshots/Screenshot 2025-07-27 153709.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 153915.png | `Screenshots\Screenshot 2025-07-27 153915.png` | `_archive/screenshots/Screenshot 2025-07-27 153915.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 153938.png | `Screenshots\Screenshot 2025-07-27 153938.png` | `_archive/screenshots/Screenshot 2025-07-27 153938.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-27 154001.png | `Screenshots\Screenshot 2025-07-27 154001.png` | `_archive/screenshots/Screenshot 2025-07-27 154001.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-28 042741.png | `Screenshots\Screenshot 2025-07-28 042741.png` | `_archive/screenshots/Screenshot 2025-07-28 042741.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-28 042746.png | `Screenshots\Screenshot 2025-07-28 042746.png` | `_archive/screenshots/Screenshot 2025-07-28 042746.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-28 042754.png | `Screenshots\Screenshot 2025-07-28 042754.png` | `_archive/screenshots/Screenshot 2025-07-28 042754.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-28 050153.png | `Screenshots\Screenshot 2025-07-28 050153.png` | `_archive/screenshots/Screenshot 2025-07-28 050153.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 132858.png | `Screenshots\Screenshot 2025-07-31 132858.png` | `_archive/screenshots/Screenshot 2025-07-31 132858.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 135301.png | `Screenshots\Screenshot 2025-07-31 135301.png` | `_archive/screenshots/Screenshot 2025-07-31 135301.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 144818.png | `Screenshots\Screenshot 2025-07-31 144818.png` | `_archive/screenshots/Screenshot 2025-07-31 144818.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 200529.png | `Screenshots\Screenshot 2025-07-31 200529.png` | `_archive/screenshots/Screenshot 2025-07-31 200529.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 201204.png | `Screenshots\Screenshot 2025-07-31 201204.png` | `_archive/screenshots/Screenshot 2025-07-31 201204.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 201254.png | `Screenshots\Screenshot 2025-07-31 201254.png` | `_archive/screenshots/Screenshot 2025-07-31 201254.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 201407.png | `Screenshots\Screenshot 2025-07-31 201407.png` | `_archive/screenshots/Screenshot 2025-07-31 201407.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 201535.png | `Screenshots\Screenshot 2025-07-31 201535.png` | `_archive/screenshots/Screenshot 2025-07-31 201535.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 202203.png | `Screenshots\Screenshot 2025-07-31 202203.png` | `_archive/screenshots/Screenshot 2025-07-31 202203.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 211223.png | `Screenshots\Screenshot 2025-07-31 211223.png` | `_archive/screenshots/Screenshot 2025-07-31 211223.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 211558.png | `Screenshots\Screenshot 2025-07-31 211558.png` | `_archive/screenshots/Screenshot 2025-07-31 211558.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 211759.png | `Screenshots\Screenshot 2025-07-31 211759.png` | `_archive/screenshots/Screenshot 2025-07-31 211759.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 211858.png | `Screenshots\Screenshot 2025-07-31 211858.png` | `_archive/screenshots/Screenshot 2025-07-31 211858.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 225750.png | `Screenshots\Screenshot 2025-07-31 225750.png` | `_archive/screenshots/Screenshot 2025-07-31 225750.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 225805.png | `Screenshots\Screenshot 2025-07-31 225805.png` | `_archive/screenshots/Screenshot 2025-07-31 225805.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 225938.png | `Screenshots\Screenshot 2025-07-31 225938.png` | `_archive/screenshots/Screenshot 2025-07-31 225938.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 230040.png | `Screenshots\Screenshot 2025-07-31 230040.png` | `_archive/screenshots/Screenshot 2025-07-31 230040.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 230655.png | `Screenshots\Screenshot 2025-07-31 230655.png` | `_archive/screenshots/Screenshot 2025-07-31 230655.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 230826.png | `Screenshots\Screenshot 2025-07-31 230826.png` | `_archive/screenshots/Screenshot 2025-07-31 230826.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-07-31 232330.png | `Screenshots\Screenshot 2025-07-31 232330.png` | `_archive/screenshots/Screenshot 2025-07-31 232330.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 162903.png | `Screenshots\Screenshot 2025-08-02 162903.png` | `_archive/screenshots/Screenshot 2025-08-02 162903.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 163203.png | `Screenshots\Screenshot 2025-08-02 163203.png` | `_archive/screenshots/Screenshot 2025-08-02 163203.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 164014.png | `Screenshots\Screenshot 2025-08-02 164014.png` | `_archive/screenshots/Screenshot 2025-08-02 164014.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 165435.png | `Screenshots\Screenshot 2025-08-02 165435.png` | `_archive/screenshots/Screenshot 2025-08-02 165435.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 171101.png | `Screenshots\Screenshot 2025-08-02 171101.png` | `_archive/screenshots/Screenshot 2025-08-02 171101.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 171121.png | `Screenshots\Screenshot 2025-08-02 171121.png` | `_archive/screenshots/Screenshot 2025-08-02 171121.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 172327.png | `Screenshots\Screenshot 2025-08-02 172327.png` | `_archive/screenshots/Screenshot 2025-08-02 172327.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 172331.png | `Screenshots\Screenshot 2025-08-02 172331.png` | `_archive/screenshots/Screenshot 2025-08-02 172331.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 174959.png | `Screenshots\Screenshot 2025-08-02 174959.png` | `_archive/screenshots/Screenshot 2025-08-02 174959.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 175751.png | `Screenshots\Screenshot 2025-08-02 175751.png` | `_archive/screenshots/Screenshot 2025-08-02 175751.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 180136.png | `Screenshots\Screenshot 2025-08-02 180136.png` | `_archive/screenshots/Screenshot 2025-08-02 180136.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-02 180201.png | `Screenshots\Screenshot 2025-08-02 180201.png` | `_archive/screenshots/Screenshot 2025-08-02 180201.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-04 155317.png | `Screenshots\Screenshot 2025-08-04 155317.png` | `_archive/screenshots/Screenshot 2025-08-04 155317.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-05 102538.png | `Screenshots\Screenshot 2025-08-05 102538.png` | `_archive/screenshots/Screenshot 2025-08-05 102538.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-05 102640.png | `Screenshots\Screenshot 2025-08-05 102640.png` | `_archive/screenshots/Screenshot 2025-08-05 102640.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 190257.png | `Screenshots\Screenshot 2025-08-06 190257.png` | `_archive/screenshots/Screenshot 2025-08-06 190257.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 191607.png | `Screenshots\Screenshot 2025-08-06 191607.png` | `_archive/screenshots/Screenshot 2025-08-06 191607.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 191917.png | `Screenshots\Screenshot 2025-08-06 191917.png` | `_archive/screenshots/Screenshot 2025-08-06 191917.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 194129.png | `Screenshots\Screenshot 2025-08-06 194129.png` | `_archive/screenshots/Screenshot 2025-08-06 194129.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 194822.png | `Screenshots\Screenshot 2025-08-06 194822.png` | `_archive/screenshots/Screenshot 2025-08-06 194822.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 201850.png | `Screenshots\Screenshot 2025-08-06 201850.png` | `_archive/screenshots/Screenshot 2025-08-06 201850.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 201947.png | `Screenshots\Screenshot 2025-08-06 201947.png` | `_archive/screenshots/Screenshot 2025-08-06 201947.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 202222.png | `Screenshots\Screenshot 2025-08-06 202222.png` | `_archive/screenshots/Screenshot 2025-08-06 202222.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 202626.png | `Screenshots\Screenshot 2025-08-06 202626.png` | `_archive/screenshots/Screenshot 2025-08-06 202626.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 203518.png | `Screenshots\Screenshot 2025-08-06 203518.png` | `_archive/screenshots/Screenshot 2025-08-06 203518.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-06 204236.png | `Screenshots\Screenshot 2025-08-06 204236.png` | `_archive/screenshots/Screenshot 2025-08-06 204236.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 201632.png | `Screenshots\Screenshot 2025-08-07 201632.png` | `_archive/screenshots/Screenshot 2025-08-07 201632.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 203907.png | `Screenshots\Screenshot 2025-08-07 203907.png` | `_archive/screenshots/Screenshot 2025-08-07 203907.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 204743.png | `Screenshots\Screenshot 2025-08-07 204743.png` | `_archive/screenshots/Screenshot 2025-08-07 204743.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 204929.png | `Screenshots\Screenshot 2025-08-07 204929.png` | `_archive/screenshots/Screenshot 2025-08-07 204929.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 205114.png | `Screenshots\Screenshot 2025-08-07 205114.png` | `_archive/screenshots/Screenshot 2025-08-07 205114.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 210513.png | `Screenshots\Screenshot 2025-08-07 210513.png` | `_archive/screenshots/Screenshot 2025-08-07 210513.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 210901.png | `Screenshots\Screenshot 2025-08-07 210901.png` | `_archive/screenshots/Screenshot 2025-08-07 210901.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 211105.png | `Screenshots\Screenshot 2025-08-07 211105.png` | `_archive/screenshots/Screenshot 2025-08-07 211105.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 211525.png | `Screenshots\Screenshot 2025-08-07 211525.png` | `_archive/screenshots/Screenshot 2025-08-07 211525.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 214036.png | `Screenshots\Screenshot 2025-08-07 214036.png` | `_archive/screenshots/Screenshot 2025-08-07 214036.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 214617.png | `Screenshots\Screenshot 2025-08-07 214617.png` | `_archive/screenshots/Screenshot 2025-08-07 214617.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 214641.png | `Screenshots\Screenshot 2025-08-07 214641.png` | `_archive/screenshots/Screenshot 2025-08-07 214641.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 215022.png | `Screenshots\Screenshot 2025-08-07 215022.png` | `_archive/screenshots/Screenshot 2025-08-07 215022.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 215043.png | `Screenshots\Screenshot 2025-08-07 215043.png` | `_archive/screenshots/Screenshot 2025-08-07 215043.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 215323.png | `Screenshots\Screenshot 2025-08-07 215323.png` | `_archive/screenshots/Screenshot 2025-08-07 215323.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 220807.png | `Screenshots\Screenshot 2025-08-07 220807.png` | `_archive/screenshots/Screenshot 2025-08-07 220807.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 221319.png | `Screenshots\Screenshot 2025-08-07 221319.png` | `_archive/screenshots/Screenshot 2025-08-07 221319.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-07 221413.png | `Screenshots\Screenshot 2025-08-07 221413.png` | `_archive/screenshots/Screenshot 2025-08-07 221413.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-09 160923.png | `Screenshots\Screenshot 2025-08-09 160923.png` | `_archive/screenshots/Screenshot 2025-08-09 160923.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-09 161442.png | `Screenshots\Screenshot 2025-08-09 161442.png` | `_archive/screenshots/Screenshot 2025-08-09 161442.png` | Unused SCREENSHOTS file / development asset |
| Screenshot 2025-08-09 164713.png | `Screenshots\Screenshot 2025-08-09 164713.png` | `_archive/screenshots/Screenshot 2025-08-09 164713.png` | Unused SCREENSHOTS file / development asset |
| test_modified.docx | `tmp_nba_template_render\test_modified.docx` | `_archive/temp/test_modified.docx` | Unused TEMP file / development asset |
| item1.xml | `tmp_validation\docx_extract\customXml\item1.xml` | `_archive/temp/item1.xml` | Unused TEMP file / development asset |
| itemProps1.xml | `tmp_validation\docx_extract\customXml\itemProps1.xml` | `_archive/temp/itemProps1.xml` | Unused TEMP file / development asset |
| item1.xml.rels | `tmp_validation\docx_extract\customXml\_rels\item1.xml.rels` | `_archive/temp/item1.xml.rels` | Unused TEMP file / development asset |
| app.xml | `tmp_validation\docx_extract\docProps\app.xml` | `_archive/temp/app.xml` | Unused TEMP file / development asset |
| core.xml | `tmp_validation\docx_extract\docProps\core.xml` | `_archive/temp/core.xml` | Unused TEMP file / development asset |
| document.xml | `tmp_validation\docx_extract\word\document.xml` | `_archive/temp/document.xml` | Unused TEMP file / development asset |
| fontTable.xml | `tmp_validation\docx_extract\word\fontTable.xml` | `_archive/temp/fontTable.xml` | Unused TEMP file / development asset |
| numbering.xml | `tmp_validation\docx_extract\word\numbering.xml` | `_archive/temp/numbering.xml` | Unused TEMP file / development asset |
| settings.xml | `tmp_validation\docx_extract\word\settings.xml` | `_archive/temp/settings.xml` | Unused TEMP file / development asset |
| styles.xml | `tmp_validation\docx_extract\word\styles.xml` | `_archive/temp/styles.xml` | Unused TEMP file / development asset |
| stylesWithEffects.xml | `tmp_validation\docx_extract\word\stylesWithEffects.xml` | `_archive/temp/stylesWithEffects.xml` | Unused TEMP file / development asset |
| theme1.xml | `tmp_validation\docx_extract\word\theme\theme1.xml` | `_archive/temp/theme1.xml` | Unused TEMP file / development asset |
| webSettings.xml | `tmp_validation\docx_extract\word\webSettings.xml` | `_archive/temp/webSettings.xml` | Unused TEMP file / development asset |
| document.xml.rels | `tmp_validation\docx_extract\word\_rels\document.xml.rels` | `_archive/temp/document.xml.rels` | Unused TEMP file / development asset |
| [Content_Types].xml | `tmp_validation\docx_extract\[Content_Types].xml` | `_archive/temp/[Content_Types].xml` | Unused TEMP file / development asset |
| .rels | `tmp_validation\docx_extract\_rels\.rels` | `_archive/temp/.rels` | Unused TEMP file / development asset |
| nba_docx_test.json | `tmp_validation\nba_docx_test.json` | `_archive/temp/nba_docx_test.json` | Unused TEMP file / development asset |
| schema_inspection.txt | `tmp_validation\schema_inspection.txt` | `_archive/temp/schema_inspection.txt` | Unused TEMP file / development asset |
| seed_40di_demo.php | `scripts/seed_40di_demo.php` | `_archive/test_files/seed_40di_demo.php` | Unused TEST_FILES file / development asset |
| PROJECT_AUDIT_DOCUMENTATION.md | `archive/reports\PROJECT_AUDIT_DOCUMENTATION.md` | `_archive/exports/PROJECT_AUDIT_DOCUMENTATION.md` | Unused EXPORTS file / development asset |
| PROJECT_CLEANUP_REPORT.md | `archive/reports\PROJECT_CLEANUP_REPORT.md` | `_archive/exports/PROJECT_CLEANUP_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_ACCOUNT_RECOVERY_REPORT.md | `archive/reports\PSG_PTC_ACCOUNT_RECOVERY_REPORT.md` | `_archive/exports/PSG_PTC_ACCOUNT_RECOVERY_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_CLEANUP_REPORT.md | `archive/reports\PSG_PTC_CLEANUP_REPORT.md` | `_archive/exports/PSG_PTC_CLEANUP_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_DUMMY_DATA_GENERATION_REPORT.md | `archive/reports\PSG_PTC_DUMMY_DATA_GENERATION_REPORT.md` | `_archive/exports/PSG_PTC_DUMMY_DATA_GENERATION_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_EMERGENCY_RESTORE_REPORT.md | `archive/reports\PSG_PTC_EMERGENCY_RESTORE_REPORT.md` | `_archive/exports/PSG_PTC_EMERGENCY_RESTORE_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_ERP_ARCHITECTURE_CORRECTION_REPORT.md | `archive/reports\PSG_PTC_ERP_ARCHITECTURE_CORRECTION_REPORT.md` | `_archive/exports/PSG_PTC_ERP_ARCHITECTURE_CORRECTION_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_FEATURE_RESTORATION_AND_FORMS_REPORT.md | `archive/reports\PSG_PTC_FEATURE_RESTORATION_AND_FORMS_REPORT.md` | `_archive/exports/PSG_PTC_FEATURE_RESTORATION_AND_FORMS_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_LOGIN_PASSWORD_SECURITY_REPORT.md | `archive/reports\PSG_PTC_LOGIN_PASSWORD_SECURITY_REPORT.md` | `_archive/exports/PSG_PTC_LOGIN_PASSWORD_SECURITY_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_NBA_EXPORT_CORRECTION_REPORT.md | `archive/reports\PSG_PTC_NBA_EXPORT_CORRECTION_REPORT.md` | `_archive/exports/PSG_PTC_NBA_EXPORT_CORRECTION_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_PASSWORD_CHANGE_FLOW_AUDIT.md | `archive/reports\PSG_PTC_PASSWORD_CHANGE_FLOW_AUDIT.md` | `_archive/exports/PSG_PTC_PASSWORD_CHANGE_FLOW_AUDIT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_PHASE_0_3_ACCOUNT_RECOVERY_LOGIN_VALIDATION.md | `archive/reports\PSG_PTC_PHASE_0_3_ACCOUNT_RECOVERY_LOGIN_VALIDATION.md` | `_archive/exports/PSG_PTC_PHASE_0_3_ACCOUNT_RECOVERY_LOGIN_VALIDATION.md` | Unused EXPORTS file / development asset |
| PSG_PTC_SESSION_ISOLATION_FIX_REPORT.md | `archive/reports\PSG_PTC_SESSION_ISOLATION_FIX_REPORT.md` | `_archive/exports/PSG_PTC_SESSION_ISOLATION_FIX_REPORT.md` | Unused EXPORTS file / development asset |
| PSG_PTC_UI_REDESIGN_REPORT.md | `archive/reports\PSG_PTC_UI_REDESIGN_REPORT.md` | `_archive/exports/PSG_PTC_UI_REDESIGN_REPORT.md` | Unused EXPORTS file / development asset |
| FINAL_LIVE_END_TO_END_VALIDATION_REPORT.md | `archive\final_live_validation\FINAL_LIVE_END_TO_END_VALIDATION_REPORT.md` | `_archive/exports/FINAL_LIVE_END_TO_END_VALIDATION_REPORT.md` | Unused EXPORTS file / development asset |
| final_live_validation_summary.json | `archive\final_live_validation\final_live_validation_summary.json` | `_archive/exports/final_live_validation_summary.json` | Unused EXPORTS file / development asset |
| http_export_rbac_session_results.json | `archive\final_live_validation\http_export_rbac_session_results.json` | `_archive/exports/http_export_rbac_session_results.json` | Unused EXPORTS file / development asset |
| validation_nba_report.docx | `archive\final_live_validation\validation_nba_report.docx` | `_archive/exports/validation_nba_report.docx` | Unused EXPORTS file / development asset |
| validation_nba_report.pdf | `archive\final_live_validation\validation_nba_report.pdf` | `_archive/exports/validation_nba_report.pdf` | Unused EXPORTS file / development asset |
| validation_nba_report.xls | `archive\final_live_validation\validation_nba_report.xls` | `_archive/exports/validation_nba_report.xls` | Unused EXPORTS file / development asset |
| validation_print_page.html | `archive\final_live_validation\validation_print_page.html` | `_archive/exports/validation_print_page.html` | Unused EXPORTS file / development asset |
| nba_export_mockup_preview.html | `archive\nba_export_mockup_preview.html` | `_archive/exports/nba_export_mockup_preview.html` | Unused EXPORTS file / development asset |
| nba_export_mockup_preview.png | `archive\nba_export_mockup_preview.png` | `_archive/exports/nba_export_mockup_preview.png` | Unused EXPORTS file / development asset |
| academic_performance_nba.pdf | `archive\validation_exports\academic_performance_nba.pdf` | `_archive/exports/academic_performance_nba.pdf` | Unused EXPORTS file / development asset |
| academic_performance_nba.xls | `archive\validation_exports\academic_performance_nba.xls` | `_archive/exports/academic_performance_nba.xls` | Unused EXPORTS file / development asset |
| excel_workbook_screenshot.png | `archive\validation_exports\excel_workbook_screenshot.png` | `_archive/exports/excel_workbook_screenshot.png` | Unused EXPORTS file / development asset |
| exported_pdf_screenshot.png | `archive\validation_exports\exported_pdf_screenshot.png` | `_archive/exports/exported_pdf_screenshot.png` | Unused EXPORTS file / development asset |
| faculty_nba_report_screenshot.png | `archive\validation_exports\faculty_nba_report_screenshot.png` | `_archive/exports/faculty_nba_report_screenshot.png` | Unused EXPORTS file / development asset |
| marks_nba.pdf | `archive\validation_exports\marks_nba.pdf` | `_archive/exports/marks_nba.pdf` | Unused EXPORTS file / development asset |
| marks_nba.xls | `archive\validation_exports\marks_nba.xls` | `_archive/exports/marks_nba.xls` | Unused EXPORTS file / development asset |
| print_preview_screenshot.png | `archive\validation_exports\print_preview_screenshot.png` | `_archive/exports/print_preview_screenshot.png` | Unused EXPORTS file / development asset |
| student_nba_report_screenshot.png | `archive\validation_exports\student_nba_report_screenshot.png` | `_archive/exports/student_nba_report_screenshot.png` | Unused EXPORTS file / development asset |
| success_rate_nba.pdf | `archive\validation_exports\success_rate_nba.pdf` | `_archive/exports/success_rate_nba.pdf` | Unused EXPORTS file / development asset |
| success_rate_nba.xls | `archive\validation_exports\success_rate_nba.xls` | `_archive/exports/success_rate_nba.xls` | Unused EXPORTS file / development asset |
| success_rate_nba_report_screenshot.png | `archive\validation_exports\success_rate_nba_report_screenshot.png` | `_archive/exports/success_rate_nba_report_screenshot.png` | Unused EXPORTS file / development asset |
| success_rate_pdf_screenshot.png | `archive\validation_exports\success_rate_pdf_screenshot.png` | `_archive/exports/success_rate_pdf_screenshot.png` | Unused EXPORTS file / development asset |
| access_restriction_test.png | `archive\validation_rbac\access_restriction_test.png` | `_archive/exports/access_restriction_test.png` | Unused EXPORTS file / development asset |
| admin_navigation.png | `archive\validation_rbac\admin_navigation.png` | `_archive/exports/admin_navigation.png` | Unused EXPORTS file / development asset |
| batch_24di_accounts.json | `archive\validation_rbac\batch_24di_accounts.json` | `_archive/exports/batch_24di_accounts.json` | Unused EXPORTS file / development asset |
| menu_visibility_test.png | `archive\validation_rbac\menu_visibility_test.png` | `_archive/exports/menu_visibility_test.png` | Unused EXPORTS file / development asset |
| PSG_PTC_24DI_50_Student_Login_Accounts.xlsx | `archive\validation_rbac\PSG_PTC_24DI_50_Student_Login_Accounts.xlsx` | `_archive/exports/PSG_PTC_24DI_50_Student_Login_Accounts.xlsx` | Unused EXPORTS file / development asset |
| PSG_PTC_Staff_Admin_Login_Accounts.xlsx | `archive\validation_rbac\PSG_PTC_Staff_Admin_Login_Accounts.xlsx` | `_archive/exports/PSG_PTC_Staff_Admin_Login_Accounts.xlsx` | Unused EXPORTS file / development asset |
| role_routing_test.png | `archive\validation_rbac\role_routing_test.png` | `_archive/exports/role_routing_test.png` | Unused EXPORTS file / development asset |
| session_isolation_test.png | `archive\validation_rbac\session_isolation_test.png` | `_archive/exports/session_isolation_test.png` | Unused EXPORTS file / development asset |
| staff_admin_accounts.json | `archive\validation_rbac\staff_admin_accounts.json` | `_archive/exports/staff_admin_accounts.json` | Unused EXPORTS file / development asset |
| staff_navigation.png | `archive\validation_rbac\staff_navigation.png` | `_archive/exports/staff_navigation.png` | Unused EXPORTS file / development asset |
| student_navigation.png | `archive\validation_rbac\student_navigation.png` | `_archive/exports/student_navigation.png` | Unused EXPORTS file / development asset |
| tutor_navigation.png | `archive\validation_rbac\tutor_navigation.png` | `_archive/exports/tutor_navigation.png` | Unused EXPORTS file / development asset |

## Files Kept in Production

| File Path | Category / Usage |
| :--- | :--- |
| `about.html` | Active Code Component |
| `academic_erp.php` | Active Code Component |
| `academic_perform.php` | Active Code Component |
| `addmission.php` | Active Code Component |
| `admin_controls.php` | Active Code Component |
| `admission_status.php` | Active Code Component |
| `api/erp_forms_counts.php` | Active API Endpoint |
| `api/marks_updates.php` | Active API Endpoint |
| `api/notifications_poll.php` | Active API Endpoint |
| `applications.php` | Active Code Component |
| `assets/DIT_IQAC_2023_2024_Edit3-feb2025-update.docx` | Active Asset |
| `assets/erp.css` | Active Asset |
| `composer.json` | Active Code Component |
| `composer.lock` | Active Code Component |
| `composer.phar` | Active Code Component |
| `curr_gap.php` | Active Code Component |
| `dashboard_admin.php` | Active Code Component |
| `dashboard_staff.php` | Active Code Component |
| `dashboard_student.php` | Active Code Component |
| `dashboard_super.php` | Active Code Component |
| `dashboard_tutor.php` | Active Code Component |
| `database/iqac_live_current_full_utf8.sql` | Latest SQL Database |
| `departments.php` | Active Code Component |
| `dp_connection.php` | Active Code Component |
| `edit.php` | Active Code Component |
| `erp_forms.php` | Active Code Component |
| `forgot_password.php` | Active Code Component |
| `gallery.html` | Active Code Component |
| `gallery.php` | Active Code Component |
| `home.html` | Active Code Component |
| `include/auth.php` | Core Include / Helper |
| `include/compat.php` | Core Include / Helper |
| `include/dp_connection.php` | Core Include / Helper |
| `include/erp_forms_config.php` | Core Include / Helper |
| `include/iqac_document_styles.php` | Core Include / Helper |
| `include/navigation.php` | Core Include / Helper |
| `include/nba_document_export.php` | Core Include / Helper |
| `include/student_portal_ui.php` | Core Include / Helper |
| `index.php` | Active Code Component |
| `iqac_document_styles.php` | Active Code Component |
| `login.php` | Active Code Component |
| `logout.php` | Active Code Component |
| `marks_entry.php` | Active Code Component |
| `mou_index.php` | Active Code Component |
| `nba_report.php` | Active Code Component |
| `password_change.php` | Active Code Component |
| `password_resets.php` | Active Code Component |
| `print_nba_report.php` | Active Code Component |
| `reg.php` | Active Code Component |
| `register.php` | Active Code Component |
| `scriptgallery.js` | Active Code Component |
| `semester_details.php` | Active Code Component |
| `slogin.php` | Active Code Component |
| `staff_fdp.php` | Active Code Component |
| `staff_upload.php` | Active Code Component |
| `std_achiev.php` | Active Code Component |
| `std_higher.php` | Active Code Component |
| `std_index.php` | Active Code Component |
| `std_indus.php` | Active Code Component |
| `std_partici.php` | Active Code Component |
| `std_percent.php` | Active Code Component |
| `std_pro.php` | Active Code Component |
| `std_publication.php` | Active Code Component |
| `std_sports_details.php` | Active Code Component |
| `std_upload.php` | Active Code Component |
| `student_dashboard.php` | Active Code Component |
| `styleabout.css` | Active Code Component |
| `stylegallery.css` | Active Code Component |
| `successrate.php` | Active Code Component |
| `tech.php` | Active Code Component |
| `tools/__pycache__/nba_docx_export.cpython-312.pyc` | Active Python Export Tool |
| `tools/nba_docx_export.py` | Active Python Export Tool |
| `upload.php` | Active Code Component |
| `uploads/1754738026_logo1.png` | Active Uploaded Attachment |
| `uploads/1754740432_1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/1754740960_1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/1754740966_1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/1754742125_1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/1754921615_AIBASEDHUMANEMOTIONALFACIALDETECTION-may-2025.docx` | Active Uploaded Attachment |
| `uploads/1754922104_javakey.docx` | Active Uploaded Attachment |
| `uploads/1754922228_PHOTO-2025-04-22-17-13-27.jpg` | Active Uploaded Attachment |
| `uploads/1754922264_PHOTO-2025-04-22-17-13-27.jpg` | Active Uploaded Attachment |
| `uploads/1754922270_PHOTO-2025-04-22-17-13-27.jpg` | Active Uploaded Attachment |
| `uploads/1754922296_PHOTO-2025-04-22-17-13-27.jpg` | Active Uploaded Attachment |
| `uploads/1754922532_PHOTO-2025-04-22-17-13-28.jpg` | Active Uploaded Attachment |
| `uploads/1754922765_PHOTO-2025-04-22-17-13-28.jpg` | Active Uploaded Attachment |
| `uploads/1754969547_PHOTO-2025-04-22-17-13-27.jpg` | Active Uploaded Attachment |
| `uploads/certificates/cert_68a455742c5fe.jpg` | Active Uploaded Attachment |
| `uploads/certificates/cert_68f65d562ed05.jpg` | Active Uploaded Attachment |
| `uploads/certificates/cert_68f65e0d06c7a.jpg` | Active Uploaded Attachment |
| `uploads/certificates/cert_68f65e4769993.jpg` | Active Uploaded Attachment |
| `uploads/certificates/cert_68f65e84c2ae4.jpg` | Active Uploaded Attachment |
| `uploads/images/1754561133_IMG-20250713-WA0002.jpg` | Active Uploaded Attachment |
| `uploads/images/1754561194_IMG-20250713-WA0002.jpg` | Active Uploaded Attachment |
| `uploads/images/1754561227_IMG-20250713-WA0002.jpg` | Active Uploaded Attachment |
| `uploads/images/1754561286_IMG-20250713-WA0002.jpg` | Active Uploaded Attachment |
| `uploads/images/1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/images/1754738026_image1.jpg` | Active Uploaded Attachment |
| `uploads/images/1754740432_1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/images/1754740960_1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/images/1754740966_1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/images/1754742125_1754561416_IMG-20250713-WA0006.jpg` | Active Uploaded Attachment |
| `uploads/images/1754921615_logo.png` | Active Uploaded Attachment |
| `uploads/images/1754922104_yoga.jpg` | Active Uploaded Attachment |
| `uploads/images/1754922228_yoga.jpg` | Active Uploaded Attachment |
| `uploads/images/1754922264_yoga.jpg` | Active Uploaded Attachment |
| `uploads/images/1754922270_yoga.jpg` | Active Uploaded Attachment |
| `uploads/images/1754922296_yoga.jpg` | Active Uploaded Attachment |
| `uploads/images/1754922532_yoga.jpg` | Active Uploaded Attachment |
| `uploads/images/1754922881_wp13397951-asus-gaming-4k-wallpapers.jpg` | Active Uploaded Attachment |
| `uploads/images/1754931315_wp6855493-rog-iphone-wallpapers.jpg` | Active Uploaded Attachment |
| `uploads/images/1754969547_NeonRain_1920x1080.jpg` | Active Uploaded Attachment |
| `uploads/images/image1.jpg` | Active Uploaded Attachment |
| `uploads/images/image2.jpg` | Active Uploaded Attachment |
| `uploads/images/image3.JPG` | Active Uploaded Attachment |
| `uploads/images/image4.JPG` | Active Uploaded Attachment |
| `uploads/images/logo1.png` | Active Uploaded Attachment |
| `uploads/images/logo2.png` | Active Uploaded Attachment |
| `uploads/mark_sheets/68a45698860d2_1754738026_logo1.png` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f669b7b8d6b_IMG-20250713-WA0007.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f66c981a250_IMG-20250713-WA0007.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f66d19de7dc_IMG-20250713-WA0007.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f66d3c72971_IMG-20250713-WA0007.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f66e1f670e6_IMG-20250713-WA0007.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f784e87fabf_IMG-20250713-WA0008.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f786258bee2_IMG-20250713-WA0008.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f78692e789b_IMG-20250713-WA0008.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f7915791887_IMG-20250713-WA0008.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f792a742581_IMG-20250713-WA0008.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f793747727a_IMG-20250713-WA0008.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f8db12d3443_IMG-20250713-WA0005.jpg` | Active Uploaded Attachment |
| `uploads/mark_sheets/68f8dbf71385e_IMG-20250713-WA0005.jpg` | Active Uploaded Attachment |
| `uploads/photos/photo_68a455742c8d2.png` | Active Uploaded Attachment |
| `uploads/photos/photo_68f65d562f444.jpg` | Active Uploaded Attachment |
| `uploads/photos/photo_68f65e0d074ca.jpg` | Active Uploaded Attachment |
| `uploads/photos/photo_68f65e4769f6b.jpg` | Active Uploaded Attachment |
| `uploads/photos/photo_68f65e84c31d6.jpg` | Active Uploaded Attachment |
| `uploads/staff/staff_68a421856b39b.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0aef1efc10.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0b006aeb0e.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0b1754bf96.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0b216d3a30.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0b265ce30a.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0b2acb1d47.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0b3cd22b1c.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0b5c4c1f57.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a0b60904f11.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a4188d3ffb6.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a418a37b6c1.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a4198d752c5.png` | Active Uploaded Attachment |
| `uploads/students/student_68a41a0573281.png` | Active Uploaded Attachment |
| `uploads/students/student_68a41ac88f5b3.png` | Active Uploaded Attachment |
| `uploads/students/student_68a41b11a6aad.png` | Active Uploaded Attachment |
| `uploads/students/student_68a41bbe9d57a.png` | Active Uploaded Attachment |
| `uploads/students/student_68a41bcc88a03.png` | Active Uploaded Attachment |
| `uploads/students/student_68a42280672de.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a423325b92b.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a42390c6acb.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a4253df36c6.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a425f9b487e.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a4265966b7f.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a427c4e2da9.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a4287381263.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a42a6e94768.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a44c5e432a8.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68a44ddc92260.png` | Active Uploaded Attachment |
| `uploads/students/student_68a44f4d340c5.png` | Active Uploaded Attachment |
| `uploads/students/student_68a44fdb359b1.png` | Active Uploaded Attachment |
| `uploads/students/student_68a4514002dbd.png` | Active Uploaded Attachment |
| `uploads/students/student_68a451a45981f.png` | Active Uploaded Attachment |
| `uploads/students/student_68a4552e77c22.png` | Active Uploaded Attachment |
| `uploads/students/student_68f659ab24333.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68f65b6c3082f.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68f65bc19f36c.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68f65c1006af2.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68f65c98d972d.jpg` | Active Uploaded Attachment |
| `uploads/students/student_68f65cc850836.jpg` | Active Uploaded Attachment |
| `view.php` | Active Code Component |
| `view_acd.php` | Active Code Component |
| `view_sem.php` | Active Code Component |
| `view_std.php` | Active Code Component |
| `view_student.php` | Active Code Component |
