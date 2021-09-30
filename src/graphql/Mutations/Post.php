<?php

namespace WPGraphQLPostsToPosts\graphql\Mutations;

use WP_Post_Type;
use WPGraphQL\AppContext;
use P2P_Connection_Type;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\Data\DataSource;
use WPGraphQL\Data\Connection;
use WPGraphQLPostsToPosts\Interfaces\Hookable;
use WPGraphQLPostsToPosts\graphql\Fields;

class Post implements Hookable {

	use Objects;

	public function register_hooks() {
		add_action( 'p2p_registered_connection_type', [ $this, 'capture_p2p_connections' ], 10, 2 );
		add_action( 'graphql_register_types', [ $this, 'set_post_types_property' ] );
		add_action( 'graphql_register_types', [ $this, 'register_input_fields' ] );
		add_action( 'graphql_post_object_mutation_update_additional_data', [ $this, 'save_additional_data' ], 10, 4 );
	}

	public function register_input_fields() {
		foreach ( $this->post_types as $post_type ) {
			$graphql_single_name = $post_type->graphql_single_name;

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

	public function save_additional_data( int $post_id, array $input, WP_Post_Type $post_type_object, string $mutation_name ) : void {
		foreach ( $this->post_types as $post_type ) {
			if ( $post_type->name === $this->camel_case_to_underscores( $mutation_name ) ) {
				$p2p_connections_to_map = array_filter( $this->p2p_connections, [ $this, 'should_create_connection' ] );

				$field_names = [];

				$connection_name = $post_type->name;

				$connections = array_filter( $p2p_connections_to_map, fn( $p2p_connection ) => $p2p_connection['from'] === $connection_name || $p2p_connection['to'] === $connection_name );

				foreach ( $connections as $connection ) {
					array_push( $field_names, $connection['name'] );
				}

				foreach ( $input['postToPostConnections'] as $post_to_post_connection ) {
					if ( in_array( $post_to_post_connection['connection'], $field_names, true ) ) {
						$connected_type = $post_to_post_connection['connection'];

						if ( ( ! $post_to_post_connection['append'] || 0 === count( $post_to_post_connection['ids'] ) ) && false === strpos( $mutation_name, 'Create' ) ) {
							$connection_to;

							foreach ( $connections as $connection ) {
								if ( $connection['name'] === $post_to_post_connection['connection'] ) {
									$connection_to = $connection['to'];
								}
							}

							$connected_ids = [];

							if ( 'user' === $connection_to || 0 === count( $post_to_post_connection['ids'] ) ) {
								$connected = new \WP_User_Query(
									[
										'posts_per_page'  => 1000,
										'fields'          => 'ids',
										'connected_type'  => sanitize_text_field( $connected_type ),
										'connected_items' => $post_id,
										'no_found_rows'   => true,
										'update_post_meta_cache' => false,
										'update_post_term_cache' => false,
									]
								);

								$connected_ids = array_map( 'absint', $connected->results );
							} else {
								$connected = new \WP_Query(
									[
										'posts_per_page'  => 1000,
										'fields'          => 'ids',
										'connected_type'  => sanitize_text_field( $connected_type ),
										'connected_items' => $post_id,
										'no_found_rows'   => true,
										'update_post_meta_cache' => false,
										'update_post_term_cache' => false,
									]
								);

								$connected_ids = array_map( 'absint', $connected->posts );
							}

							foreach ( $connected_ids as $id ) {
								p2p_type( $connected_type )->disconnect(
									$post_id,
									$id,
								);
							}
						}

						foreach ( array_map( 'absint', $post_to_post_connection['ids'] ) as $id ) {
							p2p_type( $connected_type )->connect(
								$post_id,
								$id,
							);
						}
					}
				}
			}
		}
	}

}

