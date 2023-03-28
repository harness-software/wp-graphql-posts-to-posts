# 📄↔📄 WPGraphQL for Posts 2 Posts

WordPress plugin that creates GraphQL connections for all of your [Posts 2 Posts](https://wordpress.org/plugins/posts-to-posts/) connections.

## Overview

- All registered Posts 2 Posts connections will be automatically added as connections in the GraphQL schema.
- The field for each in the GraphQL schema will be the name of the Posts 2 Post connection, converted to camelCase and with the word "Connection" appended to it. So if the Posts 2 Posts connection is registered as `projects_to_managers`, the field in the GraphQL schema will be named `projectsToManagersConnection` (see the example below).
- Supports posts<->posts, posts<->users, and users<->users connections.

### Example

Let's say you have registered a Posts 2 Posts connection between the `project` custom post type on your site and the `user` object, like this:

```php
function register_p2p_connection() {
  p2p_register_connection_type( [
    'name' => 'projects_to_managers',
    'from' => 'project',
    'to'   => 'user'
  ] );
}
add_action( 'p2p_init', 'register_p2p_connection' );
```

With this plugin activated, you can query for the users connected to projects, like this:

```graphql
query getProjects {
  projects(first: 10) {
    nodes {
      databaseId
      title
      projectsToManagersConnection {
        nodes {
          databaseId
          name
        }
      }
    }
  }
}
```

You can also query from the other direction, and get the projects connected to users, like this:

```graphql
query getUsers {
  users(first: 10) {
    nodes {
      databaseId
      name
      projectsToManagersConnection {
        nodes {
          databaseId
          title
        }
      }
    }
  }
}
```

## Minimum Software Requirements

- PHP 7.4+
- [WPGraphQL](https://github.com/wp-graphql/wp-graphql) 1.8.1+ (1.10.0+ recommended)
- [Posts 2 Posts](https://wordpress.org/plugins/posts-to-posts/) 1.6.6+

## Future Enhancements

1. Register a `where` arg for each post type/user object so you can query to find posts/users that are connected to them.
2. Expose [connection metadata](https://github.com/scribu/wp-posts-to-posts/wiki/Connection-metadata) as edge fields in the GraphQL schema.
3. Register input fields for the create & update mutations so you can update the connections.

I think the input fields for #3 will end up looking something like this:

```graphql
projectsToManagersConnections: {
    append: false
    databaseIds: [1,4,8]
}
```
