<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

/**
 * Monitors wp_mail events.
 */
class WPMail extends Listener {

	/**
	 * Register wp_mail_* hooks for the subscriber.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_mail_succeeded', array( $this, 'mail_succeeded' ), 10, 1 );
	}

	/**
	 * Mail sent successfully.
	 *
	 * @param array $mail_data An array containing the email recipient(s), subject, message, headers, and attachments.
	 * @return void
	 */
	public function mail_succeeded( $mail_data ) {

		$send_event       = false;
		$site_admin_email = get_option( 'admin_email' );

		$recipients = $mail_data['to'];

		if ( ! is_array( $recipients ) ) {
			$recipients = array( $recipients );
		}

		foreach ( $recipients as $email ) {
			$email = trim( $email );

			if ( $email === $site_admin_email ) {
				$send_event = true;
				break;
			}

			$user = get_user_by( 'email', $email );

			if ( $user && ( in_array( 'administrator', $user->roles, true ) || in_array( 'editor', $user->roles, true ) ) ) {
				$send_event = true;
				break;
			}
		}

		if ( $send_event ) {
			$this->push(
				'wp_mail',
				array(
					'label_key' => 'subject',
					'subject'   => $mail_data['subject'],
				)
			);
		}
	}
}
