<?php

namespace ILJ\Database;

/**
 * Database wrapper for the database collation tool fix.
 *
 * @package ILJ\Database
 * @since   2.24.4
 */
class DatabaseCollation {

	const ILJ_DEFAULT_COLLATION = 'utf8mb4_unicode_ci';
	const ILJ_DEFAULT_CHARSET_COLLATE = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

	/**
	 * Get Collation to update to
	 *
	 * @param  bool $for_linkindex This parameter determines if the function is used for database creation or not.
	 * @return string
	 */
	public static function get_collation($for_linkindex = false) {
		global $wpdb;
		$collation = self::ILJ_DEFAULT_COLLATION;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- We need to use a direct query here.
		$query = $wpdb->prepare("SELECT t.table_collation AS TABLE_COLLATION, CCSA.CHARACTER_SET_NAME AS CHARACTER_SET_NAME FROM information_schema.tables AS t JOIN information_schema.collation_character_set_applicability AS CCSA ON t.table_collation = CCSA.collation_name WHERE t.table_schema = DATABASE() AND t.table_name = %s", $wpdb->posts);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- We need to use a direct query here.
		$result = $wpdb->get_row($query);
		
		if (!empty($result) && isset($result->TABLE_COLLATION, $result->CHARACTER_SET_NAME)) {
			if ($for_linkindex) {
				$collation = "DEFAULT CHARACTER SET " . $result->CHARACTER_SET_NAME . " COLLATE " . $result->TABLE_COLLATION;
				return $collation;
			} else {
				return $result->TABLE_COLLATION;
			}
		}

		return $for_linkindex ? self::ILJ_DEFAULT_CHARSET_COLLATE : self::ILJ_DEFAULT_COLLATION;
	}

	/**
	 * Modify the database collation
	 *
	 * @return void
	 */
	public static function modify_collation() {
		global $wpdb;
		$collation = self::get_collation();

		$sql = "SHOW TABLES LIKE '{$wpdb->prefix}ilj_%'";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is required to check table existence, and caching is not applicable.
		$res = $wpdb->get_col($sql);
		if (null !== $res) {
			foreach ($res as $table) {
				self::modify_table($table, $collation);
			}
			
		}
	}

	/**
	 * Modify the collation per database table
	 *
	 * @param  string $table          Name of the table to alter.
	 * @param  string $collation_term Collation term to update to.
	 * @return void
	 */
	private static function modify_table($table, $collation_term) {
		global $wpdb;
		$sql = "SHOW CREATE TABLE `{$table}`";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is required for schema retrieval, and caching does not apply.
		$create_table_res = $wpdb->get_row($sql, ARRAY_A);
		$create_table = $create_table_res['Create Table'];

		// determine current collation value
		$old_coll = '';
		preg_match('/ COLLATE=([a-zA-Z0-9._-]+)/i', $create_table, $collate_match);
		if (isset($collate_match[1]) && "" != $collate_match[1]) {
			$old_coll = $collate_match[1];
		}

		// check table collation and modify if it's an undesired algorithm
		if ($old_coll != $collation_term) {
			$sql = "ALTER TABLE `{$table}` COLLATE={$collation_term}";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is necessary for altering table structure; caching is not applicable for schema changes.
			$wpdb->query($sql);
		}

		$sql = "SHOW FULL COLUMNS FROM `{$table}`";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is necessary for retrieving table schema, caching is not applicable for this type of query.
		$columns_res = $wpdb->get_results($sql, ARRAY_A);
		if (null !== $columns_res) {
			foreach ($columns_res as $row) {
				// Skip non text fields.
				if (false === stripos($row['Type'], 'text')
					&& false === stripos($row['Type'], 'char')
					&& false === stripos($row['Type'], 'enum')
				) {
					continue;
				}

				if ($row['Collation'] != $collation_term) {
					$null = 'NO' === $row['Null'] ? 'NOT NULL' : 'NULL';
					$default = (null !== $row['Default']) ? " DEFAULT '{$row['Default']}' " : '';
					$sql = "ALTER TABLE `{$table}`
						CHANGE `{$row['Field']}` `{$row['Field']}` {$row['Type']} COLLATE {$collation_term} {$null} {$default}";
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query necessary for ALTER TABLE operation, caching is not applicable.
					$wpdb->query($sql);
				}
			}
		}
	}
}
