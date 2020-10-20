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
		$this->instances['post'] = new Types\Post();
		$this->instances['users'] = new Types\Users();
		$this->instances['post_mutation'] = new Mutations\Post();
		$this->instances['users_mutation'] = new Mutations\Users();
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
