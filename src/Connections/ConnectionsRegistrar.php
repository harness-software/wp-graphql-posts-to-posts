<?php

namespace WPGraphQLPostsToPosts\Connections;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection;
use WPGraphQL\Model\User;
use WPGraphQLPostsToPosts\Interfaces\Hookable;
use WPGraphQLPostsToPosts\Types\Fields;

class ConnectionsRegistrar implements Hookable {

	public function register_hooks() : void {
		add_action( 'graphql_register_types', [ $this, 'register_connections' ], 11 );
	}

	public function register_connections() : void {
		$p2p_connections_to_map = Fields::get_p2p_connections();

		foreach ( $p2p_connections_to_map as $p2p_connection ) {
			// Register from -> to connection.
			$this->register_connection(
				[
					'from_object_name' => $p2p_connection['from'],
					'to_object_name'   => $p2p_connection['to'],
					'connection_name'  => $p2p_connection['name'],
				]
			);

			if ( $p2p_connection['from'] === $p2p_connection['to'] ) {
				continue;
			}

			// Register to -> from connection.
			$this->register_connection(
				[
					'from_object_name' => $p2p_connection['to'],
					'to_object_name'   => $p2p_connection['from'],
					'connection_name'  => $p2p_connection['name'],
				]
			);
		}
	}

	private function register_connection( array $args ) : void {
		register_graphql_connection(
			[
				'fromType'      => self::get_graphql_single_name( $args['from_object_name'] ),
				'toType'        => self::get_graphql_single_name( $args['to_object_name'] ),
				'fromFieldName' => graphql_format_field_name( $args['connection_name'] . 'Connection' ),
				'resolve'       => function( $source, array $request_args, AppContext $context, ResolveInfo $info ) use ( $args ) {
					// We need to query for connected users.
					if ( 'user' === $args['to_object_name'] ) {
						$resolver = new Connection\UserConnectionResolver( $source, $request_args, $context, $info );
						// We need to query for connected posts.
					} else {
						$resolver = new Connection\PostObjectConnectionResolver( $source, $request_args, $context, $info, $args['to_object_name'] );
						$resolver->set_query_arg( 'post_parent', null );
						$resolver->set_query_arg( 'author', null );
					}

					$source_object_id = $source instanceof User ? $source->userId : $source->ID;
					$resolver->set_query_arg( 'connected_items', $source_object_id );
					$resolver->set_query_arg( 'connected_type', $args['connection_name'] );
					$connection = $resolver->get_connection();

					return $connection;
				},
			]
		);
	}

	private static function get_graphql_single_name( string $object_name ) : string {
		if ( 'user' === $object_name ) {
			return 'User';
		}

		$post_types = WPGraphQL::get_allowed_post_types( 'objects' );

		$post_object = self::array_find( $post_types, fn( $post_type ) => $post_type->name === $object_name );

		return $post_object->graphql_single_name;
	}

	/**
	 * Get the value of the first array element that satisfies the callback function.
	 * Similar to JavaScript's Array.prototype.find() method.
	 *
	 * @param array    $array    The array.
	 * @param callable $callback The callback function.
	 *
	 * @return mixed The value of the element, or null if not found.
	 */
	private static function array_find( array $array, callable $callback ) {
		foreach ( $array as $key => $value ) {
			if ( $callback( $value, $key, $array ) ) {
				return $value;
			}
		}

		return null;
	}
}
