<?php

/**
 * query-related co-authors functionalities 
 */
class GO_Local_Coauthors_Plus_Query
{
	public function __construct()
	{
		// we want our init to run after co-author-plus has added its filters
		add_action( 'init', array( $this, 'init' ), 200 );

		// parse_query needs to be run before co-author-plus' posts_* filters,
		// which're at priority 10
		add_action( 'parse_query', array( $this, 'parse_query' ), 9 );
	}//END __construct

	/**
	 * remove the filters co-author-plus adds to modify the query.
	 */
	public function init()
	{
		global $coauthors_plus;
		remove_filter( 'posts_where', array( $coauthors_plus, 'posts_where_filter' ), 10, 2 );
		remove_filter( 'posts_join', array( $coauthors_plus, 'posts_join_filter' ), 10, 2 );
		remove_filter( 'posts_groupby', array( $coauthors_plus, 'posts_groupby_filter' ), 10, 2 );
	}//END init

	/**
	 * detect an author/author_name query and convert it into a taxonomy
	 * query. note that WP_Query looks for the user-supplied tax_query
	 * in $wp_query->query_vars, not $wp_query->tax_query.
	 */
	public function parse_query( $wp_query )
	{
		if ( $wp_query->is_author )
		{
			// get author_name if we only have author id
			if ( ! isset( $wp_query->query['author_name'] ) || empty( $wp_query->query['author_name'] ) )
			{
				$user = get_user_by( 'id', $wp_query->query['author'] );
				$author_name = $user->user_nicename;
			}
			else
			{
				$author_name = $wp_query->query_vars['author_name'];
			}

			$author_tax_query = array(
				'taxonomy' => 'author',
				'terms' => array( $author_name ),
				'include_children' => 1,
				'field' => 'slug',
				'operator' => 'IN',
			);
			if ( isset( $wp_query->query_vars['tax_query'] ) && is_array( $wp_query->query_vars['tax_query'] ) )
			{
				$wp_query->query_vars['tax_query'][] = $author_tax_query;
			}
			else
			{
				$wp_query->query_vars['tax_query'] = array( $author_tax_query );
			}

			$wp_query->set( 'author_name', '' );
			$wp_query->set( 'author', '' );
			$wp_query->is_author = FALSE;
		}//END if
	}//END parse_query

}//END class GO_Local_Coauthors_Query