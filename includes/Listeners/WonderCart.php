<?php

namespace NewfoldLabs\WP\Module\Data\Listeners;

/**
 * Monitors WonderCart events
 */
class WonderCart extends Listener {

	/**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action('rest_after_insert_yith_campaign', array( $this, 'register_campaign' ), 10 );
		add_action('yith_sales_edit_campaign_event_modal_opened', array( $this, 'create_campaign_modal_open' ), 10, 2 );
		add_action('yith_sales_edit_campaign_event_campaign_selected', array( $this, 'campaign_selected' ), 10, 2 );
		add_action('yith_sales_edit_campaign_event_campaign_abandoned', array( $this, 'campaign_abandoned' ), 10, 2 );
	}

	/**
	 * Campaign created
	 *
	 * @param string $post
	 *
	 * @return string The post value
	 */
	public function register_campaign( $post){
		$campaign   = yith_sales_get_campaign( $post->ID );
		if ($campaign){
			$type = $campaign->get_type();
			
			$data = array(
				"label_key"=> "type",
				"type"=> $type,
			);
			
			$this->push(
				"campaign_created",
				$data
			);
		}
		
		return $post;
	}

	/**
	 * Track wonder_cart create campaign modal window open
	 * Send data to hiive

	 * @param string $args A list of details that were involved on the event. 
	 * @param string $event The name of the event.

	 * @return void
	 */
	public function create_campaign_modal_open($args, $event) {
		$url =  is_ssl() ? "https://" : "http://";
		$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$data = array(
			"label_key" => "trigger",
			"trigger"   => 'Campaign Modal Open',
			"page"      =>  $url
		);

		$this->push(
			'modal_open',
		 	'wonder_cart',
			$data
		);
	}

	/**
	 * Track wonder_cart campaign selection
	 * Send data to hiive

	 * @param string $args A list of details that were involved on the event. 
	 * @param string $event The name of the event.

	 * @return void
	 */
	public function campaign_selected($args, $event) {
		$data = array(
			"label_key"     => "campaign_slug",
			"campaign_type" => $args['type'],
			"campaign_slug" => $args['type']
		);

		$this->push(
			'campaign_selected',
		 	'wonder_cart',
			$data
		);

	}

	/**
	 * Track wonder_cart campaign abondoned
	 * Send data to hiive

	 * @param string $args A list of details that were involved on the event. 
	 * @param string $event The name of the event.

	 * @return void
	 */
	public function campaign_abandoned($args, $event) {
		$data = array(
			'label_key'     => 'campaign_slug',
			'campaign_type' => $args['type'],
			'campaign_slug' => $args['type']."-".$args['id']
		);

		$this->push(
			'campaign_abondoned',
		 	'wonder_cart',
			$data
		);
	}
}
