<?php

namespace WPGraphQLPostsToPosts\Mutations;

use WP_Post_Type;
use WPGraphQL;
use WPGraphQLPostsToPosts\Types\Fields;

class PostMutation extends AbstractMutation {

	public function register_hooks(): void {
		parent::register_hooks();
		add_action( 'graphql_post_object_mutation_update_additional_data', [ $this, 'save_additional_data' ], 10, 4 );
	}

	public function register_input_fields(): void {
		$post_types = WPGraphQL::get_allowed_post_types( 'objects' );

		$types_wtith_connections = Fields::get_post_types_with_connections();

		foreach ( $post_types as $post_type ) {
			// Bail if no P2P connection registered for type.
			if ( ! in_array( $post_type->name, $types_wtith_connections, true ) ) {
				continue;
			}

			$graphql_single_name = ucfirst( $post_type->graphql_single_name );

			register_graphql_field(
				'Update' . $graphql_single_name . 'Input',
				Fields::NAME,
				[
					'type'        => [ 'list_of' => Fields::MUTATION_TYPE ],
					'description' => __( 'Id', 'wp-graphql-posts-to-posts' ),
				]
			);
			register_graphql_field(
				'Create' . $graphql_single_name . 'Input',
				Fields::NAME,
				[
					'type'        => [ 'list_of' => Fields::QUERY_TYPE ],
					'description' => __( 'Id', 'wp-graphql-posts-to-posts' ),
				]
			);
		}
	}

	public function save_additional_data( int $post_id, array $input, WP_Post_Type $post_type_object, string $mutation_name ): void {
		if ( ! isset( $input['postToPostConnections'] ) || ! is_array( $input['postToPostConnections'] ) ) {
			return;
		}

		$post_types = WPGraphQL::get_allowed_post_types( 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( self::camel_case_to_underscores( $mutation_name ) !== $post_type->name ) {
				continue;
			}

			$p2p_connections_to_map = Fields::get_p2p_connections();

			$field_names = [];

			$connection_name = $post_type->name;

			$connections = array_filter( $p2p_connections_to_map, fn ( $p2p_connection) => $p2p_connection['from'] === $connection_name || $p2p_connection['to'] === $connection_name );

			foreach ( $connections as $connection ) {
				array_push( $field_names, $connection['name'] );
			}

			foreach ( $input['postToPostConnections'] as $post_to_post_connection ) {
				if ( ! in_array( $post_to_post_connection['connection'], $field_names, true ) ) {
					continue;
				}

				$connected_type = $post_to_post_connection['connection'];

				$should_append = isset( $post_to_post_connection['append'] ) ? $post_to_post_connection['append'] : false;

				if ( ( ! $should_append || 0 === count( $post_to_post_connection['ids'] ) ) && false === strpos( $mutation_name, 'Create' ) ) {
					$connection_to = null;

					foreach ( $connections as $connection ) {
						if ( $connection['name'] === $post_to_post_connection['connection'] ) {
							$connection_to = $connection['to'];
						}
					}

					$connected_ids = [];

					$connected_query_args = [
						'posts_per_page'         => 1000,
						'fields'                 => 'ids',
						'connected_type'         => sanitize_text_field( $connected_type ),
						'connected_items'        => $post_id,
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					];

					if ( 'user' === $connection_to || 0 === count( $post_to_post_connection['ids'] ) ) {
						$connected = new \WP_User_Query( $connected_query_args );

						$connected_ids = array_map( 'absint', $connected->get_results() );
					} else {
						$connected = new \WP_Query( $connected_query_args );

						$connected_ids = array_map( 'absint', $connected->posts );
					}

					foreach ( $connected_ids as $id ) {
						$this->disconnect_p2p_type( $post_id, $id, $connected_type );
					}
				}

				foreach ( array_map( 'absint', $post_to_post_connection['ids'] ) as $id ) {
					$this->connect_p2p_type( $post_id, $id, $connected_type );
				}
			}
		}
	}

	protected static function camel_case_to_underscores( string $string ): string {
		if ( 0 === preg_match( '/[A-Z]/', $string ) ) {
			return $string;
		}
		$pattern          = '/([a-z])([A-Z])/';
		$replaced_str     = strtolower(
			(string) preg_replace_callback(
				$pattern,
				function ( $lettersToReplace ) {
					return $lettersToReplace[1] . '_' . strtolower( $lettersToReplace[2] );
				},
				$string
			)
		);
		$update_removed   = str_replace( 'update_', '', $replaced_str );
		$ready_connection = str_replace( 'create_', '', $update_removed );
		return $ready_connection;
	}
}
