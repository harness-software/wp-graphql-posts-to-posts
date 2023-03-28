<?php
/**
 * Register GraphQL Input Types
 */
namespace WPGraphQLPostsToPosts\Types;

use WPGraphQL\Type\Enum\RelationEnum;
use WPGraphQLPostsToPosts\Interfaces\Hookable;

/**
 * Class - Inputs
 */
class Inputs implements Hookable {
	public function register_hooks() : void {
		add_action( 'graphql_register_types', [ $this, 'register_type' ] );
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
			Fields::PARENT_QUERY_TYPE,
			[
				'description' => __( 'Post to post connection query type.', 'wp-graphql-posts-to-posts' ),
				'fields'      => [
					'connections' => [
						'type'        => [ 'list_of' => Fields::QUERY_TYPE ],
						'description' => __( 'The post to post connections and ids', 'wp-graphql-posts-to-posts' ),
					],
					'relation'    => [
						'type'        => 'RelationEnum',
						'description' => __( 'Relation enum between the P2P connections.', 'wp-graphql-posts-to-posts' ),
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
