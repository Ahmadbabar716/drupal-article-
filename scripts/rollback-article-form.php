<?php

/**
 * @file
 * Rollback: restores the previous article form configuration.
 *
 * In a real release, rollback is simply re-importing the previous config
 * export (git revert + drush config:import). This script mirrors that for
 * the local demo. Run: ddev drush php:script scripts/rollback-article-form.php
 */

declare(strict_types=1);

\Drupal::state()->delete('article_workflow.form_improved');
echo "Live preview disabled. For a full rollback: git checkout the previous config/sync and run 'ddev drush config:import -y'.\n";
