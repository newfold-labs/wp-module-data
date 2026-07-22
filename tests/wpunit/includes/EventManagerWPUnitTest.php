<?php

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\EventManager
 */
class EventManagerWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Tear down the test case.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		Mockery::resetContainer();
	}

	/**
	 * Count the `cron_schedules` callbacks registered by a given EventManager instance.
	 *
	 * @param EventManager $sut The instance whose callbacks should be counted.
	 *
	 * @return int
	 */
	protected function count_schedule_callbacks( EventManager $sut ): int {
		global $wp_filter;

		if ( ! isset( $wp_filter['cron_schedules'] ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $wp_filter['cron_schedules']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] === $sut ) {
					++$count;
				}
			}
		}

		return $count;
	}

	/**
	 * The schedule is now registered from two places: `Data::start()` on every request, and
	 * `EventManager::initialize_cron()` when the site is connected to Hiive. WordPress keys filter
	 * callbacks by object hash + method name, so the second registration replaces the first rather
	 * than appending a duplicate. This asserts that, and that `minutely` resolves exactly once.
	 *
	 * @covers ::register_cron_schedule
	 */
	public function test_registering_the_schedule_twice_does_not_duplicate_the_callback(): void {
		$sut = new EventManager();

		$this->assertSame( 0, $this->count_schedule_callbacks( $sut ) );

		// The Data::start() registration.
		$sut->register_cron_schedule();
		$this->assertSame( 1, $this->count_schedule_callbacks( $sut ) );

		// The connected-site registration, via EventManager::init().
		$initialize_cron = new \ReflectionMethod( $sut, 'initialize_cron' );
		$initialize_cron->setAccessible( true );
		$initialize_cron->invoke( $sut );

		$this->assertSame( 1, $this->count_schedule_callbacks( $sut ) );

		$schedules = wp_get_schedules();
		$this->assertArrayHasKey( 'minutely', $schedules );
		$this->assertSame( MINUTE_IN_SECONDS, $schedules['minutely']['interval'] );
	}

	/**
	 * 2.6.0 was released with a bug where an empty array was passed to the Queue to be saved, causing a fatal error.
	 *
	 * "Undefined offset: 0 at includes/EventQueue/Queryable.php:38"
	 *
	 * @see https://github.com/newfold-labs/wp-module-data/releases/tag/2.6.0
	 *
	 * @covers ::shutdown
	 * @covers ::send_request_events
	 */
	public function test_empty_response_from_hiive(): void {
		$sut = new EventManager();

		$event           = Mockery::mock( Event::class );
		$event->category = 'admin';
		$event->key      = 'plugin_search';
		$event->data     = array(
			'type'  => 'term',
			'query' => 'seo',
		);

		$sut->push( $event );

		$hiive_connection = Mockery::mock( HiiveConnection::class );
		$hiive_connection->expects( 'notify' )
			->once()
			->andReturn(
				array(
					'succeededEvents' => array(),
					'failedEvents'    => array(),
				)
			);

		$sut->add_subscriber( $hiive_connection );

		$sut->shutdown();
	}

	/**
	 * Event keys that are deliberately dropped in shutdown().
	 *
	 * @return array<array<string>>
	 */
	public static function does_not_send_certain_events_dataprovider(): array {
		return array(
			array( 'pageview' ),
			array( 'page_view' ),
			array( 'wp_mail' ),
			array( 'plugin_updated' ),
		);
	}

	/**
	 * @dataProvider does_not_send_certain_events_dataprovider
	 *
	 * @param string $event_name The event key that should not be sent.
	 */
	public function test_does_not_send_certain_events( string $event_name ): void {
		$sut = new EventManager();

		$event           = Mockery::mock( Event::class );
		$event->category = 'admin';
		$event->key      = $event_name;
		$event->data     = array();

		$sut->push( $event );

		$hiive_connection = Mockery::mock( HiiveConnection::class );
		$hiive_connection->shouldReceive( 'notify' )->never();

		$sut->add_subscriber( $hiive_connection );

		$sut->shutdown();
	}
}
