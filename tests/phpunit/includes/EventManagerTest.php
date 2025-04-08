<?php

namespace NewfoldLabs\WP\Module\Data;

use Mockery;
use NewfoldLabs\WP\Module\Data\EventQueue\EventQueue;
use NewfoldLabs\WP\Module\Data\EventQueue\Queues\BatchQueue;
use NewfoldLabs\WP\Module\Data\Listeners\Admin;
use WP_Error;
use WP_Mock;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\EventManager
 */
class EventManagerTest extends \WP_Mock\Tools\TestCase {
	public function tearDown(): void {
		parent::tearDown();

		\Patchwork\restoreAll();
	}

	/**
	 * @covers ::initialize_listeners
	 */
	public function test_initialize_listeners_register_hooks() {

		$this->markTestSkipped( 'WP_Mock AnyInstance not available in until PHP 7.4.' );

		$sut = Mockery::mock( EventManager::class )->makePartial();
		$sut->expects( 'get_listeners' )->andReturn( array( '\\NewfoldLabs\\WP\\Module\\Data\\Listeners\\WP_Mail' ) );
		$sut->expects( 'initialize_cron' );

		\Patchwork\redefine(
			'defined',
			function ( string $constant_name ) {
				switch ( $constant_name ) {
					case 'BURST_SAFETY_MODE':
						return false;
					default:
						return \Patchwork\relay( func_get_args() );
				}
			}
		);

		WP_Mock::expectActionAdded( 'shutdown', array( $sut, 'shutdown' ) );

		// WP_Mock AnyInstance not available in until PHP 7.4.
		WP_Mock::expectActionAdded( 'admin_footer', array( new AnyInstance( \NewfoldLabs\WP\Module\Data\Listeners\WPMail::class ), 'mail_succeeded' ) );

		$sut->init();
	}

	/**
	 * @covers ::initialize_listeners
	 */
	public function test_initialize_listeners_burst_safety_enabled_no_listeners_hooks_registered() {
		$sut = Mockery::mock( EventManager::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$sut->expects( 'initialize_cron' );

		\Patchwork\redefine(
			'defined',
			function ( string $constant_name ) {
				switch ( $constant_name ) {
					case 'BURST_SAFETY_MODE':
						return true;
					default:
						return \Patchwork\relay( func_get_args() );
				}
			}
		);

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ( $constant_name ) {
					case 'BURST_SAFETY_MODE':
						return true;
					default:
						return \Patchwork\relay( func_get_args() );
				}
			}
		);

		WP_Mock::expectActionAdded( 'shutdown', array( $sut, 'shutdown' ) );

		$sut->init();

		/**
		 * Test fails if {@see Listener::register_hooks()} is called.
		 *
		 * `Unexpected use of add_action for action admin_footer with callback NewfoldLabs\WP\Module\Data\Listeners\Admin::view`
		 */
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::initialize_cron
	 */
	public function test_initialize_cron(): void {
		$sut = Mockery::mock( EventManager::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$sut->expects( 'initialize_listeners' );

		WP_Mock::expectFilterAdded( 'cron_schedules', array( $sut, 'add_minutely_schedule' ) );
		WP_Mock::expectActionAdded( 'nfd_data_sync_cron', array( $sut, 'send_saved_events_batch' ) );

		WP_Mock::userFunction( 'wp_next_scheduled' )
				->once()
				->with( 'nfd_data_sync_cron' )
				->andReturnFalse();

		\Patchwork\redefine(
			'time',
			function () {
				return 0; }
		);

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ( $constant_name ) {
					case 'MINUTE_IN_SECONDS':
						return 60;
					default:
						return \Patchwork\relay( func_get_args() );
				}
			}
		);

		WP_Mock::userFunction( 'wp_schedule_event' )
				->once()
				->with( 60, 'minutely', 'nfd_data_sync_cron' );

		WP_Mock::expectActionAdded( 'shutdown', array( $sut, 'shutdown' ) );

		$sut->init();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::init
	 */
	public function test_init(): void {
		$sut = Mockery::mock( EventManager::class )->makePartial()->shouldAllowMockingProtectedMethods();
		$sut->expects( 'initialize_listeners' );
		$sut->expects( 'initialize_cron' );

		WP_Mock::expectActionAdded( 'shutdown', array( $sut, 'shutdown' ) );

		$sut->init();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::send_saved_events_batch
	 */
	public function test_send_saved_events_happy_path(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$sut = Mockery::mock( EventManager::class )->makePartial();

		$event = Mockery::mock( Event::class );

		$batch_queue_mock->expects( 'pull' )
						->once()
						->with( 100 )
						->andReturn(
							array(
								15 => $event,
							)
						);

		$batch_queue_mock->expects( 'reserve' )
						->once()
						->with( array( 15 ) )
						->andReturnTrue();

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->expects( 'get_subscribers' )
			->once()
			->andReturn( array( $hiive_connection_subscriber ) );

		$hiive_connection_subscriber->expects( 'notify' )
			->once()
			->andReturn(
				array(
					'succeededEvents' => array( 15 => array() ),
					'failedEvents'    => array( 16 => array() ),
				)
			);

		WP_Mock::userFunction( 'is_wp_error' )
				->once()
				->andReturnFalse();

		$batch_queue_mock->expects( 'remove' )
						->once()
						->with( array( 15 ) );

		$batch_queue_mock->expects( 'release' )
						->once()
						->with( array( 16 ) );

		$sut->send_saved_events_batch();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::send_saved_events_batch
	 */
	public function test_send_saved_events_happy_path_no_failed_events(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$sut = Mockery::mock( EventManager::class )->makePartial();

		$event = Mockery::mock( Event::class );

		$batch_queue_mock->expects( 'pull' )
						->once()
						->with( 100 )
						->andReturn(
							array(
								15 => $event,
							)
						);

		$batch_queue_mock->expects( 'reserve' )
						->once()
						->with( array( 15 ) )
						->andReturnTrue();

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->expects( 'get_subscribers' )
			->once()
			->andReturn( array( $hiive_connection_subscriber ) );

		$hiive_connection_subscriber->expects( 'notify' )
			->once()
			->andReturn(
				array(
					'succeededEvents' => array( 15 => array() ),
					'failedEvents'    => array(),
				)
			);

		WP_Mock::userFunction( 'is_wp_error' )
				->once()
				->andReturnFalse();

		$batch_queue_mock->expects( 'remove' )
						->once()
						->with( array( 15 ) );

		$batch_queue_mock->expects( 'release' )
						->never();

		$sut->send_saved_events_batch();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::send_saved_events_batch
	 */
	public function test_send_saved_events_happy_path_no_successful_events(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$sut = Mockery::mock( EventManager::class )->makePartial();

		$event = Mockery::mock( Event::class );

		$batch_queue_mock->expects( 'pull' )
						->once()
						->with( 50 )
						->andReturn(
							array(
								15 => $event,
							)
						);

		$batch_queue_mock->expects( 'reserve' )
						->once()
						->with( array( 15 ) )
						->andReturnTrue();

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->expects( 'get_subscribers' )
			->once()
			->andReturn( array( $hiive_connection_subscriber ) );

		$hiive_connection_subscriber->expects( 'notify' )
			->once()
			->andReturn(
				array(
					'succeededEvents' => array(),
					'failedEvents'    => array( 16 => array() ),
				)
			);

		WP_Mock::userFunction( 'is_wp_error' )
				->once()
				->andReturnFalse();

		$batch_queue_mock->expects( 'remove' )
						->never();

		$batch_queue_mock->expects( 'release' )
						->once()
						->with( array( 16 ) );

		$sut->send_saved_events_batch();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::send_saved_events_batch
	 */
	public function test_send_saved_events_wp_error_from_hiive_connection(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$sut = Mockery::mock( EventManager::class )->makePartial();

		$event = Mockery::mock( Event::class );

		$batch_queue_mock->expects( 'pull' )
						->once()
						->with( 50 )
						->andReturn(
							array(
								15 => $event,
							)
						);

		$batch_queue_mock->expects( 'reserve' )
						->once()
						->with( array( 15 ) )
						->andReturnTrue();

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->expects( 'get_subscribers' )
			->once()
			->andReturn( array( $hiive_connection_subscriber ) );

		$hiive_connection_subscriber->expects( 'notify' )
			->once()
			->andReturn( new WP_Error() );

		WP_Mock::userFunction( 'is_wp_error' )
			->once()
			->andReturnTrue();

		$batch_queue_mock->expects( 'remove' )->never();

		$batch_queue_mock->expects( 'release' )
						->once()
						->with( array( 15 ) );

		$sut->send_saved_events_batch();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::send_saved_events_batch
	 */
	public function test_send_saved_events_failures_from_hiive(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$sut = Mockery::mock( EventManager::class )->makePartial();

		$event = Mockery::mock( Event::class );

		$batch_queue_mock->expects( 'pull' )
						->once()
						->with( 50 )
						->andReturn(
							array(
								16 => $event,
							)
						);

		$batch_queue_mock->expects( 'reserve' )
						->once()
						->with( array( 16 ) )
						->andReturnTrue();

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->expects( 'get_subscribers' )
			->once()
			->andReturn( array( $hiive_connection_subscriber ) );

		$hiive_connection_subscriber->expects( 'notify' )
			->once()
			->andReturn(
				array(
					'succeededEvents' => array(),
					'failedEvents'    => array( 19 => array() ),
				)
			);

		WP_Mock::userFunction( 'is_wp_error' )
			->once()
			->andReturnFalse();

		$batch_queue_mock->expects( 'release' )
						->once()
						->with( array( 19 ) );

		$batch_queue_mock->expects( 'remove' )->never();

		$sut->send_saved_events_batch();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::shutdown
	 * @covers ::send_request_events
	 */
	public function test_shutdown_happy_path_no_failed_events(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$sut = new EventManager();

		$event      = Mockery::mock( Event::class )->makePartial();
		$event->key = 'test';

		$sut->push( $event );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->add_subscriber( $hiive_connection_subscriber );

		$hiive_connection_subscriber->expects( 'notify' )
									->once()
									->andReturn(
										array(
											'succeededEvents' => array(),
											'failedEvents' => array(),
										)
									);

		WP_Mock::userFunction( 'is_wp_error' )
				->once()
				->andReturnFalse();

		$batch_queue_mock->expects( 'push' )->never();

		$sut->shutdown();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::shutdown
	 * @covers ::send_request_events
	 */
	public function test_shutdown_happy_path_with_failed_events(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$sut = new EventManager();

		$event      = Mockery::mock( Event::class )->makePartial();
		$event->key = 'test';

		$sut->push( $event );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->add_subscriber( $hiive_connection_subscriber );

		$hiive_connection_subscriber->expects( 'notify' )
									->once()
									->andReturn(
										array(
											'succeededEvents' => array(),
											'failedEvents' => array( 2 => array( 'key' => 'test' ) ),
										)
									);

		WP_Mock::userFunction( 'is_wp_error' )
				->once()
				->andReturnFalse();

		$batch_queue_mock->expects( 'push' )->once()
			->with( array( 2 => array( 'key' => 'test' ) ) );

		$sut->shutdown();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::shutdown
	 * @covers ::send_request_events
	 */
	public function test_shutdown_hiive_connection_wp_error(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$sut = new EventManager();

		$event      = Mockery::mock( Event::class )->makePartial();
		$event->key = 'test';

		$sut->push( $event );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->add_subscriber( $hiive_connection_subscriber );

		$hiive_connection_subscriber->expects( 'notify' )
									->once()
									->andReturn( new WP_Error() );

		WP_Mock::userFunction( 'is_wp_error' )
				->once()
				->andReturnTrue();

		$batch_queue_mock->expects( 'push' )->once()
		->with( array( $event ) );

		$sut->shutdown();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::shutdown
	 * @covers ::send_request_events
	 */
	public function test_shutdown_hiive_500_error(): void {

		$this->markTestSkipped( 'Due to an unidentified bug causing events to be resent, we are temporarily disabling retries.' );

		$sut = new EventManager();

		$event      = Mockery::mock( Event::class )->makePartial();
		$event->key = 'test';

		$sut->push( $event );

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$hiive_connection_subscriber = Mockery::mock( HiiveConnection::class );

		$sut->add_subscriber( $hiive_connection_subscriber );

		$hiive_connection_subscriber->expects( 'notify' )
									->once()
									->andReturn(
										array(
											'succeededEvents' => array(),
											'failedEvents' => array( 18 => array( 'key' => 'event ' ) ),
										)
									);

		WP_Mock::userFunction( 'is_wp_error' )
				->once()
				->andReturnFalse();

		$batch_queue_mock->expects( 'push' )->once()
		->with( array( 18 => array( 'key' => 'event ' ) ) );

		$sut->shutdown();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::send_saved_events_batch
	 */
	public function test_send_saved_events_reserve_fails(): void {

		$batch_queue_mock = Mockery::mock( BatchQueue::class );

		\Patchwork\redefine(
			array( EventQueue::class, '__construct' ),
			function () {}
		);
		\Patchwork\redefine(
			array( EventQueue::class, 'queue' ),
			function () use ( $batch_queue_mock ) {
				return $batch_queue_mock;
			}
		);

		$sut = Mockery::mock( EventManager::class )->makePartial();

		$event = Mockery::mock( Event::class );
 
		$batch_queue_mock->expects( 'remove_events_exceeding_attempts_limit' )
						->once()
						->with( 3 )
						->andReturnTrue();

		$batch_queue_mock->expects( 'pull' )
						->once()
						->with( 50 )
						->andReturn(
							array(
								15 => $event,
							)
						);

		$batch_queue_mock->expects( 'reserve' )
						->once()
						->with( array( 15 ) )
						->andReturnFalse();

		$sut->expects( 'get_subscribers' )->never();

		$sut->send_saved_events_batch();

		$this->assertConditionsMet();
	}
}
