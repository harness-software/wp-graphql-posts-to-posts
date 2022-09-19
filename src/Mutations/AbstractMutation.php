<?php
/**
 * Abstract Mutation class.
 */
namespace WPGraphQLPostsToPosts\Mutations;

use GraphQL\Error\UserError;
use WPGraphQLPostsToPosts\Interfaces\Hookable;

abstract class AbstractMutation implements Hookable {

	public function register_hooks() : void {
		add_action( 'graphql_register_types', [ $this, 'register_input_fields' ] );
	}

	abstract public function register_input_fields() : void;

	/**
	 * Register the P2P Connection
	 *
	 * @param integer $from_id
	 * @param integer $to_id
	 * @param string $connected_type
	 *
	 * @throws UserError No Posts2Posts connection for $connected_type.
	 */
	public function connect_p2p_type( int $from_id, int $to_id, string $connected_type ) : void {
		$p2p_type = p2p_type( $connected_type );

		if ( ! is_object( $p2p_type ) ) {
			throw new UserError(
				// translators: P2P connnection type name.
				sprintf( __( 'No Posts2Posts connection type found for %s', 'wp-graphql-posts-to-posts' ), $connected_type )
			);
		}

		$p2p_type->connect(
			$from_id,
			$to_id,
		);
	}

	public function disconnect_p2p_type( int $from_id, int $to_id, string $connected_type ) : void {
		$p2p_type = p2p_type( $connected_type );

		if ( ! is_object( $p2p_type ) ) {
			throw new UserError(
				// translators: P2P connnection type name.
				sprintf( __( 'No Posts2Posts connection type found for %s', 'wp-graphql-posts-to-posts' ), $connected_type )
			);
		}

		$p2p_type->disconnect(
			$from_id,
			$to_id,
		);
	}
}
