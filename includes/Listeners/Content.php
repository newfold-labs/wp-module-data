<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

use WP_Query;

/**
 * Monitors page/post events
 */
class Content extends Listener {

	/**
	 * Register the hooks for the subscriber
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Post status transitions
		add_action( 'transition_post_status', array( $this, 'post_status' ), 10, 3 );

		add_filter( 'newfold_wp_data_module_cron_data_filter', array( $this, 'comments_count' ) );

	}

	/**
	 * Post status transition
	 *
	 * @param string   $new_status The new post status
	 * @param string   $old_status The old post status
	 * @param \WP_Post $post Post object
	 *
	 * @return void
	 */
	public function post_status( $new_status, $old_status, $post ) {

		$post_type = get_post_type_object( $post->post_type );

		/**
		 * Ignore all post types that aren't public
		 */
		if ( $post_type->public !== true ) {
			return;
		}

		$allowed_statuses = array(
			'draft',
			'pending',
			'publish',
			'new',
			'future',
			'private',
			'trash',
		);
		if ( $new_status !== $old_status && in_array( $new_status, $allowed_statuses, true ) ) {
			$data = array(
				'label_key'  => 'new_status',
				'old_status' => $old_status,
				'new_status' => $new_status,
				'post'       => $post,
			);
			$this->push( 'content_status', $data );

			if ( 'publish' === $new_status ) {
				$count = $this->count_posts();

				if ( 1 === $count ) {
					$this->push( 'first_post_published', array( 'post' => $post ) );
				}

				if ( 5 === $count ) {
					$this->push( 'fifth_post_published', array( 'post' => $post ) );
				}
			}
		}
	}

	/**
	 * Count published posts excluding the default 3: Sample Page, Hello World and the Privacy Page
	 *
	 * @return integer Number of published non-default posts
	 */
	public function count_posts() {
		$types = get_post_types( array( 'public' => true ) );
		$args  = array(
			'post_status'  => 'publish',
			'post_type'    => $types,
			'post__not_in' => array( 1, 2, 3 ),
		);
		$query = new WP_Query( $args );

		return $query->post_count;
	}

	/**
	 * Comments reviews count
	 *
	 * @param  string $data  Array of data to be sent to Hiive
	 *
	 * @return string Array of data
	 */
	public function comments_count( $data ) {
		if ( ! isset( $data['meta'] ) ) {
			$data['meta'] = array();
		}

		$comments = wp_count_comments();
		if( isset( $comments->all ) ) {
			$data['meta']['post_comments_count'] = (int)$comments->all;
		}
		return $data;
	}

}
