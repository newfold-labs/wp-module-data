<?php

namespace NewfoldLabs\WP\Module\Data\API;

use NewfoldLabs\WP\Module\Data\EventManager;
use NewfoldLabs\WP\Module\Data\HiiveConnection;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\API\Events
 */
class EventsWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * @covers ::create_item
	 */
	public function test_create_item(): void {
		$hiive         = \Mockery::mock( HiiveConnection::class );
		$event_manager = \Mockery::mock( EventManager::class );

		$sut = new Events( $hiive, $event_manager );

		$event = array(
			'category' => 'admin',
			'action'   => 'plugin_search', // 'action' here is used as 'key' later.
			'data'     => array(
				'type'  => 'term',
				'query' => 'seo',
			),
		);

		$request_data = array_merge(
			$event,
			array(
				'queue' => false,
			)
		);

		$request = new \WP_REST_Request( 'POST', 'newfold-data/v1/events' );
		$request->set_body_params( $request_data );

		$notification = array(
			'id'         => 'abc123',
			'locations'  => array(),
			'query'      => null,
			'expiration' => time(),
			'content'    => '<p>Some content</p>',
		);

		$hiive->expects( 'send_event' )->once()
			->andReturn( array( $notification ) );

		$result = $sut->create_item( $request );

		$this->assertEquals('abc123',$result->get_data()['data'][0]['id']);
	}
}
