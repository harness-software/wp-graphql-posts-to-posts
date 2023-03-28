<?php

namespace WPGraphQLPostsToPosts\Connections;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection;
use WPGraphQL\Model\User;
use WPGraphQLPostsToPosts\Interfaces\Hookable;
use WPGraphQLPostsToPosts\Types\Fields;

class ConnectionsRegistrar implements Hookable
{
    public function register_hooks() : void {
        add_action( 'graphql_register_types', [ $this, 'register_connections' ], 11 );
    }

    public function register_connections_p2p_relationships(): void
    {
        $obj = new \P2P_Relationships;
        $relationships = $obj->get_relationships();
        if (empty($relationships)) {
            return;
        }

        foreach ($relationships as $rel_key => $relationship) {
            foreach ($relationship['from']['object_name'] as $from_post_type) {

                foreach ($relationship['to']['object_name'] as $to_post_type) {
                    $this->register_connection(
                        [
                            'rel_key' => $rel_key,
                            'from_object_name' => $from_post_type,
                            'to_object_name' => $to_post_type,
                            'connection_name' => $from_post_type . "_to_" . $to_post_type,
                        ]
                    );

                    if ($from_post_type === $to_post_type) {
                        continue;
                    }

                    $this->register_connection(
                        [
                            'rel_key' => $rel_key,
                            'from_object_name' => $to_post_type,
                            'to_object_name' => $from_post_type,
                            'connection_name' => $to_post_type . "_to_" . $from_post_type,
                        ]
                    );
                }
            }
        }
    }

    public function register_connections_p2p_scribu(): void
    {
        $p2p_connections_to_map = Fields::get_p2p_connections();

        foreach ($p2p_connections_to_map as $p2p_connection) {
            // Register from -> to connection.
            $this->register_connection(
                [
                    'from_object_name' => $p2p_connection['from'],
                    'to_object_name' => $p2p_connection['to'],
                    'connection_name' => $p2p_connection['name'],
                ]
            );

            if ($p2p_connection['from'] === $p2p_connection['to']) {
                continue;
            }

            // Register to -> from connection.
            $this->register_connection(
                [
                    'from_object_name' => $p2p_connection['to'],
                    'to_object_name' => $p2p_connection['from'],
                    'connection_name' => $p2p_connection['name'],
                ]
            );
        }
    }

    public function register_connections(): void
    {
        $variant = get_p2p_plugin_variant();
        if ($variant === "wpcentrics") {
            $this->register_connections_p2p_relationships();;
        } else {
            $this->register_connections_p2p_scribu();
        }
    }

    private function register_connection(array $args): void
    {
        $from_gql_single_name = self::get_graphql_single_name($args['from_object_name']);
        $to_gql_single_name = self::get_graphql_single_name($args['to_object_name']);
        $field_name = graphql_format_field_name($args['connection_name'] . 'Connection');

        register_graphql_connection(
            [
                'fromType' => $from_gql_single_name,
                'toType' => $to_gql_single_name,
                'fromFieldName' => $field_name,
                'resolve' => function ($source, array $request_args, AppContext $context, ResolveInfo $info) use ($args) {
                    // We need to query for connected users.
                    if ('user' === $args['to_object_name']) {
                        $resolver = new Connection\UserConnectionResolver($source, $request_args, $context, $info);
                        // We need to query for connected posts.
                    } else {
                        $resolver = new Connection\PostObjectConnectionResolver($source, $request_args, $context, $info, $args['to_object_name']);
                        $resolver->set_query_arg('post_parent', null);
                        $resolver->set_query_arg('author', null);
                    }


                    $source_object_id = $source instanceof User ? $source->userId : $source->ID;
                    $variant = get_p2p_plugin_variant();
                    if ($variant === "wpcentrics") {
                        $resolver->set_query_arg('p2p_rel_key', $args['rel_key']);
                        $resolver->set_query_arg('p2p_rel_post_id', $source_object_id);
                        $resolver->set_query_arg('p2p_rel_direction', 'any');
                    } else {
                        $resolver->set_query_arg('connected_items', $source_object_id);
                        $resolver->set_query_arg('connected_type', $args['connection_name']);
                    }

                    return $resolver->get_connection();
                },
            ]
        );
    }

    private static function get_graphql_single_name(string $object_name): string
    {
        if ('user' === $object_name) {
            return 'User';
        }

        $post_types = WPGraphQL::get_allowed_post_types( 'objects' );

        $post_object = self::array_find($post_types, fn($post_type) => $post_type->name === $object_name);

        return $post_object->graphql_single_name;
    }

    /**
     * Get the value of the first array element that satisfies the callback function.
     * Similar to JavaScript's Array.prototype.find() method.
     *
     * @param array $array The array.
     * @param callable $callback The callback function.
     *
     * @return mixed The value of the element, or null if not found.
     */
    private static function array_find(array $array, callable $callback)
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key, $array)) {
                return $value;
            }
        }

        return null;
    }
}
