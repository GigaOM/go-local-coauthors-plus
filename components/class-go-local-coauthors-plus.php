<?php

// @TODO move code dependencies from go-analytics, go-local-bsocial, go-google-analytics, and go-guestpost into this plugin
// See https://github.com/GigaOM/gigaom-plugins/issues/28

class GO_Local_Coauthors_Plus
{
	public function __construct()
	{
		add_filter( 'go_xpost_pre_send_post', array( $this, 'go_xpost_pre_send_post' ) );
		add_action( 'go_xpost_save_post', array( $this, 'go_xpost_save_post' ), 10, 2 );

		add_filter( 'go_theme_post_author', array( $this, 'go_theme_post_author_filter' ), 5, 2 );

		// This filter was added by VIP:
		// Only do co-author post lookups based on terms to avoid nasty queries because of how many terms there are
		add_filter( 'coauthors_plus_should_query_post_author', '__return_false' );
	}// end __construct

	/**
	 * filter the post author.  Don't use the default WP author, instead
	 * use guest or coauthors data. See GO_GuestPost::coauthors_posts_links
	 *
	 * @param $author author to filter
	 * @param $post WP_Post object
	 * @return author
	 */
	public function go_theme_post_author_filter( $author, $post )
	{
		$author = $this->coauthors_posts_links( NULL, NULL, NULL, NULL, FALSE );

		return $author;
	}//end go_theme_post_author_filter

	/**
	 * Replacement function for coauthors_posts_links that allows us to hook in additional custom functionality
	 */
	public function coauthors_posts_links( $between = NULL, $betweenLast = NULL, $before = NULL, $after = NULL, $echo = TRUE )
	{
		global $post;

		$post_id = get_the_ID();

		if ( ! $post_id )
		{
			return;
		}//end if

		$author = apply_filters( 'go_coauthor_posts_links', coauthors_posts_links( $between, $betweenLast, $before, $after, false ), $post_id );

		if ( $echo )
		{
			echo $author;
		}// end if

		return $author;
	} // END coauthors_posts_links

	/**
	 * Hooked to the go_xpost_pre_send_post filter
	 *
	 * @param $xpost stdClass custom post object
	 * @return $xpost stdClass filtered custom post object
	 */
	public function go_xpost_pre_send_post( $xpost )
	{
		global $coauthors_plus;

		if ( is_object( $coauthors_plus ) )
		{
			$xpost->co_authors_plus = get_coauthors( $xpost->origin->ID );
		}// end if

		return $xpost;
	}// end go_xpost_pre_send_post

	/**
	 * Hooked to the go_xpost_save_post action
	 *
	 * @param $post_id int post id being saved from an xpost
	 * @param $xpost stdClass custom post object
	 */
	public function go_xpost_save_post( $post_id, $xpost )
	{
		global $coauthors_plus;

		if ( ! is_object( $coauthors_plus ) )
		{
			return;
		}// end if

		if ( ! isset( $xpost->co_authors_plus ) )
		{
			return;
		}// end if

		$coauthors = array();

		// allow go_xpost to lookup all of these authors, which will trigger creations if appropriate
		foreach ( $xpost->co_authors_plus as $author )
		{
			go_xpost_util()->get_author( $author );
			$coauthors[] = $author->data->user_nicename;
		}// end foreach

		$coauthors_plus->add_coauthors( $post_id, $coauthors );
	}// end go_xpost_save_post
}//end class

/**
 * singleton
 */
function go_coauthors()
{
	global $go_coauthors;

	if ( ! isset( $go_coauthors ) )
	{
		$go_coauthors = new GO_Local_Coauthors_Plus();
	}// end if

	return $go_coauthors;
}// end go_xpost
