<?php
namespace ILJ\Statistics;

use ILJ\Data\Content;
use ILJ\Database\Linkindex;

/**
 * Gets Link statistics
 *
 * Gets the link statistics for the dashboard
 *
 * @package ILJ\Statistics
 * @since   2.23.5
 */
class Link {

	/**
	 * Arguments for statistics query.
	 *
	 * @var array $args {
	 * @type int             $limit                  The number of rows which needs to be returned.
	 * @type int             $offset                 The number of rows which needs to be offset (used for pagination).
	 * @type string          $sort_by                The field which needs to be used for sorting, one of 'title', 'keywords_count', 'incoming_links', 'outgoing_links'
	 * @type string          $sort_direction         The sort direction, it can be ASC or DESC
	 * @type string          $search                 The search query (optional)
	 * @type array           $main_types             The main types filter (optional)
	 * @type array           $sub_types              The sub types filter (optional)
	 * }
	 */
	private $args;

	/**
	 * Constructor for {@link Link} class
	 *
	 * @param array $args {
	 * @type int             $limit                  The number of rows which needs to be returned.
	 * @type int             $offset                 The number of rows which needs to be offset (used for pagination).
	 * @type string          $sort_by                The field which needs to be used for sorting, one of 'title', 'keywords_count', 'incoming_links', 'outgoing_links'
	 * @type string          $sort_direction         The sort direction, it can be ASC or DESC
	 * @type string          $search                 The search query (optional)
	 * @type array           $main_types             The main types filter (optional)
	 * @type array           $sub_types              The sub types filter (optional)
	 * }
	 */
	public function __construct($args) {
		$this->args = wp_parse_args($args, array(
			'sort_by' => 'title',
			'sort_direction' => 'ASC',
			'limit' => 10,
			'offset' => 0,
			'search' => '',
			'types' => array('post', 'term'),
			'main_types' => array(),
			'sub_types' => array(),
		));
	}

	private function should_apply_main_types_filter() {
		return !empty($this->args['main_types']);
	}

	private function should_apply_sub_types_filter() {
		return !empty($this->args['sub_types']);
	}

	private function get_sql_escaped_main_types() {
		return sprintf("'%s'", implode("','", array_map('esc_sql', $this->args['main_types'])));
	}
	private function get_sql_escaped_sub_types() {
		return sprintf("'%s'", implode("','", array_map('esc_sql', $this->args['sub_types'])));
	}




	/**
	 * Get sort_by after validation.
	 *
	 * @return string
	 */
	private function get_sort_by() {
		$allowed_sorting_columns = array('title', 'keywords_count', 'incoming_links', 'outgoing_links');
		return in_array($this->args['sort_by'], $allowed_sorting_columns, true) ? $this->args['sort_by'] : 'title';
	}

	/**
	 * Get sort direction after validation.
	 *
	 * @return string
	 */
	private function get_sort_direction() {
		$allowed_sorting_directions = array('ASC', 'DESC');
		return in_array($this->args['sort_direction'], $allowed_sorting_directions, true) ? $this->args['sort_direction'] : 'ASC';
	}



	/**
	 * Return the query for sub_type, since the link statistics is now paginated, these types
	 * needs to be available in the query for filtering, the equivalent function is
	 * {@link \ILJ\Helper\IndexAsset::getDetailedType}
	 *
	 * @return string
	 */
	private static function get_sub_type_query() {
		$sub_type_query = "
		    CASE
		        WHEN idx.type = 'post' THEN items.entity_type
		        ELSE ''
		    END AS sub_type";

		
		return $sub_type_query;
	}

	/**
	 * Return a map of main_type and sub_type which will be used for filtering in the
	 * link statistics ui screen.
	 *
	 * @return array
	 */
	public static function get_types() {
		global $wpdb;

		$link_index_table_name = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
		$sub_type_query = self::get_sub_type_query();

		$term_query = "";
		$type_condition_query = "WHERE idx.type = items.type AND (idx.type != CONCAT(items.type, '_meta') OR items.entity_type != 'ilj_customlinks' OR items.entity_type != 'term')";
		
		

		$query = "
			SELECT
			    idx.type AS main_type,
			    {$sub_type_query}
			FROM
			    (
			        SELECT
			            p.ID AS id,
			            'post' AS type,
			            p.post_type as entity_type
			        FROM
			            $wpdb->posts p
			        LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'ilj_linkdefinition'
					WHERE p.post_status = 'publish'
			    	$term_query
			    ) items
			
			RIGHT JOIN (
			        SELECT DISTINCT link_from as id, type_from AS type FROM $link_index_table_name
					UNION
					SELECT DISTINCT link_to AS id, type_to AS type FROM $link_index_table_name
			) AS idx ON items.id = idx.id
			$type_condition_query
			GROUP BY main_type, sub_type
			";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is necessary for real-time data fetch and caching is not applicable for this use case.
		return $wpdb->get_results($query);
	}

	/**
	 * Returns the statistics for linkindex table.
	 *
	 * @return array
	 */
	public function get_statistics() {
		global $wpdb;
		$link_index_table_name = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;

		$term_query = "";
		$type_condition_query = "AND idx.type = items.type";

		
		$query = "
			SELECT * FROM (
					SELECT
					    items.id, idx.type AS main_type, items.type, items.keywords_count, items.title,
					    COALESCE(incoming_links.count, 0) AS incoming_links,
					    COALESCE(outgoing_links.count, 0) AS outgoing_links,
					    {$this->get_sub_type_query()}
					FROM
					    (
					        SELECT
					            p.ID AS id,
					            p.post_title AS title,
					            'post' AS type,
					            COALESCE(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value, 'a:', -1), ':', 1) AS SIGNED), 0) AS keywords_count,
					            p.post_type as entity_type
					        FROM
					            $wpdb->posts p
					        LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'ilj_linkdefinition'
							WHERE p.post_status = 'publish'
					       $term_query
					    ) items
					
					LEFT JOIN (
					        SELECT DISTINCT link_from AS id, type_from AS type FROM $link_index_table_name
							UNION
							SELECT DISTINCT link_to AS id, type_to AS type FROM $link_index_table_name
					) AS idx ON items.id = idx.id $type_condition_query
					
					LEFT JOIN (
					    SELECT link_to AS id, type_to AS TYPE, COUNT(1) AS count
					    FROM $link_index_table_name
					    GROUP BY link_to, type_to
					) AS incoming_links ON items.id = incoming_links.id AND idx.type = incoming_links.type
					
					LEFT JOIN (
					    SELECT link_from AS id, type_from AS TYPE, COUNT(1) AS count
					    FROM $link_index_table_name
					    GROUP BY link_from, type_from
					) AS outgoing_links ON items.id = outgoing_links.id AND idx.type = outgoing_links.type
			) AS results WHERE 1=1
";
		if (!empty($this->args['search'])) {
			$query .= " AND title LIKE %s";
		}
		if ($this->should_apply_main_types_filter() && $this->should_apply_sub_types_filter()) {
			$query .= " AND (main_type IN ({$this->get_sql_escaped_main_types()}) AND sub_type IN ({$this->get_sql_escaped_sub_types()}) )";
		}

		$query .= " AND (incoming_links > 0 OR outgoing_links > 0)";
		$query .= " ORDER BY {$this->get_sort_by()} {$this->get_sort_direction()} LIMIT %d OFFSET %d;";
		
		if (!empty($this->args['search'])) {
			$prepared_query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Already prepared.
				$query,
				!empty($this->args['search']) ? '%' . $wpdb->esc_like($this->args['search']) . '%' : '',
				$this->args['limit'],
				$this->args['offset']
			);
		} else {
			$prepared_query = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Already prepared
				$query,
				$this->args['limit'],
				$this->args['offset']
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is necessary for real-time data fetch and caching is not applicable for this use case.
		$results = $wpdb->get_results(
			$prepared_query,
			ARRAY_A
		);
		return array_map(function ($result) {
			$content = Content::from_content_type_and_id($result['type'], $result['sub_type'], $result['id']);
			$result['edit_link'] = $content->get_edit_link();
			$result['edit_title'] = $content->get_edit_title();
			$result['permalink'] = $content->get_permalink();
			$result['permalink_title'] = $content->get_permalink_title();
			return $result;
		}, $results);
	}

	/**
	 * Return the total number of filtered rows.
	 *
	 * @return int
	 */
	public function get_filtered_results_count() {
		global $wpdb;

		$link_index_table_name = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
		$query = "
			SELECT COUNT(1) FROM (
			SELECT
			idx.type as main_type,
			items.title,
			{$this->get_sub_type_query()}
			FROM
			    (
			        SELECT
			            p.ID AS id,
			            'post' AS type,
			            p.post_type as entity_type,
			            p.post_title AS title
			        FROM
			            $wpdb->posts p
			        LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'ilj_linkdefinition'
					WHERE p.post_status = 'publish'
			        UNION
			
			        SELECT
			            t.term_id AS id,
			            'term' AS type,
			            tt.taxonomy as entity_type,
			            t.name as title
			        FROM
			            $wpdb->terms t
			        LEFT JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
			    ) items
			
			RIGHT JOIN (
			        SELECT
				        link_from, type_from AS type
				    FROM
				        $link_index_table_name
				    GROUP BY
				        link_from,
				        type_from
			) AS idx ON items.id = idx.link_from  AND (idx.type = items.type OR idx.type = CONCAT(items.type, '_meta'))
			
			UNION

			SELECT
			idx.type as main_type,
			items.title,
			{$this->get_sub_type_query()}
			FROM
			    (
			        SELECT
			            p.ID AS id,
			            'post' AS type,
			            p.post_type as entity_type,
			            p.post_title AS title
			        FROM
			            $wpdb->posts p
			        LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'ilj_linkdefinition'
					WHERE p.post_status = 'publish'
			        UNION
			
			        SELECT
			            t.term_id AS id,
			            'term' AS type,
			            tt.taxonomy as entity_type,
			            t.name as title
			        FROM
			            $wpdb->terms t
			        LEFT JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
			    ) items
			
			RIGHT JOIN (
			        SELECT
				        link_to, type_to AS type
				    FROM
				        $link_index_table_name
				    GROUP BY
				        link_to,
				        type_to
			) AS idx ON items.id = idx.link_to  AND (idx.type = items.type OR idx.type = CONCAT(items.type, '_meta'))) AS results WHERE 1=1";

		if (!empty($this->args['search'])) {
			$query .= " AND title LIKE %s";
		}
		if ($this->should_apply_main_types_filter() && $this->should_apply_sub_types_filter()) {
			$query .= " AND (main_type IN ({$this->get_sql_escaped_main_types()}) AND sub_type IN ({$this->get_sql_escaped_sub_types()}) )";
		}
		if (!empty($this->args['search'])) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is necessary for real-time data fetch and caching is not applicable for this use case.
			return intval($wpdb->get_var($wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Already prepared.
				$query,
				'%' . $wpdb->esc_like($this->args['search']) . '%'
			)));
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is necessary for real-time data fetch and caching is not applicable for this use case.
			return intval($wpdb->get_var($query));
		}
	}

	/**
	 * Return the total number of rows in link statistics.
	 *
	 * @return int
	 */
	public function get_total() {
		global $wpdb;
		$link_index_table_name = $wpdb->prefix . Linkindex::ILJ_DATABASE_TABLE_LINKINDEX;
		$count_query = "
			SELECT COUNT(DISTINCT id, type) 
			FROM (
				SELECT
					items.id,
					CASE 
						WHEN idx.type = CONCAT(items.type, '_meta') THEN CONCAT(items.type, '_meta') 
						ELSE items.type
					END AS type
				FROM
				(
					SELECT
						p.ID AS id,
						'post' AS type
					FROM
						$wpdb->posts p
					WHERE p.post_status = 'publish'
					UNION
					SELECT
						t.term_id AS id,
						'term' AS type
					FROM
						$wpdb->terms t
				) items
				RIGHT JOIN (
					SELECT
						link_from AS id, 
						type_from AS type
					FROM
						$link_index_table_name
					GROUP BY
						link_from, type_from
				) AS idx 
				ON items.id = idx.id 
				AND (idx.type = items.type OR idx.type = CONCAT(items.type, '_meta'))

				UNION

				SELECT
					items.id,
					CASE 
						WHEN idx.type = CONCAT(items.type, '_meta') THEN CONCAT(items.type, '_meta') 
						ELSE items.type
					END AS type
				FROM
				(
					SELECT
						p.ID AS id,
						'post' AS type
					FROM
						$wpdb->posts p
					WHERE p.post_status = 'publish'
					UNION
					SELECT
						t.term_id AS id,
						'term' AS type
					FROM
						$wpdb->terms t
				) items
				RIGHT JOIN (
					SELECT
						link_to AS id, 
						type_to AS type
					FROM
						$link_index_table_name
					GROUP BY
						link_to, type_to
				) AS idx 
				ON items.id = idx.id 
				AND (idx.type = items.type OR idx.type = CONCAT(items.type, '_meta')  OR idx.type = 'custom')
			) AS combined_results;

		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is necessary for real-time data fetch and caching is not applicable for this use case.
		return intval($wpdb->get_var($count_query));
	}
}
