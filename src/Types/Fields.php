<?php

namespace WPGraphQLPostsToPosts\Types;

use P2P_Connection_Type;
use WPGraphQL;
use WPGraphQLPostsToPosts\Interfaces\Hookable;

class Fields implements Hookable {
	const PARENT_QUERY_TYPE = 'PostToPostConnectionQuery';
	const QUERY_TYPE        = 'PostToPostConnections';
	const MUTATION_TYPE     = 'PostToPostConnectionsMutate';
	const NAME              = 'postToPostConnections';

	/**
	 * Registered Posts2Posts connections.
	 *
	 * @var array
	 */
	public static $p2p_connections = [];

	public function register_hooks() : void {
		add_action( 'p2p_registered_connection_type', [ $this, 'capture_p2p_connections' ], 10, 2 );
		add_action( 'graphql_register_types_late', [ $this, 'register_connection_name_enum' ], 9 );
	}

	public function capture_p2p_connections( P2P_Connection_Type $ctype, array $args ) : void {
		if ( ! isset( self::$p2p_connections[ $args['name'] ] ) ) {
			self::$p2p_connections[ $args['name'] ] = $args;
		}
	}

	public static function get_p2p_connections() : array {
		return array_filter( self::$p2p_connections, [ __CLASS__, 'should_create_connection' ] );
	}

	public static function get_post_types_with_connections() : array {
		$connections = self::$p2p_connections;

		$post_types = [];

		foreach ( $connections as $name => $args ) {
			$post_types[] = $args['from'];
			$post_types[] = $args['to'];
		}

		return array_unique( $post_types );
	}

	public static function should_create_connection( array $connection ) : bool {
		return self::should_connect_object( $connection['from'] )
			&& self::should_connect_object( $connection['to'] );
	}

	public static function should_connect_object( string $object_name ) : bool {
		return 'user' === $object_name || self::is_post_type_in_schema( $object_name );
	}

	public static function is_post_type_in_schema( string $post_type_name ) : bool {
		return in_array( $post_type_name, WPGraphQL::get_allowed_post_types(), true );
	}

	public function register_connection_name_enum() : void {
		$p2p_connections_to_map = self::get_p2p_connections();

		$values = [];
		foreach ( $p2p_connections_to_map as $connection ) {
			$name = $connection['name'];

			if ( ! empty( $name ) ) {
				$values[ strtoupper( $name ) ] = [
					// translators: The P2P connection name.
					'description' => sprintf( __( 'The %s Posts2Posts connection', 'wp-graphql-posts-to-posts' ), $name ),
					'value'       => $name,
				];
			}
		}

		if ( empty( $values ) ) {
			return;
		}

		register_graphql_enum_type(
			'PostsToPostsConnectionNameEnum',
			[
				'description' => __( 'Posts 2 Posts connection names', 'wp-graphql-posts-to-posts' ),
				'values'      => $values,
			]
		);
	}
}
