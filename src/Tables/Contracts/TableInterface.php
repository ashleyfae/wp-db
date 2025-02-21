<?php
/**
 * TableInterface.php
 *
 * @package   wp-db
 * @copyright Copyright (c) 2025, Ashley Gibson
 * @license   MIT
 */

namespace Ashleyfae\WPDB\Tables\Contracts;

interface TableInterface
{
    /**
     * Gets the package prefix.
     */
    public function getPackageTablePrefix(): string;

    /**
     * Returns the name of the table (without the WP prefix and without the package prefix).
     */
    public function getName(): string;

    /**
     * Returns the current version of this table, as defined in the code base (not DB).
     * This should be a unix timestamp representing when the table schema was last changed.
     */
    public function getVersion(): int;

    /**
     * Returns the schema used to create the table.
     */
    public function getSchema(): string;

    /**
     * Returns the fully qualified table name, with all prefixes applied.
     * Format:
     * {wp prefix}{package prefix}{table name}
     */
    public function getTableName(): string;

    /**
     * Updates or creates the database table if required.
     */
    public function maybeUpdateOrCreate(): void;
}
