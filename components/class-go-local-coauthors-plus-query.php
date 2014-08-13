<?php

/**
 * query-related co-authors functionalities
 */
class GO_Local_Coauthors_Plus_Query
{
	private $converted_author_query = FALSE;

	public function __construct()
	{
		// we want our init to run after co-author-plus has added its filters
		add_action( 'init', array( $this, 'init' ), 199 );

		// parse_query needs to be run before co-author-plus' posts_* filters,
		// which are at priority 10
		add_action( 'parse_query', array( $this, 'parse_query' ), 9 );
	}//end __construct

	/**
	 * remove the filters co-author-plus adds to modify the query.
	 *
	 * @global GO_Local_Coauthors_Plus $coauthors_plus
	 */
	public function init()
	{
		global $coauthors_plus;
		remove_filter( 'posts_where', array( $coauthors_plus, 'posts_where_filter' ), 10, 2 );
		remove_filter( 'posts_join', array( $coauthors_plus, 'posts_join_filter' ), 10, 2 );
		remove_filter( 'posts_groupby', array( $coauthors_plus, 'posts_groupby_filter' ), 10, 2 );
	}//end init

	/**
	 * detect an author/author_name query and convert it into a taxonomy
	 * query. note that WP_Query looks for the user-supplied tax_query
	 * in $wp_query->query_vars, not $wp_query->tax_query.
	 *
	 * @param WP_Query $wp_query
	 * @global GO_Local_Coauthors_Plus $coauthors_plus
	 * @return null
	 */
	public function parse_query( $wp_query )
	{
		global $coauthors_plus;

		if ( ! $wp_query->is_author )
		{
			return;
		}//end if

		// check if the queried post type is in the coauthors-plus supported types
		if ( isset( $wp_query->query_vars['post_type'] ) && ! in_array( $wp_query->query_vars['post_type'], $coauthors_plus->supported_post_types ) )
		{
			return;
		}// end if

		// get the user_nicename so we can use coauthor to get the
		// actual author term. this is to account for some author slugs
		// having the "cap-" prefix added by co-authors-plus
		$user_nicename = FALSE; // default case
		if ( ! isset( $wp_query->query['author_name'] ) || empty( $wp_query->query['author_name'] ) )
		{
			$user = get_user_by( 'id', $wp_query->query['author'] );
			if ( $user )
			{
				$user_nicename = $user->user_nicename;
			}//end if
			else
			{
				// we got an invalid author id
				$wp_query->set_404();
				return;
			}//end else
		}//end if
		else
		{
			// already have the user_nicename (slug)
			$user_nicename = $wp_query->query_vars['author_name'];
		}//end else

		if ( ! $user_nicename )
		{
			return; // we don't have a valid author
		}//end if

		$author_term = FALSE;
		$coauthor = $coauthors_plus->get_coauthor_by( 'user_nicename', $user_nicename );
		if ( FALSE != $coauthor )
		{
			$term_obj = $coauthors_plus->get_author_term( $coauthor );
			if ( $term_obj )
			{
				$author_term = $term_obj->slug;
			}//end if
		}//end if

		// give up if we don't find the author as an author term
		if ( FALSE == $author_term )
		{
			$wp_query->set_404();
			return;
		}//end if

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
		}//end if
		else
		{
			$wp_query->query_vars['tax_query'] = array( $author_tax_query );
		}//end else

		$wp_query->set( 'author_name', '' );
		$wp_query->set( 'author', '' );
		$wp_query->is_author = FALSE;
		$wp_query->is_tax = TRUE;

		// set this flag so we know we've modified the authorh query
 		$this->converted_author_query = TRUE;

		// We just converted an author query to a coauthors-plus taxonomy
		// query by setting $wp_query->is_author to FALSE. To avoid confusing
		// other code that depend on that flag, we'll set the is_author flag
		// back to TRUE after the converted query has been executed.
		// this is will be done in the "posts_selection" action callback.
		add_action( 'posts_selection', array( $this, 'posts_selection' ) );
	}//end parse_query

	/**
	 * this function is hooked to 'posts_selection' conditionally at the
	 * end of parse_query(), only if we actually converted an author query
	 * into a coauthors-plus taxonomy query. so when this function is invoked
	 * and if we detect that we are indeed running a coauthors-plus taxonomy
	 * query, we'll restore the value of the $wp_query->is_author variable
	 * to TRUE so WP_Query and other code that depend on it will still
	 * think this is an author query.
	 */
	public function posts_selection()
	{
		// remove ourself to avoid extraneous processing
		remove_action( 'posts_selection', array( $this, 'posts_selection' ) );

		global $wp_query;

		// if we had modified the $wp_query->is_author then restore it
		if ( $this->converted_author_query )
		{
			$this->converted_author_query = FALSE;
			$wp_query->is_author = TRUE;
		}
	}//END posts_selection
}//end GO_Local_Coauthors_Plus_Query

/**
 * singleton
 *
 * @global GO_Local_Coauthors_Plus $coauthors_plus
 * @return GO_Local_Coauthors_Plus_Admin
 */
function go_coauthors_query()
{
	global $go_coauthors_query;

	if ( ! isset( $go_coauthors_query ) )
	{
		$go_coauthors_query = new GO_Local_Coauthors_Plus_Query();
	}//end if

	return $go_coauthors_query;
}//end go_coauthors_query