<?php

namespace Endurance\WP\Module\Data;

/**
 * Subscriber interface for registering to receive event notifications
 */
interface SubscriberInterface {

	/**
	 * Method for handling receiving event data
	 *
	 * @param Event $event Event object representing data about the event that occurred
	 */
	public function notify( Event $event );
}
