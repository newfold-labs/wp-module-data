<?php

namespace NewfoldLabs\WP\Module\Data\EventQueue;

use NewfoldLabs\WP\Module\Data\EventQueue\Queues\BatchQueue;
use NewfoldLabs\WP\Module\Data\WPUnitTestCase;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\EventQueue\EventQueue
 */
class EventQueueTest extends WPUnitTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::getInstance
	 * @covers ::container
	 */
	public function test_container(): void {

		/** @var \wpdb $wpdb */
		global $wpdb;
		$wpdb->prefix = 'test_';

		$event_queue_instance = EventQueue::getInstance();

		$sut = $event_queue_instance->container();

		$this->assertEquals( 3, $sut->count() );

		$this->assertTrue( $sut->has( 'query' ) );
		$this->assertTrue( $sut->has( 'table' ) );

		$this->assertEquals( 'test_nfd_data_event_queue', $sut->get( 'table' ) );
	}

	/**
	 * @covers ::queue
	 */
	public function test_queue(): void {
		$event_queue_instance = EventQueue::getInstance();

		$sut = $event_queue_instance->queue();

		$this->assertInstanceOf( BatchQueue::class, $sut );
	}
}
