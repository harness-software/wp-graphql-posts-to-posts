<?php

namespace WPGraphQLPostsToPosts\WPGraphQL\Types;

use P2P_Connection_Type;
use WPGraphQLPostsToPosts\Interfaces\Hookable;

class Fields implements Hookable {

	const QUERY_TYPE    = 'PostToPostConnections';
	const MUTATION_TYPE = 'PostToPostConnectionsMutate';
	const NAME          = 'postToPostConnections';

	public $p2p_connections = [];

	public function register_hooks() {
		add_action( 'p2p_registered_connection_type', [ $this, 'capture_p2p_connections' ], 10, 2 );
		add_action( 'graphql_register_types', [ $this, 'register_connection_name_enum' ], 9 );
		add_action( 'graphql_register_types', [ $this, 'register_type' ], 9 );
	}

	public function capture_p2p_connections( P2P_Connection_Type $ctype, array $args ) : void {
		$this->p2p_connections[] = $args;

		$fp = fopen( plugin_dir_path( __FILE__ ) . 'results3.json', 'w' );
		fwrite( $fp, json_encode( $args ) );
		fclose( $fp );
	}

	public function register_connection_name_enum() {
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
		$fp                     = fopen( plugin_dir_path( __FILE__ ) . 'results2.json', 'w' );
		fwrite( $fp, json_encode( $values ) );
		fclose( $fp );

		register_graphql_enum_type(
			'PostsToPostsConnectionNameEnum',
			[
				'description' => __( 'Posts 2 Posts connection names', 'wp-graphql-posts-to-posts' ),
				'values'      => $values,
			]
		);
	}

	public function register_type() {
		register_graphql_input_type(
			self::QUERY_TYPE,
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
			self::MUTATION_TYPE,
			[
				'description' => __( 'PostToPostConnections type.', 'wp-graphql-posts-to-posts' ),
				'fields'      => [
					'connection' => [
						'type'        => 'String',
						'description' => __( 'connection type.', 'wp-graphql-posts-to-posts' ),
					],
					'ids'        => [
						'type'        => [ 'list_of' => 'Int' ],
						'description' => __( 'connection ids.', 'wp-graphql-posts-to-posts' ),
					],
					'append'     => [
						'type'        => 'Boolean',
						'description' => __( 'append connection boolean.', 'harness' ),
					],
				],
			]
		);
	}
}
