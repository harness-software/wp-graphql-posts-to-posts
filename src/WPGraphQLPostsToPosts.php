<?php

namespace WPGraphQLPostsToPosts;

use WPGraphQLPostsToPosts\Interfaces\Hookable;

/**
 * Main plugin class.
 */
final class WPGraphQLPostsToPosts {
	/**
	 * Class instances.
	 */
	private $instances = [];

	/**
	 * Main method for running the plugin.
	 */
	public function run() {
		$this->create_instances();
		$this->register_hooks();
	}

	private function create_instances() {
		$this->instances['connections_registrar'] = new Connections\ConnectionsRegistrar();
		$this->instances['field_types']           = new WPGraphQL\Types\Fields();
		$this->instances['post']                  = new WPGraphQL\Types\Post();
		// $this->instances['users']                 = new WPGraphQL\Types\Users();
		// $this->instances['post_mutation'] = new graphql\Mutations\Post();
		// $this->instances['users_mutation'] = new graphql\Mutations\Users();
	}

	private function register_hooks() {
		foreach ( $this->get_hookable_instances() as $instance ) {
			$instance->register_hooks();
		}
	}

	private function get_hookable_instances() {
		return array_filter( $this->instances, fn( $instance ) => $instance instanceof Hookable );
	}
}
