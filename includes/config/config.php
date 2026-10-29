<?php /* Starts the configuration loader. */ ?>
<?php
/**
 * Configuration loader.
 *
 * The original config.php is split into modules under modules/. They are
 * loaded in the same order as the original file, so constants, functions and
 * bootstrap code keep the same behavior.
 */

require_once __DIR__ . '/modules/00-settings.php';
require_once __DIR__ . '/modules/10-ui-and-urls.php';
require_once __DIR__ . '/modules/20-workflow-definitions.php';
require_once __DIR__ . '/modules/25-prototype-store.php';
require_once __DIR__ . '/modules/30-people.php';
require_once __DIR__ . '/modules/31-authentication.php';
require_once __DIR__ . '/modules/40-applications.php';
require_once __DIR__ . '/modules/41-documents.php';
require_once __DIR__ . '/modules/42-templates.php';
require_once __DIR__ . '/modules/43-database-bridge.php';
require_once __DIR__ . '/modules/44-helpers.php';
require_once __DIR__ . '/modules/45-pools.php';
require_once __DIR__ . '/modules/46-schedules.php';
require_once __DIR__ . '/modules/50-progress.php';
require_once __DIR__ . '/modules/51-milestones.php';
require_once __DIR__ . '/modules/60-coordinator-queries.php';
require_once __DIR__ . '/modules/61-drafts.php';
require_once __DIR__ . '/modules/62-requirements.php';
require_once __DIR__ . '/modules/99-bootstrap.php';
