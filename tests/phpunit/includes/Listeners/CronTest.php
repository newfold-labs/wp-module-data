<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

use WP_Mock;

/**
 * @coversDefaultClass \NewfoldLabs\WP\Module\Data\Listeners\Cron
 */
class CronTest extends \WP_Mock\Tools\TestCase {

	public function setUp(): void
	{
		parent::setUp();

		WP_Mock::passthruFunction('__');
	}

	/**
	 * @covers ::register_hooks
	 */
	public function test_register_hooks(): void {
		$event_manager = \Mockery::mock( \NewfoldLabs\WP\Module\Data\EventManager::class );

		$sut = new Cron( $event_manager );

		WP_Mock::expectFilterAdded( 'cron_schedules', array( $sut, 'add_weekly_schedule' ) );
		WP_Mock::expectActionAdded( 'nfd_data_cron', array( $sut, 'update' ) );

		WP_Mock::userFunction('wp_next_scheduled')
			->with( 'nfd_data_cron' )
			->once()->andReturnTrue();

		$sut->register_hooks();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register_hooks
	 */
	public function test_register_hooks_schedules_job_when_absent(): void {
		$event_manager = \Mockery::mock( \NewfoldLabs\WP\Module\Data\EventManager::class );

		$sut = new Cron( $event_manager );

		WP_Mock::expectFilterAdded( 'cron_schedules', array( $sut, 'add_weekly_schedule' ) );
		WP_Mock::expectActionAdded( 'nfd_data_cron', array( $sut, 'update' ) );

		WP_Mock::userFunction('wp_next_scheduled')
			->with( 'nfd_data_cron' )
			->once()->andReturnFalse();

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'DAY_IN_SECONDS':
						return 60 * 60 * 24;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		WP_Mock::userFunction('wp_schedule_event')
			->with( \WP_Mock\Functions::type( 'int' ), 'weekly', 'nfd_data_cron' )
			->once()->andReturnTrue();

		$sut->register_hooks();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::update
	 */
	public function test_cron_job_main_function(): void {
		$sut = \Mockery::mock( \NewfoldLabs\WP\Module\Data\Listeners\Cron::class )->makePartial();
		$sut->shouldAllowMockingProtectedMethods();

		WP_Mock::userFunction('get_plugins')
			->once()->andReturn(array());

		WP_Mock::userFunction('get_mu_plugins')
			->once()->andReturn(array());

		WP_Mock::expectFilter( 'newfold_wp_data_module_cron_data_filter', array( 'plugins' => array() ) );

		$sut->shouldReceive('push')
			->once()
			->with('cron', \Mockery::type('array'));

		$sut->update();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::add_weekly_schedule
	 */
	public function test_add_weekly_schedule_to_wp_cron_schedules(): void {
		$event_manager = \Mockery::mock( \NewfoldLabs\WP\Module\Data\EventManager::class );

		$sut = new Cron( $event_manager );

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'WEEK_IN_SECONDS':
						return 60 * 60 * 24 * 7;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		$result = $sut->add_weekly_schedule(array());

		self::assertArrayHasKey('weekly', $result);
		self::assertArrayHasKey('interval', $result['weekly']);
		self::assertArrayHasKey('display', $result['weekly']);
		self::assertEquals(604800, $result['weekly']['interval']);
		self::assertEquals('Once Weekly', $result['weekly']['display']);
	}

	/**
	 * @covers ::add_weekly_schedule
	 */
	public function test_fixes_weekly_schedule_in_wp_cron_schedules_if_value_is_wrong(): void {
		$event_manager = \Mockery::mock( \NewfoldLabs\WP\Module\Data\EventManager::class );

		$sut = new Cron( $event_manager );

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				switch ($constant_name) {
					case 'WEEK_IN_SECONDS':
						return 60 * 60 * 24 * 7;
					default:
						return \Patchwork\relay(func_get_args());
				}
			}
		);

		$result = $sut->add_weekly_schedule(
			array(
				'weekly' => array(
					'interval' => 60 * 60 * 24, // Incorrect value.
					'display' => 'Weekly'
				)
			)
		);

		self::assertEquals(604800, $result['weekly']['interval']);
	}
}
