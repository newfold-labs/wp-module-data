
	/**
	 * @covers ::send
	 */
	public function test_send(): void {

		$sut = new EventManager();

		$event_mock = Mockery::mock(Event::class);

		$subscriber_mock = Mockery::mock(SubscriberInterface::class)->makePartial();

		$sut->add_subscriber($subscriber_mock);

		$sut->send( array( $event_mock ) );

	}

