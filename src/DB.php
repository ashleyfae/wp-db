<?php
/**
 * DB.php
 *
 * @package   wp-db
 * @copyright Copyright (c) 2022, Ashley Gibson
 * @license   GPL2+
 */

namespace Ashleyfae\WPDB;

use Ashleyfae\WPDB\Exceptions\DatabaseQueryException;

/**
 * Static wrapper for `\wpdb`.
 * Taken from GiveWP.
 *
 * Common individual static methods are declared in order to document the {@throws} properly.
 * {@see static::__callStatic()} exists as a fallback.
 */
class DB
{
    /**
     * Returns an instance of wpdb.
     *
     * @return \wpdb
     */
    public static function getInstance(): \wpdb
    {
        global $wpdb;

        return $wpdb;
    }

    /**
     * Fallback for any wpdb methods not explicitly declared below.
     *
     * @since 1.0
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     * @throws DatabaseQueryException
     */
    public static function __callStatic($name, $arguments)
    {
        return self::runQueryWithErrorChecking(
            function () use ($name, $arguments) {
                return call_user_func_array([static::getInstance(), $name], $arguments);
            }
        );
    }

    /**
     * Runs the dbDelta function.
     *
     * @see dbDelta()
     *
     * @param  string  $delta
     *
     * @return array
     * @throws DatabaseQueryException
     */
    public static function delta(string $delta): array
    {
        return self::runQueryWithErrorChecking(
            function () use ($delta) {
                return dbDelta($delta);
            }
        );
    }

    /**
     * Prepares the query.
     *
     * @param $query
     * @param ...$args
     *
     * @return string|void
     */
    public static function prepare($query, ...$args)
    {
        return static::getInstance()->prepare($query, ...$args);
    }

    /**
     * @return int|bool
     * @throws DatabaseQueryException
     */
    public static function query(string $query)
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->query($query)
        );
    }

    /**
     * @param array|string $format
     * @return int|false
     * @throws DatabaseQueryException
     */
    public static function insert(string $table, array $data, $format)
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->insert($table, $data, $format)
        );
    }

    /**
     * @param array|string $where_format
     * @return int|false
     * @throws DatabaseQueryException
     */
    public static function delete(string $table, array $where, $where_format)
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->delete($table, $where, $where_format)
        );
    }

    /**
     * @param array|string|null $format
     * @param array|string|null $where_format
     * @return int|false
     * @throws DatabaseQueryException
     */
    public static function update(string $table, array $data, array $where, $format = null, $where_format = null)
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->update($table, $data, $where, $format, $where_format)
        );
    }

    /**
     * @param array|string $format
     * @return int|false
     * @throws DatabaseQueryException
     */
    public static function replace(string $table, array $data, $format)
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->replace($table, $data, $format)
        );
    }

    /**
     * @throws DatabaseQueryException
     */
    public static function get_var(string $query = null, int $x = 0, int $y = 0): ?string
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->get_var($query, $x, $y)
        );
    }

    /**
     * @return array|object|null
     * @throws DatabaseQueryException
     */
    public static function get_row(string $query = null, string $output = OBJECT, int $y = 0)
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->get_row($query, $output, $y)
        );
    }

    /**
     * @throws DatabaseQueryException
     */
    public static function get_col(string $query = null, int $x = 0): array
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->get_col($query, $x)
        );
    }

    /**
     * @return array|object|null
     * @throws DatabaseQueryException
     */
    public static function get_results(string $query = null, string $output = OBJECT)
    {
        return self::runQueryWithErrorChecking(
            fn() => static::getInstance()->get_results($query, $output)
        );
    }

    public static function get_charset_collate(): string
    {
        return static::getInstance()->get_charset_collate();
    }

    public static function esc_like(string $text): string
    {
        return static::getInstance()->esc_like($text);
    }

    /**
     * Get last insert ID
     *
     * @since 1.0
     * @return int
     */
    public static function lastInsertId(): int
    {
        return (int) static::getInstance()->insert_id;
    }

    /**
     * Applies the table prefix to a given table name.
     *
     * @param  string  $tableName
     *
     * @return string
     */
    public static function applyPrefix(string $tableName): string
    {
        return static::getInstance()->prefix.$tableName;
    }

    /**
     * Runs a query callable and checks to see if any unique SQL errors occurred when it was run
     *
     * @since 1.0
     *
     * @param  Callable  $queryCaller
     *
     * @return mixed
     * @throws DatabaseQueryException
     */
    private static function runQueryWithErrorChecking(callable $queryCaller)
    {
        global $EZSQL_ERROR;
        require_once ABSPATH.'wp-admin/includes/upgrade.php';

        $errorCount    = is_array($EZSQL_ERROR) ? count($EZSQL_ERROR) : 0;
        $hasShowErrors = static::getInstance()->hide_errors();

        $output = $queryCaller();

        if ($hasShowErrors) {
            static::getInstance()->show_errors();
        }

        $wpError = self::getQueryErrors($errorCount);

        if (! empty($wpError->errors)) {
            throw new DatabaseQueryException($wpError->get_error_message());
        }

        return $output;
    }


    /**
     * Retrieves the SQL errors stored by WordPress
     *
     * @since 1.0
     *
     * @param  int  $initialCount
     *
     * @return \WP_Error
     */
    private static function getQueryErrors(int $initialCount = 0): \WP_Error
    {
        global $EZSQL_ERROR;

        $wpError = new \WP_Error();

        if (is_array($EZSQL_ERROR)) {
            for ($index = $initialCount, $indexMax = count($EZSQL_ERROR); $index < $indexMax; $index++) {
                $error = $EZSQL_ERROR[$index];

                if (
                    empty($error['error_str']) ||
                    empty($error['query']) ||
                    strpos($error['query'], 'DESCRIBE ') === 0
                ) {
                    continue;
                }

                $wpError->add('db_delta_error', $error['error_str']);
            }
        }

        return $wpError;
    }

}
