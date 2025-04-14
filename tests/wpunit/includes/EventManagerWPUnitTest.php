<?php

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use function NewfoldLabs\WP\ModuleLoader\container;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\EventManager
 */
class EventManagerWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	protected function tearDown(): void {
		parent::tearDown();
		\Mockery::close();
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
