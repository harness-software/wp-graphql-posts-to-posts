<?php

namespace WPGraphQLPostsToPosts\Mutations;

use WPGraphQLPostsToPosts\Types\Fields;

class UserMutation extends AbstractMutation {
	public function register_hooks() : void {
		parent::register_hooks();
		add_action( 'graphql_user_object_mutation_update_additional_data', [ $this, 'save_additional_data' ], 10, 3 );
	}

	public function register_input_fields() : void {
		register_graphql_field(
			'UpdateUserInput',
			Fields::NAME,
			[
				'type'        => [ 'list_of' => Fields::MUTATION_TYPE ],
				'description' => __( 'Id', 'wp-graphql-posts-to-posts' ),
			]
		);
		register_graphql_field(
			'CreateUserInput',
			Fields::NAME,
			[
				'type'        => [ 'list_of' => Fields::QUERY_TYPE ],
				'description' => __( 'Id', 'wp-graphql-posts-to-posts' ),
			]
		);
	}

	public function save_additional_data( int $user_id, array $input, string $mutation_name ) : void {
		if ( ! isset( $input['postToPostConnections'] ) || ! is_array( $input['postToPostConnections'] ) ) {
			return;
		}

		if ( 'updateUser' !== $mutation_name ) {
			return;
		}

		$p2p_connections_to_map = Fields::get_p2p_connections();

		$field_names = [];

		$connections = array_filter( $p2p_connections_to_map, fn( $p2p_connection ) => 'user' === $p2p_connection['from'] || 'user' === $p2p_connection['to'] );

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
				$connected_ids = [];

				$connected_query_args = [
					'posts_per_page'         => 1000,
					'fields'                 => 'ids',
					'connected_type'         => sanitize_text_field( $connected_type ),
					'connected_items'        => $user_id,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				];

				$connected = new \WP_Query( $connected_query_args );

				$connected_ids = array_map( 'absint', $connected->posts );

				foreach ( $connected_ids as $id ) {
					$this->disconnect_p2p_type( $user_id, $id, $connected_type );
				}
			}

			foreach ( array_map( 'absint', $post_to_post_connection['ids'] ) as $id ) {
				$this->connect_p2p_type( $user_id, $id, $connected_type );
			}
		}
	}
}
