<?php
/**
 * AbstractTable.php
 *
 * @package   wp-db
 * @copyright Copyright (c) 2025, Ashley Gibson
 * @license   MIT
 */

namespace Ashleyfae\WPDB\Tables;

use Ashleyfae\WPDB\DB;
use Ashleyfae\WPDB\Tables\Contracts\TableInterface;

abstract class AbstractTable implements TableInterface
{
    /**
     * @inheritDoc
     */
    public function getTableName(): string
    {
        return DB::applyPrefix($this->getPackageTablePrefix().$this->getName());
    }

    /**
     * Returns the option name for storing the database version.
     */
    protected function getVersionCacheKey(): string
    {
        return sanitize_key('wpdb_table_'.$this->getTableName().'_version');
    }

    /**
     * Returns the current version number saved in the database, if any.
     */
    protected function getSavedVersion(): ?int
    {
        $saved = get_option($this->getVersionCacheKey(), null);

        return $saved ? (int) $saved : null;
    }

    /** @inheritDoc */
    public function maybeUpdateOrCreate(): void
    {
        $dbVersion = $this->getSavedVersion();
        if (! $dbVersion || version_compare($dbVersion, $this->getVersion(), '<')) {
            $this->updateOrCreate();
        }
    }

    /**
     * Creates the table if it doesn't exist, or updates it if it does.
     *
     * @since 4.0.0
     *
     * @return void
     */
    protected function updateOrCreate(): void
    {
        if (! function_exists('dbDelta')) {
            require_once ABSPATH.'wp-admin/includes/upgrade.php';
        }

        $charset = DB::get_charset_collate();
        dbDelta("CREATE TABLE {$this->getTableName()} ({$this->getSchema()}) {$charset}");

        update_option($this->getVersionCacheKey(), $this->getVersion());
    }
}
