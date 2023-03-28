<?php

namespace WPGraphQLPostsToPosts\Types;

use WPGraphQLPostsToPosts\Interfaces\Hookable;
use WPGraphQLPostsToPosts\Types\Fields;
use WPGraphQL;
class User implements Hookable {

	public function register_hooks() : void {
		add_action( 'graphql_register_types', [ $this, 'register_where_input_fields' ] );
		add_filter( 'graphql_map_input_fields_to_wp_user_query', [ $this, 'modify_query_input_fields' ], 10 );
	}

	public function register_where_input_fields() : void {
		register_graphql_field(
			'RootQueryToUserConnectionWhereArgs',
			Fields::PARENT_QUERY_TYPE,
			[
				'type'        => Fields::PARENT_QUERY_TYPE,
				'description' => __( 'Id', 'wp-graphql-posts-to-posts' ),
			]
		);
	}

	public function modify_query_input_fields( array $query_args ) : array {
		$p2p_connections_to_map = Fields::get_p2p_connections();

		$field_names = [];
		$include     = [];

		$post_types = WPGraphQL::get_allowed_post_types( 'objects' );

		foreach ( $post_types as $post_type ) {
			$connection_name = $post_type->name;

			$connections = array_filter( $p2p_connections_to_map, fn( $p2p_connection ) => $p2p_connection['from'] === $connection_name || $p2p_connection['to'] === $connection_name );

			foreach ( $connections as $connection ) {
				array_push( $field_names, $connection['name'] );
			}
		}

		if ( ! isset( $query_args['postToPostConnectionQuery'] ) ) {
			return $query_args;
		}

		if ( empty( $query_args['postToPostConnectionQuery']['connections'] ) ) {
			return $query_args;
		}

		$connections = $query_args['postToPostConnectionQuery']['connections'];
		$relation    = ! empty( $query_args['postToPostConnectionQuery']['relation'] ) ? $query_args['postToPostConnectionQuery']['relation'] : 'AND';

		if ( 1 === count( $connections ) ) {
			$connection = $connections[0]['connection'];

			if ( in_array( $connection, $field_names, true ) ) {
				$connected_type                = $connection;
				$query_args['connected_type']  = sanitize_text_field( $connected_type );
				$query_args['connected_items'] = array_map( 'absint', $connections[0]['ids'] );
				return $query_args;
			}
		}

		foreach ( $connections as $post_to_post_connection ) {
			if ( in_array( $post_to_post_connection['connection'], $field_names, true ) ) {
				$connected_type = $post_to_post_connection['connection'];

				$connected_query_args = [
					'users_per_page'         => 1000,
					'fields'                 => 'ids',
					'connected_type'         => sanitize_text_field( $connected_type ),
					'connected_items'        => array_map( 'absint', $post_to_post_connection['ids'] ),
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				];

				$connected = new \WP_User_Query(
					$connected_query_args
				);

				$user_ids = $connected->get_results();

				if ( 'AND' === $relation ) {
					$include = $include ? array_values( array_intersect( $include, $user_ids ) ) : $user_ids;
				} else {
					$include = $include ? array_values( array_merge( $include, $user_ids ) ) : $user_ids;
				}
			}
		}

		$query_args['include'] = empty( $include ) ? [ 0 ] : $include;

		return $query_args;
	}

}
