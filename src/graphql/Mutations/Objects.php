<?php

namespace WPGraphQLPostsToPosts\graphql\Mutations;

use P2P_Connection_Type;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Data\DataSource;
use WPGraphQL\Data\Connection;
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

	public function register_type() {
		register_graphql_input_type(
			'PostToPostConnectionsUpdate',
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
						'description' => __( 'append connection boolean.', 'wp-graphql-posts-to-posts' ),
					],
				],
			]
		);
	}

	public function capture_p2p_connections( P2P_Connection_Type $ctype, array $args ) : void {
		$this->p2p_connections[] = $args;
	}

	public function set_post_types_property() {
		$this->post_types = get_post_types( [ 'show_in_graphql' => true ], 'objects' );
	}

	private function camel_case_to_underscores( $string ) {
		if ( 0 === preg_match( '/[A-Z]/', $string ) ) {
			return $string; }
		$pattern          = '/([a-z])([A-Z])/';
		$replaced_str     = strtolower(
			preg_replace_callback(
				$pattern,
				function ( $lettersToReplace ) {
					return $lettersToReplace[1] . '_' . strtolower( $lettersToReplace[2] );
				},
				$string
			)
		);
		$update_removed   = \str_replace( 'update_', '', $replaced_str );
		$ready_connection = \str_replace( 'create_', '', $update_removed );
		return $ready_connection;
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
