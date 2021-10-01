<?php

namespace WPGraphQLPostsToPosts\Types;

use WPGraphQLPostsToPosts\Interfaces\Hookable;
use WPGraphQLPostsToPosts\Traits\ObjectsTrait;

class Fields implements Hookable {
	use ObjectsTrait;

	const QUERY_TYPE    = 'PostToPostConnections';
	const MUTATION_TYPE = 'PostToPostConnectionsMutate';
	const NAME          = 'postToPostConnections';

	public function register_hooks() : void {
		add_action( 'p2p_registered_connection_type', [ $this, 'capture_p2p_connections' ], 10, 2 );
		add_action( 'graphql_register_types', [ $this, 'register_connection_name_enum' ], 9 );
	}

	public function register_connection_name_enum() : void {
		$p2p_connections_to_map = array_filter( $this->p2p_connections, [ $this, 'should_create_connection' ] );
		$values                 = array_map(
			function( $connection ) {
				$name = $connection['name'];
				return [
					strtoupper( $name ) => [
						'value' => $name,
					],
				];
			},
			$p2p_connections_to_map
		);

		register_graphql_enum_type(
			'PostsToPostsConnectionNameEnum',
			[
				'description' => __( 'Posts 2 Posts connection names', 'wp-graphql-posts-to-posts' ),
				'values'      => $values,
			]
		);
	}
}
