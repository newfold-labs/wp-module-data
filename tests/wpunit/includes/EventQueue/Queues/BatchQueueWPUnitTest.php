<?php

namespace NewfoldLabs\WP\Module\Data\EventQueue\Queues;

use Mockery;
use NewfoldLabs\WP\Module\Data\Event;
use NewfoldLabs\WP\ModuleLoader\Container;
use WP_Forge\QueryBuilder\Query;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\EventQueue\Queues\BatchQueue
 */
class BatchQueueWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/** @var Container $container */
	protected $container;

	/** @var array<Event> $events */
	protected $events;

	protected function setUp(): void {
		parent::setUp();

		BatchQueue::create_table();

		$this->container = Mockery::mock( Container::class );
		$this->container->expects( 'get' )
						->zeroOrMoreTimes()
						->with( 'table' )
						->andReturn( 'wp_nfd_data_event_queue' );

		$this->container->expects( 'get' )
						->zeroOrMoreTimes()
						->with( 'query' )
						->andReturnUsing(
							function () {
								return new Query();
							}
						);

		$this->events[] = new Event(
			'Admin',
			'test-event',
			array( 'foo' => 'bar' ),
		);
	}

	protected function tearDown(): void {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE wp_nfd_data_event_queue' );

		Mockery::close();
		parent::tearDown();
	}

	/**
	 * @return array<\stdClass{id:int, event:string, attempts:int, reserved_at:string, available_at:string, created_at:string}>
	 */
	protected function get_raw_rows(): array {

		/** @var \wpdb $wpdb */
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i;',
				$wpdb->prefix . 'nfd_data_event_queue',
			)
		);

		return $results;
	}

	/**
	 * @covers ::push
	 */
	public function test_push() {

		$batch_queue = new BatchQueue( $this->container );

		$result = $batch_queue->push( $this->events );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$this->assertTrue( $result, $wpdb->last_query );

		$results = $this->get_raw_rows();

		$this->assertCount( 1, $results );

		$row = array_pop( $results );

		$db_event = unserialize( $row->event );

		$this->assertEquals( 'test-event', $db_event->key );
		$this->assertEquals( 1, $row->attempts );
	}

	/**
	 * @covers ::pull
	 */
	public function test_pull() {

		$batch_queue = new BatchQueue( $this->container );
		$result      = $batch_queue->push( $this->events );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$this->assertTrue( $result, $wpdb->last_query );

		/** @var array<Event> $results */
		$results = $batch_queue->pull( 1 );

		/** @var Event $row */
		$row = array_pop( $results );

		$this->assertEquals( 'test-event', $row->key );
	}

	/**
	 * @covers ::remove_events_exceeding_attempts_limit
	 */
	public function test_remove_events_exceeding_attempts_limit(): void {

		$batch_queue = new BatchQueue( $this->container );
		$batch_queue->push( $this->events );

		$events_from_db = $batch_queue->pull( 1 );

		$batch_queue->increment_attempt( array_keys( $events_from_db ) );
		$batch_queue->increment_attempt( array_keys( $events_from_db ) );
		$batch_queue->increment_attempt( array_keys( $events_from_db ) );

		$result = $batch_queue->remove_events_exceeding_attempts_limit( 2 );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$this->assertTrue( $result, $wpdb->last_query );

		$results = $this->get_raw_rows();

		$this->assertEmpty( $results );
	}

	/**
	 * @covers ::increment_attempt
	 */
	public function tests_increment_attempt(): void {

		$batch_queue = new BatchQueue( $this->container );
		$batch_queue->push( $this->events );

		$events_from_db = $batch_queue->pull( 1 );

		$result = $batch_queue->increment_attempt( array_keys( $events_from_db ) );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$this->assertTrue( $result, $wpdb->last_query );

		$results = $this->get_raw_rows();

		$row = array_pop( $results );

		$this->assertEquals( 2, $row->attempts );
	}

	/**
	 * @covers ::remove
	 */
	public function test_remove(): void {

		$batch_queue = new BatchQueue( $this->container );
		$batch_queue->push( $this->events );

		$events_from_db = $batch_queue->pull( 1 );

		$result = $batch_queue->remove( array_keys( $events_from_db ) );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$this->assertTrue( $result, $wpdb->last_query );

		$results = $this->get_raw_rows();

		$this->assertEmpty( $results );
	}

	/**
	 * @covers ::reserve
	 */
	public function test_reserve(): void {

		$batch_queue = new BatchQueue( $this->container );
		$batch_queue->push( $this->events );

		$events_from_db = $batch_queue->pull( 1 );

		$result = $batch_queue->reserve( array_keys( $events_from_db ) );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$this->assertTrue( $result, $wpdb->last_query );

		$results = $this->get_raw_rows();

		$row = array_pop( $results );

		$this->assertNotNull( $row->reserved_at );

		$time = strtotime( $row->reserved_at );

		$this->assertGreaterThan( time() - 10, $time );

		$events_from_db = $batch_queue->pull( 1 );

		$this->assertEmpty( $events_from_db );
	}

	/**
	 * @covers ::release
	 */
	public function test_release(): void {

		$batch_queue = new BatchQueue( $this->container );
		$batch_queue->push( $this->events );

		$events_from_db = $batch_queue->pull( 1 );
		$batch_queue->reserve( array_keys( $events_from_db ) );

		assert( 0 === count( $batch_queue->pull( 1 ) ) );

		$result = $batch_queue->release( array_keys( $events_from_db ) );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$this->assertTrue( $result, $wpdb->last_query );

		$results = $this->get_raw_rows();

		$row = array_pop( $results );

		$this->assertNull( $row->reserved_at );

		$events_from_db = $batch_queue->pull( 1 );
		$this->assertCount( 1, $events_from_db );
	}

	/**
	 * @covers ::count
	 */
	public function test_count(): void {

		$batch_queue = new BatchQueue( $this->container );
		$batch_queue->push( $this->events );
		$batch_queue->push( $this->events );
		$batch_queue->push( $this->events );

		$this->assertEquals(3, $batch_queue->count());
	}
}
