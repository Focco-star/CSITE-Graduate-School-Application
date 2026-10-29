# Shared includes

The shared PHP files are grouped by responsibility:

| Folder | Files | Purpose |
|---|---|---|
| `config/` | `config.php`, `db.php`, `google_config.php`, `modules/` | Configuration loader, database gateway, Google client ID, and 19 numbered modules for settings, people, applications, documents, workflow, schedules, and requirements. |
| `layout/` | `header.php`, `footer.php`, `sidebar.php`, `slideshow.php` | Shared page shell, role-based navigation, assets, and login slideshow. |
| `components/` | `calendar.php`, `program_chart.php`, `template_library.php` | Reusable UI/data components used by more than one page. |
| `integrations/` | `gdrive.php`, `google.php`, `template_sync.php` | Google Drive template sync and Google Sign-In. |

Pages should load these files with their new paths, for example:

```php
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/config/db.php';
require_once __DIR__ . '/../includes/layout/header.php';
require_once __DIR__ . '/../includes/layout/footer.php';
```

`config/config.php` is a loader; the actual configuration and domain functions are split into `config/modules/` in the original load order. The file contents and behavior are otherwise unchanged.
