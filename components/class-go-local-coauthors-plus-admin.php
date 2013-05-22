<?php

class GO_Local_Coauthors_Plus_Admin
{
	public $id_base              = 'go_local_coauthors_plus_admin';
	public $refresh_author_cache = FALSE;
	public $author_cache_key     = 'go-local-coauthors-plus-authors';
	public $cron_key             = 'go_local_coauthors_plus_refresh_authors_cron';
	public $version              = 1;

	/**
	 * Constructor! BOOM!
	 */
	public function __construct()
	{
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( $this->cron_key, array( $this, 'refresh_author_cache' ) );

		// Deactivate nicely and clear our custom cron
		register_deactivation_hook( __FILE__, array( $this, 'cron_deregister' ) );
	}// end __construct

	/**
	 * hooked into the admin_init action
	 */
	public function admin_init()
	{
		// @TODO: update author cache when an editor's name has updated

		add_filter( 'update_user_metadata', array( $this, 'update_user_metadata' ), 10, 5 );
		add_action( 'updated_user_meta', array( $this, 'updated_user_meta' ), 10, 4 );
		add_action( 'profile_update', array( $this, 'profile_update' ), 10, 4 );
	}//end admin_init

	/**
	 * hooked into the admin_enqueue_scripts
	 */
	public function admin_enqueue_scripts( $hook )
	{
		// we only need to enqueue all of these scripts if we are on a post page
		if ( 'post-new.php' != $hook && 'post.php' != $hook )
		{
			return;
		}//end if

		wp_register_script( 'mockjax', plugins_url( 'js/external/jquery.mockjax.js', __FILE__ ), array(), '1.5.1', TRUE );
		wp_register_script( 'go-local-coauthors-plus-admin', plugins_url( 'js/go-local-coauthors-plus-admin.js', __FILE__ ), array( 'jquery', 'co-authors-plus-js', 'mockjax' ), $version, TRUE );

		$data = array(
			'authors' => $this->cached_authors(),
		);
		wp_localize_script( 'go-local-coauthors-plus-admin', 'go_local_coauthors_plus_admin', $data );

		wp_enqueue_script( 'mockjax' );
		wp_enqueue_script( 'go-local-coauthors-plus-admin' );
	}//end admin_enqueue_scripts

	/**
	 * hooked into the update_user_meta filter.  This method will determine if the author
	 * cache should be updated based on the changing role of the user being updated. This
	 * filter fires off BEFORE the wp_capabilities meta value has been updated, allowing
	 * us to look at the soon-to-be old capabilities and compare with the new.
	 */
	public function update_user_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value )
	{
		if ( 'wp_capabilities' != $meta_key )
		{
			return $check;
		}//end if

		// get the user object so we can check capabilities
		$user = get_user_by( 'id', $object_id );

		$could_edit_posts = $this->role_set_has_capability( $user->roles, 'edit_posts' );
		$can_edit_posts   = $this->role_set_has_capability( array_keys( $meta_value ), 'edit_posts' );

		// if the ability to edit posts has changed, we'll want to refresh the cached
		// collection of authors
		if ( $could_edit_posts != $can_edit_posts )
		{
			$this->refresh_author_cache = TRUE;
		}//end if

		return $check;
	}//end update_user_metadata

	/**
	 * given an array of roles, check for the existence of a given
	 * capability
	 */
	public function role_set_has_capability( $user_roles, $capability )
	{
		$editor_roles = $this->editor_roles();

		$user_roles = is_array( $user_roles ) ? $user_roles : array();

		foreach ( $user_roles as $role_id )
		{
			// if the role is set to FALSE for some reason, just move on
			if ( ! isset( $editor_roles[ $role_id ] ) )
			{
				continue;
			}//end if

			return TRUE;
		}//end foreach

		return FALSE;
	}//end role_set_has_edit_capability

	/**
	 * this action fires AFTER update_user_metadata - which calculates whether or not
	 * the author cache should be refreshed.
	 */
	public function updated_user_meta( $meta_id, $object_id, $meta_key, $meta_value )
	{
		// bail if not updating capabilities
		if ( 'wp_capabilities' != $meta_key )
		{
			return;
		}//end if

		// if the author cache doesn't need to be refreshed, bail
		if ( ! $this->refresh_author_cache )
		{
			return;
		}//end if

		$this->refresh_author_cache();

		// reset the 'refresh_author_cache' status
		$this->refresh_author_cache = FALSE;
	}//end profile_update

	/**
	 * hooked to the profile_update action
	 */
	public function profile_update( $user_id, $old_user_data )
	{
		// get the user object so we can check capabilities
		$user = get_user_by( 'id', $user_id );

		if ( $user->has_cap( 'edit_posts' ) )
		{
			$this->refresh_author_cache();
		}//end if
	}//end profile_update

	/**
	 * Get authors from site options if they exist.  If they aren't stored in site options,
	 * generate the authors and store them in options.
	 */
	public function cached_authors()
	{
		$authors = get_option( $this->author_cache_key, array() );

		if ( ! $authors )
		{
			$this->refresh_author_cache();
		}//end if

		return $authors;
	}//end cached_authors

	/**
	 * refresh the author cache
	 */
	public function refresh_author_cache()
	{
		update_option( $this->author_cache_key, $this->simple_authors() );
	}//end refresh_author_cache

	/**
	 * generates a simple array of stdClass authors
	 */
	public function simple_authors()
	{
		$simple_authors = array();

		$roles = $this->editor_roles();

		foreach ( $roles as $key => $role )
		{
			$args = array(
				'role' => $key,
			);

			$authors = new WP_User_Query( $args );

			if ( ! empty( $authors->results ) )
			{
				foreach ( $authors->results as $author )
				{
					if ( isset( $simple_authors[ $author->ID ] ) )
					{
						continue;
					}//end if

					$simple                = new stdClass;
					$simple->ID            = $author->ID;
					$simple->user_login    = $author->user_login;
					$simple->user_nicename = $author->user_nicename;
					$simple->user_email    = $author->user_email;
					$simple->display_name  = $author->display_name;

					$simple_authors[ $author->ID ] = $simple;
				}//end foreach
			}//end if
		}//end foreach

		return $simple_authors;
	}//end simple_authors

	/**
	 * return a list of roles that have edit privileges
	 */
	public function editor_roles()
	{
		global $wp_roles;
		static $roles = array();

		if ( $roles )
		{
			return $roles;
		}//end if

		foreach ( $wp_roles->role_objects as $key => $role )
		{
			if ( ! $role->has_cap( 'edit_posts' ) )
			{
				continue;
			}//end if

			$roles[ $key ] = $role;
		}//end foreach

		return $roles;
	}//end editor_roles

	/**
	 * when the plugin is activated, activate our new custom cron hook
	 */
	public function cron_register()
	{
		if ( ! wp_next_scheduled( $this->cron_key ) )
		{
			wp_schedule_event( time(), 'daily', $this->cron_key );
		}//end if
	}//end cron_register

	/**
	 * clear out this cron hook when the plugin is deactivated
	 */
	public function cron_deregister()
	{
		wp_clear_scheduled_hook( $this->cron_key );
	}//end cron_deregister
}//end class

function go_local_coauthors_plus_admin()
{
	global $go_local_coauthors_plus_admin;

	if ( ! $go_local_coauthors_plus_admin )
	{
		$go_local_coauthors_plus_admin = new GO_Local_Coauthors_Plus_Admin;
	}//end if

	return $go_local_coauthors_plus_admin;
}//end go_local_coauthors_plus_admin
