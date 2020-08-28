<?php

namespace Endurance\WP\Module\Data;

/**
 * Class to manage event subscriptions
 */
class EventManager {

	/**
	 * List of default listener category classes
	 *
	 * @var array
	 */
	const LISTENERS = array(
		'\\Listeners\\Admin',
		'\\Listeners\\BHPlugin',
		'\\Listeners\\Content',
		'\\Listeners\\Jetpack',
		'\\Listeners\\Plugin',
		'\\Listeners\\Theme',
	);

	/**
	 * List of subscribers receiving event data
	 *
	 * @var array
	 */
	private $subscribers = array();

	/**
	 * Register a new event subscriber
	 *
	 * @param SubscriberInterface $subscriber Class subscribing to event updates
	 * @return void
	 */
	public function add_subscriber( SubscriberInterface $subscriber ) {
		$this->subscribers[] = $subscriber;
	}

	/**
	 * Returns filtered list of registered event subscribers
	 *
	 * @return array List of susbscriber classes
	 */
	public function get_subscribers() {
		return apply_filters( 'endurance_data_subscribers', $this->subscribers );
	}

	/**
	 * Return an array of registered listener classes
	 *
	 * @return array List of listener classes
	 */
	public function get_listeners() {
		return apply_filters( 'endurance_data_listeners', $this::LISTENERS );
	}

	/**
	 * Initialize event listener classes
	 *
	 * @return void
	 */
	public function initialize_listeners() {
		foreach ( $this->get_listeners() as $listener ) {
			$classname = __NAMESPACE__ . $listener;
			$class     = new $classname( $this );
			$class->register_hooks();
		}
	}

	/**
	 * Push event details out to subscribers
	 *
	 * @param Event $event Details about the action taken
	 *
	 * @return void
	 */
	public function push( Event $event ) {
		foreach ( $this->get_subscribers() as $subscriber ) {
			$subscriber->notify( $event );
		}
	}
}
