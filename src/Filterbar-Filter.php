<?php

/**
 * Class Tribe__Events__Filterbar__Filters__Performer
 *
 * Based on Tribe__Events__Filterbar__Filters__Organizer
 */
class Tribe__Events__Filterbar__Filters__Performer extends Tribe__Events__Filterbar__Filter {
	public $type = 'select';

	public function get_admin_form() {
		$title = $this->get_title_field();
		$type  = $this->get_multichoice_type_field();

		return $title . $type;
	}

	protected function get_values() {
		/** @var wpdb $wpdb */
		global $wpdb;
		// get performer IDs associated with published events
		$performer_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE p.post_type=%s AND p.post_status='publish' AND m.meta_key=%s AND m.meta_value > 0",
				Tribe__Events__Main::POSTTYPE,
				Tribe__Extension__Performer_Linked_Post_Type::instance()->get_linked_post_type_custom_field_key()
			)
		);
		array_filter( $performer_ids );
		if ( empty( $performer_ids ) ) {
			return array();
		}

		/**
		 * Filter Total Performers in Filter Bar
		 * Use this with caution, this will load performers on the front-end, may be slow
		 * The base limit is 200 for safety reasons
		 *
		 *
		 * @parm int  200 posts per page limit
		 * @parm array $performer_ids   ids of performers attached to events
		 */
		$limit = apply_filters( Tribe__Extension__Performer_Linked_Post_Type::POST_TYPE_KEY . '_filter_bar_limit', 200, $performer_ids );

		$performers = get_posts(
			array(
				'post_type'        => Tribe__Extension__Performer_Linked_Post_Type::POST_TYPE_KEY,
				'posts_per_page'   => $limit,
				'suppress_filters' => false,
				'post__in'         => $performer_ids,
				'post_status'      => 'publish',
				'orderby'          => 'title',
				'order'            => 'ASC',
			)
		);

		$performers_array = array();
		foreach ( $performers as $performer ) {
			$performers_array[] = array(
				'name'  => $performer->post_title,
				'value' => $performer->ID,
			);
		}

		return $performers_array;
	}

	protected function setup_join_clause() {
		global $wpdb;
		$this->joinClause = $wpdb->prepare(
			"INNER JOIN {$wpdb->postmeta} AS performer_filter ON ({$wpdb->posts}.ID = performer_filter.post_id AND performer_filter.meta_key=%s)",
			Tribe__Extension__Performer_Linked_Post_Type::instance()->get_linked_post_type_custom_field_key()
		);
	}

	protected function setup_where_clause() {
		if ( is_array( $this->currentValue ) ) {
			$performer_ids = implode( ',', array_map( 'intval', $this->currentValue ) );
		} else {
			$performer_ids = esc_attr( $this->currentValue );
		}

		$this->whereClause = " AND performer_filter.meta_value IN ($performer_ids) ";
	}
}
