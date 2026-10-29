# Stylesheet sections

`style.css` is the entry index. The actual rules are split by system area and
loaded in this order by `includes/header.php`:

| Order | File | Area |
|---|---|---|
| 1 | `shared/00-base.css` | Shared variables, layout, sidebar, top bar, public and login pages |
| 2 | `applications/10-student-application.css` | Student application workflow |
| 3 | `coordinator/20-applications-list.css` | Coordinator applications list |
| 4 | `coordinator/30-process-application.css` | Coordinator process application and stage slider |
| 5 | `coordinator/40-notifications.css` | Coordinator in-page notifications |
| 6 | `templates/50-templates.css` | Templates library, updates and records |
| 7 | `student/60-dashboard.css` | Student milestone tracker and dashboard |
| 8 | `student/70-application-page.css` | Student track indicator and application page |
| 9 | `shared/80-success-popup.css` | Shared success pop-up |
| 10 | `coordinator/90-charts-calendar.css` | Coordinator charts and schedule calendar |
| 11 | `student/95-portal-pages.css` | Student portal interface refresh |
| 12 | `coordinator/96-dashboard.css` | Coordinator dashboard |
| 13 | `applications/97-coordinator-table.css` | Coordinator applications table |
| 14 | `coordinator/98-edit-controls.css` | Coordinator edit controls |
| 15 | `applications/99-records.css` | Application records |
| 16 | `shared/pickers.css` | Shared option, date and time pickers |
| 17 | `shared/responsive.css` | Shared responsive top bar rules |

The order above is the original cascade order. Keep it when moving or adding
sections.
