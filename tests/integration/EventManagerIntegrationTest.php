<?php

namespace NewfoldLabs\WP\Module\Data;

use NewfoldLabs\WP\Module\Data\EventQueue\EventQueue;
use NewfoldLabs\WP\Module\Data\EventQueue\Queues\BatchQueue;

class EventManagerIntegrationTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	public function set_up() {
		parent::set_up();

		BatchQueue::create_table();
	}

	/**
	 * Add an event to the queue, fail to send it three times, it should no longer be in the queue.
	 */
	public function test_queue_retries() {

		/**
		 * Function to check if an event with the given uid is present in the queued events.
		 */
		$is_event_present = function ( array $events, string $uid ): bool {
			return array_reduce(
				$events,
				function ( $carry, $event ) use ( $uid ) {
					return $carry ||
							( isset( $event->data['uid'] ) && $event->data['uid'] === $uid );
				},
				false
			);
		};

		$sut = new EventManager();
		$sut->add_subscriber( new HiiveConnection() );

		$starting_queue_count = EventQueue::getInstance()->queue()->count();

		$uid = uniqid( __METHOD__ );

		$event = new Event(
			'commerce',
			'product_created',
			array(
				'uid' => $uid,
			)
		);

		/**
		 * Adds the event to the object's {@see EventManager::queue}.
		 */
		$sut->push( $event );

		/**
		 * Runs {@see EventManager::send_request_events()} which calls {@see HiiveConnection::notify()}
		 * but since {@see HiiveConnection::is_connected()} is `false`, it will fail, and the event
		 * will be saved to {@see BatchQueue::push()}.
		 */
		$sut->shutdown();

		$queued_events = EventQueue::getInstance()->queue()->pull( $starting_queue_count + 1 );

		$this->assertTrue( $is_event_present( $queued_events, $uid ) );

		/**
		 * This will call {@see BatchQueue::increment_attempt()} -> attempts = 2.
		 */
		$sut->send_saved_events_batch();
		/**
		 * This will call {@see BatchQueue::increment_attempt()} -> attempts = 3.
		 */
		$sut->send_saved_events_batch();
		/**
		 * This will call {@see BatchQueue::remove_events_exceeding_attempts_limit(3)}, deleting the event.
		 */
		$sut->send_saved_events_batch();

		$queued_events = EventQueue::getInstance()->queue()->pull( $starting_queue_count + 1 );

		$this->assertFalse( $is_event_present( $queued_events, $uid ) );
	}
}
