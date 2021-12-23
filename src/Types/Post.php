<?php

namespace WPGraphQLPostsToPosts\Types;

use \WPGraphQL\Type\Enum\RelationEnum;
use WPGraphQLPostsToPosts\Interfaces\Hookable;
use WPGraphQLPostsToPosts\Traits\ObjectsTrait;
use WPGraphQLPostsToPosts\Types\Fields;

class Post implements Hookable {

	use ObjectsTrait;

	public function register_hooks() : void {
		add_action( get_graphql_register_action(), [ $this, 'register_where_input_fields' ] );
		add_filter( 'graphql_map_input_fields_to_wp_query', [ $this, 'modify_query_input_fields' ], 10, 6 );
	}

	public function register_where_input_fields() : void {
		$post_types = self::get_post_types();
		foreach ( $post_types as $post_type ) {
			$graphql_single_name = $post_type->graphql_single_name;

			register_graphql_field(
				'RootQueryTo' . $graphql_single_name . 'ConnectionWhereArgs',
				Fields::PARENT_QUERY_TYPE,
				[
					'type'        => Fields::PARENT_QUERY_TYPE,
					'description' => __( 'Id', 'wp-graphql-posts-to-posts' ),
				]
			);
		}
	}


	public function modify_query_input_fields( array $query_args ) : array {
		$p2p_connections_to_map = Fields::get_p2p_connections();

		$field_names = [];
		$post__in    = [];

		$post_types = self::get_post_types();

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
					'posts_per_page'         => 1000,
					'fields'                 => 'ids',
					'connected_type'         => $connected_type,
					'connected_items'        => array_map( 'absint', $post_to_post_connection['ids'] ),
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				];

				$connected = new \WP_Query( $connected_query_args );

				$post_ids = $connected->get_posts();

				// if ( ! $post_ids && 'AND' === $relation ) {
				// 	$query_args['post__in'] = [ 0 ];
				// 	return $query_args;
				// }

				if ( 'AND' === $relation ) {
					$post__in = $post__in ? array_values( array_intersect( $post__in, $post_ids ) ) : $post_ids;
				} else {
					$post__in = $post__in ? array_values( array_merge( $post__in, $post_ids ) ) : $post_ids;
				}
			}
		}

		$query_args['post__in'] = empty( $post__in ) ? [ 0 ] : $post__in;

		return $query_args;
	}

}
