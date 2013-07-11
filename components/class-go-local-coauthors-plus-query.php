<?php

/**
 * query-related co-authors functionalities 
 */
class GO_Local_Coauthors_Plus_Query
{
	public function __construct()
	{
		// we want our init to run after co-author-plus has added its filters
		add_action( 'init', array( $this, 'init' ), 199 );

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
		global $coauthors_plus;

		if ( $wp_query->is_author )
		{
			// get author_name if we only have author id
			$author_term = FALSE;
			if ( ! isset( $wp_query->query['author_name'] ) || empty( $wp_query->query['author_name'] ) )
			{
				$user = get_user_by( 'id', $wp_query->query['author'] );
				$author_term = $user->user_nicename;
			}
			else
			{
				// this is already a user_nicename so we start with it
				$author_term = $author_name;

				// but try to use the coauthor term if possible.
				$author_name = $wp_query->query_vars['author_name'];
				$coauthor = $coauthors_plus->get_coauthor_by( 'user_nicename', $author_name );
				if ( FALSE != $coauthor )
				{
					$term_obj = $coauthors_plus->get_author_term( $coauthor );
					if ( $term_obj )
					{
						$author_term = $term_obj->slug;
					}
				}
			}//END if-else

			// give up if we don't find the author as an author term
			if ( FALSE == $author_term )
			{
				return;
			}

			$author_tax_query = array(
				'taxonomy' => $coauthors_plus->coauthor_taxonomy,
				'terms' => array( $author_term ),
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
			}//END if-else

			$wp_query->set( 'author_name', '' );
			$wp_query->set( 'author', '' );
			$wp_query->is_author = FALSE;
		}//END if
	}//END parse_query

}//END class GO_Local_Coauthors_Query

/**
 * singleton
 */
function go_coauthors_query()
{
	global $go_coauthors_query;

	if ( ! isset( $go_coauthors_query ) )
	{
		$go_coauthors_query = new GO_Local_Coauthors_Plus_Query();
	}// end if

	return $go_coauthors_query;
}// end go_coauthors_query