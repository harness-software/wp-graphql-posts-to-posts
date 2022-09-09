<?php

namespace WPGraphQLPostsToPosts;

use WPGraphQLPostsToPosts\Interfaces\Hookable;

/**
 * Main plugin class.
 */
final class WPGraphQLPostsToPosts {
	/**
	 * Class instances.
	 *
	 * @var array
	 */
	private $instances = [];

	/**
	 * Main method for running the plugin.
	 */
	public function run() : void {
		$this->create_instances();
		$this->register_hooks();
	}

	private function create_instances() : void {
		$this->instances['connections_registrar'] = new Connections\ConnectionsRegistrar();
		$this->instances['field_types']           = new Types\Fields();
		$this->instances['input_types']           = new Types\Inputs();
		$this->instances['post']                  = new Types\Post();
		$this->instances['user']                  = new Types\User();
		$this->instances['post_mutation']         = new Mutations\PostMutation();
		$this->instances['users_mutation']        = new Mutations\UserMutation();
	}

	private function register_hooks() : void {
		foreach ( $this->get_hookable_instances() as $instance ) {
			$instance->register_hooks();
		}
	}

	private function get_hookable_instances() : array {
		return array_filter( $this->instances, fn( $instance ) => $instance instanceof Hookable );
	}
}
