<?php
/**
 * Register GraphQL Input Types
 */
namespace WPGraphQLPostsToPosts\Types;

use WPGraphQLPostsToPosts\Interfaces\Hookable;

/**
 * Class - Inputs
 */
class Inputs implements Hookable {
	public function register_hooks() : void {
		add_action( get_graphql_register_action(), [ $this, 'register_type' ] );
	}

	public function register_type() : void {
		register_graphql_input_type(
			Fields::QUERY_TYPE,
			[
				'description' => __( 'PostToPostConnections type.', 'wp-graphql-posts-to-posts' ),
				'fields'      => [
					'connection' => [
						'type'        => 'PostsToPostsConnectionNameEnum',
						'description' => __( 'connection type.', 'wp-graphql-posts-to-posts' ),
					],
					'ids'        => [
						'type'        => [ 'list_of' => 'Int' ],
						'description' => __( 'connection ids.', 'wp-graphql-posts-to-posts' ),
					],
				],
			]
		);

		register_graphql_input_type(
			Fields::MUTATION_TYPE,
			[
				'description' => __( 'PostToPostConnections type.', 'wp-graphql-posts-to-posts' ),
				'fields'      => [
					'connection' => [
						'type'        => 'PostsToPostsConnectionNameEnum',
						'description' => __( 'connection type.', 'wp-graphql-posts-to-posts' ),
					],
					'ids'        => [
						'type'        => [ 'list_of' => 'Int' ],
						'description' => __( 'connection ids.', 'wp-graphql-posts-to-posts' ),
					],
					'append'     => [
						'type'        => 'Boolean',
						'description' => __( 'append connection boolean.', 'wp-graphql-posts-to-posts' ),
					],
				],
			]
		);

		register_graphql_input_type(
			'PostToPostConnectionsUpdate',
			[
				'description' => __( 'PostToPostConnections type.', 'wp-graphql-posts-to-posts' ),
				'fields'      => [
					'connection' => [
						'type'        => 'PostsToPostsConnectionNameEnum',
						'description' => __( 'connection type.', 'wp-graphql-posts-to-posts' ),
					],
					'ids'        => [
						'type'        => [ 'list_of' => 'Int' ],
						'description' => __( 'connection ids.', 'wp-graphql-posts-to-posts' ),
					],
					'append'     => [
						'type'        => 'Boolean',
						'description' => __( 'append connection boolean.', 'wp-graphql-posts-to-posts' ),
					],
				],
			]
		);
	}
}
