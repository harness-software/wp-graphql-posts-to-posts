<?php

namespace WPGraphQLPostsToPosts\Connections;

use P2P_Connection_Type;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection;
use WPGraphQL\Model\User;
use WPGraphQLPostsToPosts\Interfaces\Hookable;
use WPGraphQL\Connection\Users;
use WPGraphQL\Connection\PostObjects;

class ConnectionsRegistrar implements Hookable {
	/**
	 * Registered Posts2Posts connections.
	 *
	 * @var array
	 */
	private $p2p_connections = [];

	/**
	 * Post types exposed in the GraphQL schema.
	 *
	 * @var array
	 */
	private $post_types = [];

	public function register_hooks() {
		add_action( 'p2p_registered_connection_type', [ $this, 'capture_p2p_connections' ], 10, 2 );
		add_action( 'graphql_register_types', [ $this, 'set_post_types_property' ] );
		add_action( 'graphql_register_types', [ $this, 'register_connections' ], 11 );
	}

	public function capture_p2p_connections( P2P_Connection_Type $ctype, array $args ) : void {
		$this->p2p_connections[] = $args;
	}

	public function set_post_types_property() {
		$this->post_types = get_post_types( [ 'show_in_graphql' => true ], 'objects' );
	}

	public function register_connections() {
		$p2p_connections_to_map = array_filter( $this->p2p_connections, [ $this, 'should_create_connection' ] );

		foreach ( $p2p_connections_to_map as $p2p_connection ) {
			// Register from -> to connection.
			$this->register_connection(
				[
					'from_object_name' => $p2p_connection['from'],
					'to_object_name'   => $p2p_connection['to'],
					'connection_name'  => $p2p_connection['name'],
				]
			);

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
				'fromType'       => $this->get_graphql_single_name( $args['from_object_name'] ),
				'toType'         => $this->get_graphql_single_name( $args['to_object_name'] ),
				'fromFieldName'  => graphql_format_field_name( $args['connection_name'] . 'Connection' ),
				'connectionArgs' => 'user' === $args['to_object_name'] ? Users::get_connection_args() : PostObjects::get_connection_args(),
				'resolve'        => function( $source, array $request_args, AppContext $context, ResolveInfo $info ) use ( $args ) {
					graphql_debug( $args['to_object_name'] );
					// We need to query for connected users.
					if ( 'user' === $args['to_object_name'] ) {
						$resolver = new Connection\UserConnectionResolver( $source, $request_args, $context, $info, $args['to_object_name'] );
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

	private function should_create_connection( array $connection ) : bool {
		return $this->should_connect_object( $connection['from'] )
			&& $this->should_connect_object( $connection['to'] );
	}

	private function should_connect_object( string $object_name ) : bool {
		return 'user' === $object_name || $this->is_post_type_in_schema( $object_name );
	}

	private function is_post_type_in_schema( string $post_type_name ) : bool {
		$post_type_names = array_map( fn( $post_type ) => $post_type->name, $this->post_types );

		return in_array( $post_type_name, $post_type_names, true );
	}

	private function get_graphql_single_name( string $object_name ) : string {
		if ( 'user' === $object_name ) {
			return 'User';
		}

		$post_object = $this->array_find( $this->post_types, fn( $post_type ) => $post_type->name === $object_name );

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
	private function array_find( array $array, callable $callback ) {
		foreach ( $array as $key => $value ) {
			if ( $callback( $value, $key, $array ) ) {
				return $value;
			}
		}

		return null;
	}

}
