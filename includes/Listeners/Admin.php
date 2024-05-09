<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

/**
 * Monitors generic admin events
 */
class Admin extends Listener {

	/**
	 * Register all required hooks for the listener category
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Admin pages
		add_action( 'admin_footer', array( $this, 'view' ), 9 );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'view' ) );

		// Login
		add_action( 'wp_login', array( $this, 'login' ), 10, 2 );

		// Logout
		add_action( 'wp_logout', array( $this, 'logout' ) );
	}

	/**
	 * Default admin event
	 *
	 * @return void
	 */
	public function view() {
		global $title;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$this->push(
			'pageview',
			array(
				'page'       => get_site_url( null, $_SERVER['REQUEST_URI'] ),
				'page_title' => $title,
			)
		);
	}

	/**
	 * Login
	 *
	 * @hooked wp_login
	 *
	 * @param string   $user_login username
	 * @param \WP_User $user logged in user info
	 */
	public function login( $user_login, $user ): void {
		$is_admin = array_key_exists( 'administrator', $user->get_role_caps() );
		if ( ( $is_admin && $user->get_role_caps()['administrator'] ) || ( $user->get_role_caps() && $user->get_role_caps()['manage_options'] ) ) {
			$this->push( 'login' );
		}
	}

	/**
	 * Logout
	 *
	 * @return void
	 */
	public function logout() {
		$this->push( 'logout' );
	}
}
