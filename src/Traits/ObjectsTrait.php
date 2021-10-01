<?php

namespace WPGraphQLPostsToPosts\Traits;

use P2P_Connection_Type;

trait ObjectsTrait {

	/**
	 * Registered Posts2Posts connections.
	 *
	 * @var array
	 */
	public $p2p_connections = [];

	/**
	 * Post types exposed in the GraphQL schema.
	 *
	 * @var array
	 */
	public static $post_types;

	public function capture_p2p_connections( P2P_Connection_Type $ctype, array $args ) : void {
		$this->p2p_connections[] = $args;
	}

	public static function get_post_types() : array {
		if ( ! isset( self::$post_types ) ) {
			self::$post_types = get_post_types( [ 'show_in_graphql' => true ], 'objects' );
		}
		return self::$post_types;
	}

	public static function should_create_connection( array $connection ) : bool {
		return self::should_connect_object( $connection['from'] )
			&& self::should_connect_object( $connection['to'] );
	}

	public static function should_connect_object( string $object_name ) : bool {
		return 'user' === $object_name || self::is_post_type_in_schema( $object_name );
	}

	public static function is_post_type_in_schema( string $post_type_name ) : bool {
		$post_type_names = array_map( fn( $post_type ) => $post_type->name, self::$post_types );

		return in_array( $post_type_name, $post_type_names, true );
	}
}
