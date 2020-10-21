<?php

namespace WPGraphQLPostsToPosts\graphql;

use WPGraphQLPostsToPosts\Interfaces\Hookable;

class Fields implements Hookable {

    const QUERY_TYPE     = 'PostToPostConnections';
    const MUTATION_TYPE  = 'PostToPostConnectionsMutate';
    const NAME           = 'postToPostConnections';
    
    public function register_hooks() {
        add_action( 'graphql_register_types', [ $this, 'register_type' ] );
    }

    public function register_type(){
        register_graphql_input_type( self::QUERY_TYPE, [
            'description' => __( 'PostToPostConnections type.', 'wp-graphql-posts-to-posts' ),
            'fields' => [
                'connection' => [
                    'type'        => 'String',
                    'description' => __( 'connection type.', 'wp-graphql-posts-to-posts' ),
                ],
                'ids' => [
                    'type'        =>  [ 'list_of' => 'Int' ],
                    'description' => __( 'connection ids.', 'wp-graphql-posts-to-posts' ),
                ],
            ]
        ]);

        register_graphql_input_type( self::MUTATION_TYPE, [
            'description' => __( 'PostToPostConnections type.', 'wp-graphql-posts-to-posts' ),
            'fields' => [
                'connection' => [
                    'type'        => 'String',
                    'description' => __( 'connection type.', 'wp-graphql-posts-to-posts' ),
                ],
                'ids' => [
                    'type'        =>  [ 'list_of' => 'Int' ],
                    'description' => __( 'connection ids.', 'wp-graphql-posts-to-posts' ),
                ],
                'append' => [
                    'type'        => 'Boolean',
                    'description' => __( 'append connection boolean.', 'harness' ),
                ],
            ]
        ]);

    }
}