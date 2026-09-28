# PSG PTC Cleanup Report

Generated: 2026-06-02

## Status

No files were deleted in this pass. This report identifies routes and UI references that should be removed only after approval and final click-path verification.

## Removed From Active UI

| Item | Location | Action Taken |
| --- | --- | --- |
| MOU dashboard card/link | `academic_erp.php` preserved modules section | Removed from active dashboard UI |
| Gallery legacy admin dashboard shortcut | `academic_erp.php` preserved modules section | Removed from active dashboard UI |
| Admin analytics exposure to staff/tutor | `academic_erp.php` | Wrapped criteria, analytics, result analytics, teacher mapping, batch report, admission analytics, admin exports, and preserved module cards behind admin/HOD/IQAC guard |
| Student access to ERP dashboard | `academic_erp.php` | Page guard remains non-student |

## Pending Removal Candidates

| File/Route | Reason | Risk Before Deletion |
| --- | --- | --- |
| `mou_index.php` | MOU module is no longer part of PSG PTC ERP scope | May still be directly reachable or linked from legacy pages |
| `mou.sql` | MOU database seed/schema no longer in active scope | Keep until DB archival is approved |
| `about.html` | Legacy MOU/about landing page | Static route may be bookmarked |
| `home.html` | Legacy MOU landing page | Static route may be bookmarked |
| `home.php` | Legacy MOU landing page | May be used by old navigation |
| `index.php` | Legacy MOU portal landing | Must verify whether Apache default route still depends on it |
| `gallery.html` | Legacy MOU gallery demo page | Static sample route |
| `styleabout.css` | Style for legacy about route | Remove only after `about.html` is removed |
| `stylehome.css` | Style for legacy home routes | Remove only after `home.html`/`home.php` are removed |
| `scriptgallery.js` | Legacy MOU gallery script | Remove only after gallery legacy pages are removed |
| `scriptdept.js` | Legacy MOU department script | Remove only after department MOU pages are removed |

## MOU-Related PHP Routes Requiring Decision

| Route | Current Concern | Recommended Decision |
| --- | --- | --- |
| `upload.php` | Currently used in staff menu as Gallery Upload and Department Upload, but page title/form still says MOU upload | Refactor to PSG PTC department/gallery upload before deleting any dependency |
| `gallery.php` | Still branded as MOU gallery and uses legacy navigation | Remove from non-admin UI now; decide whether to refactor as PSG PTC gallery or retire |
| `departments.php` | MOU department listing | Retire if not needed by PSG PTC ERP |
| `view.php` | MOU uploaded files viewer | Retire if not needed by PSG PTC ERP |
| `edit.php` | MOU edit route | Retire if not needed by PSG PTC ERP |
| `delete.php` | MOU delete route | Retire if not needed by PSG PTC ERP |

## Broken/High-Risk Links To Audit

| Source | Link |
| --- | --- |
| `about.html`, `gallery.html`, `home.html`, `home.php`, `mou_index.php`, `view.php`, `gallery.php`, `departments.php` | `about.html` / `home.html` legacy navigation |
| `index.php` | `mou.php`, `iqac.php` references may not exist or may be obsolete |
| Documentation files | Many references to the old MOU module remain informational only |

## Recommendation

Approve deletion only after:

1. Direct URL tests confirm these routes are no longer required.
2. Staff upload and gallery workflows are clarified as PSG PTC ERP workflows instead of MOU workflows.
3. A backup of removed files is created outside the web root.
4. Apache default landing behavior is verified if `index.php` is retired.

