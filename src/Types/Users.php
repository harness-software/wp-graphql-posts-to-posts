<?php

namespace WPGraphQLPostsToPosts\Types;

use P2P_Connection_Type;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Data\DataSource;
use WPGraphQL\Data\Connection;
use WPGraphQL\Model\User;
use WPGraphQLPostsToPosts\Interfaces\Hookable;

class Users implements Hookable {

    use Objects;

    public function register_hooks() {
        add_action( 'p2p_registered_connection_type',       [ $this, 'capture_p2p_connections' ], 10, 2 );
        add_action( 'graphql_register_types',               [ $this, 'set_post_types_property' ] );
        add_action( 'graphql_register_types',               [ $this, 'register_type'] );
        add_action( 'graphql_register_types',               [ $this, 'register_where_input_fields' ] );
        add_filter( 'graphql_map_input_fields_to_wp_user_query', [ $this, 'modify_query_input_fields' ], 10, 6 );
    }

    public function register_where_input_fields() {
    
        register_graphql_field( 'RootQueryToUserConnectionWhereArgs', 'postToPostConnections', [
            'type'        => [ 'list_of' => 'PostToPostConnections' ],
            'description' =>  __( 'Id', 'harness' ),
        ] );
               
    }

    public function modify_query_input_fields( array $query_args) : array {

        $p2p_connections_to_map = array_filter( $this->p2p_connections, [ $this, 'should_create_connection' ] );
        
        $field_names = [];
        $post__in = [];

        foreach( $this->post_types as $post_type ){

            $connection_name = $post_type->name;

            $connections = array_filter( $p2p_connections_to_map, fn( $p2p_connection ) => $p2p_connection['from'] ===  $connection_name ||  $p2p_connection['to'] ===  $connection_name);

            foreach( $connections as $connection ){
                array_push($field_names,  $connection['name'] );
            } 
        }


        if( count ( $query_args['postToPostConnections']  ) === 1 ){

            $connection = $query_args['postToPostConnections'][0]['connection'];

            if( in_array( $connection, $field_names ) ){
                $connected_type = $connection;
                $query_args['connected_type'] = sanitize_text_field( $connected_type );
                $query_args['connected_items'] = array_map( 'absint', $query_args['postToPostConnections'][0]['ids'] );
                return $query_args;
            }
        }
    
        foreach( $query_args['postToPostConnections'] as $post_to_post_connection ) {

            if( in_array( $post_to_post_connection['connection'], $field_names ) ){

                $connected_type = $post_to_post_connection['connection'];

                $connected = new \WP_User_Query( array(
                    'posts_per_page'         => 1000,
                    'fields'                 => 'ids',
                    'connected_type'         => sanitize_text_field( $connected_type ),
                    'connected_items'        => array_map( 'absint', $post_to_post_connection['ids'] ),
                    'no_found_rows'          => true,  
                    'update_post_meta_cache' => false, 
                    'update_post_term_cache' => false, 
                ) );

                $post_ids = $connected->posts;

                if( ! $post_ids)  {
                    $query_args['post__in'] = [0];
                    return $query_args;
                }

                $post__in = $post__in ? array_values( array_intersect( $post__in, $post_ids ) ) : $post_ids;
            }
        }

        $query_args['post__in'] = $post__in;
        
        return $query_args;
    }

}



