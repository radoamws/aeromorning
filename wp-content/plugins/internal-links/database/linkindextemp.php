<?php
namespace ILJ\Database;

use ILJ\Enumeration\LinkType;

/**
 * Database wrapper for the linkindex table
 *
 * @package ILJ\Database
 * @since   1.3.10
 */
class LinkindexTemp {

	const ILJ_DATABASE_TABLE_LINKINDEX_TEMP      = 'ilj_linkindex_temp';
	const ILJ_ACTION_AFTER_DELETE_LINKINDEX_TEMP = 'ilj_after_delete_linkindex_temp';

	public static function install_temp_db() {
		global $wpdb;
		$charset_collate = DatabaseCollation::get_collation(true);

		$query_linkindex = 'CREATE TABLE ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_TEMP . ' (
			`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
			`link_from` BIGINT(20) NULL,
			`link_to` BIGINT(20) NULL,
			`type_from` VARCHAR(45) NULL,
			`type_to` VARCHAR(45) NULL,
			`anchor` TEXT NULL,
			PRIMARY KEY (`id`),
			INDEX `link_from` (`link_from` ASC),
			INDEX `type_from` (`type_from` ASC),
			INDEX `type_to` (`type_to` ASC),
			INDEX `link_to` (`link_to` ASC)) ' . $charset_collate . ';';

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($query_linkindex);
	}

	/**
	 * Drops the current temporary table
	 *
	 * @return void
	 */
	public static function uninstall_temp_db() {
		global $wpdb;
		$query_linkindex = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_TEMP . ';';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is necessary for table deletion, and caching is not applicable.
		$wpdb->query($query_linkindex);

	}

	/**
	 * Cleans the whole index table
	 *
	 * @since  1.3.10
	 * @return void
	 */
	public static function flush() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is necessary for checking table existence in INFORMATION_SCHEMA, caching is not applicable.
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = %s AND table_name = %s",
			$wpdb->dbname,
			$wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_TEMP
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is necessary for checking table existence in INFORMATION_SCHEMA, caching is not applicable.
		$row = $wpdb->get_var($query);

		if (1 == $row) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Direct query necessary for table truncation, no dynamic values in query.
			$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_TEMP);
		}

	}

	/**
	 * Returns all post outlinks from linkindex table
	 *
	 * @since  1.0.1
	 * @param  int    $id   The post ID where outlinks should be retrieved
	 * @param  String $type
	 * @return array
	 */
	public static function getRules($id, $type) {
		if (!is_numeric($id)) {
			return array();
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Already prepared.
		$query = $wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_TEMP . ' linkindex WHERE linkindex.link_from = %d AND linkindex.type_from = %s', $id, $type);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is necessary, caching is not applicable for real-time data.
		return $wpdb->get_results($query);
	}

	/**
	 * Adds a post rule to the linkindex table
	 *
	 * @since  1.0.1
	 * @param  int    $link_from Post ID which gives the link
	 * @param  int    $link_to   Post ID where the link should point to
	 * @param  string $anchor    The anchor text which gets used for linking
	 * @param  string $type_from The type of asset which gives the link
	 * @param  string $type_to   The type of asset which receives the link
	 * @return void
	 */
	public static function addRule($link_from, $link_to, $anchor, $type_from, $type_to) {
		if (!is_integer((int) $link_from) || !is_integer((int) $link_to) || !is_string((string) $anchor)) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct database insert is necessary.
		$wpdb->insert(
			$wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_TEMP,
			array(
				'link_from' => $link_from,
				'link_to'   => $link_to,
				'anchor'    => $anchor,
				'type_from' => $type_from,
				'type_to'   => $type_to,
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Rename old ilj index table to temp and vice versa
	 *
	 * @return void
	 */
	public static function switchTableTemp() {
		global $wpdb;

		$linkindex_table = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
		$temp_table = $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_TEMP;
		$dummy_table = $wpdb->prefix . self::ILJ_DATABASE_TABLE_LINKINDEX_TEMP . '2';

		if (!$wpdb->get_var("SHOW TABLES LIKE '{$linkindex_table}'") && $wpdb->get_var("SHOW TABLES LIKE '{$temp_table}'")) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- We need to use a direct query here.
			$wpdb->query("RENAME TABLE {$temp_table} TO {$linkindex_table}");
		} elseif ($wpdb->get_var("SHOW TABLES LIKE '{$temp_table}'")) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- We need to use a direct query here.
			$wpdb->query("
				RENAME TABLE {$linkindex_table} TO {$dummy_table},
				{$temp_table} TO {$linkindex_table};
			");
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- We need to use a direct query here.
			$wpdb->query("RENAME TABLE {$dummy_table} TO {$temp_table};");
		}

		self::uninstall_temp_db();
	}
}
