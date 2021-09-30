<?php

namespace WPGraphQLPostsToPosts\WPGraphQL\Types;

use GraphQL\Type\Definition\ResolveInfo;
use P2P_Connection_Type;
use WPGraphQL\AppContext;
use WPGraphQL\Data\DataSource;
use WPGraphQL\Data\Connection;
use WPGraphQL\Model\User;
use WPGraphQLPostsToPosts\Interfaces\Hookable;

trait Objects {

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
	public $post_types = [];

	public function capture_p2p_connections( P2P_Connection_Type $ctype, array $args ) : void {
		$this->p2p_connections[] = $args;
	}

	public function set_post_types_property() {
		$this->post_types = get_post_types( [ 'show_in_graphql' => true ], 'objects' );
	}

	public function should_create_connection( array $connection ) : bool {
		return $this->should_connect_object( $connection['from'] )
			&& $this->should_connect_object( $connection['to'] );
	}

	public function should_connect_object( string $object_name ) : bool {
		return 'user' === $object_name || $this->is_post_type_in_schema( $object_name );
	}

	public function is_post_type_in_schema( string $post_type_name ) : bool {
		$post_type_names = array_map( fn( $post_type ) => $post_type->name, $this->post_types );

		return in_array( $post_type_name, $post_type_names, true );
	}

}
