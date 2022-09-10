<?php

namespace WPGraphQLPostsToPosts\Traits;

use WPGraphQL;

trait ObjectsTrait {
	/**
	 * Post types exposed in the GraphQL schema.
	 *
	 * @var array
	 */
	public static $post_types;

	public static function get_post_types() : array {
		if ( empty( self::$post_types ) ) {
			self::$post_types = WPGraphQL::get_allowed_post_types( 'objects' );
		}

		return self::$post_types;
	}
}
