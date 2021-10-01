<?php

namespace WPGraphQLPostsToPosts\Traits;

use P2P_Connection_Type;

trait ObjectsTrait {
	/**
	 * Post types exposed in the GraphQL schema.
	 *
	 * @var array
	 */
	public static $post_types;

	public static function get_post_types() : array {
		if ( ! isset( self::$post_types ) ) {
			self::$post_types = get_post_types( [ 'show_in_graphql' => true ], 'objects' );
		}
		return self::$post_types;
	}
}
