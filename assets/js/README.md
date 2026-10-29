# JavaScript sections

The shared browser script is split by responsibility and loaded in this order
by `includes/footer.php`:

| Order | File | Area |
|---|---|---|
| 1 | `shared/app.js` | Sidebar, tabs, modals, filters, uploads, success pop-up, pickers, select menus and shared UI behavior |
| 2 | `coordinator/population-dashboard.js` | Student Population charts used by the coordinator dashboards |
| 3 | `coordinator/schedule-calendar.js` | Presentation Schedule Calendar |

Student, Applications and Templates pages use the shared `shared/app.js`
behavior. Section folders are kept beside it for any future page-specific
scripts, so all JavaScript stays grouped by system area.
