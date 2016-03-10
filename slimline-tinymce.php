<?php
/**
 * Plugin Name: Slimline Term and User TinyMCE
 * Plugin URI: http://www.michaeldozark.com/slimline/term-user-tinymce/
 * Description: Adds TinyMCE editor to Term Descriptions and User Bios
 * Author: Michael Dozark
 * Author URI: http://www.michaeldozark.com/
 * Version: 0.3.0
 * License: GPL2
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2.0, as published by the Free Software Foundation.  You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package    Slimline / Term and User TinyMCE
 * @version    0.3.1
 * @author     Michael Dozark <michael@michaeldozark.com>
 * @copyright  Copyright (c) 2016, Michael Dozark
 * @link       http://www.michaeldozark.com/wordpress/slimline/term-user-tinymce/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit; // exit if accessed directly

/**
 * Call initialization function.
 *
 * @link https://developer.wordpress.org/reference/hooks/plugins_loaded/
 *       Documentation of `plugins_loaded` hook
 */
add_action( 'plugins_loaded', 'slimline_tinymce' );

/**
 * Initialize plugin
 *
 * @link  https://github.com/slimline/tinymce/wiki/slimline_tinymce()
 * @since 0.1.0
 */
function slimline_tinymce() {

	/**
	 * Stop initialization if not in the dashboard
	 *
	 * @link https://developer.wordpress.org/reference/functions/is_admin/
	 *       Documentation of `is_admin` function
	 */
	if ( ! is_admin() ) {
		return;
	} // if ( ! is_admin() )

    /**
     * Enqueue styles to fix wp editor on term and user pages
     *
     * The term and user pages include styles that cause quicktag buttons to span the
     * full width of the editor. We will load some CSS to fix that by setting them to
     * width "auto".
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
     *       Documentation of `admin_enqueue_scripts` hook
     */
	add_action( 'admin_enqueue_scripts', 'slimline_tinymce_admin_enqueue_scripts' );

	/**
	 * Add filters to edit-tags page
	 *
	 * @link https://developer.wordpress.org/reference/hooks/load-plugin_page/
	 *       Documentation of `load-{$plugin_page}` hook
	 */
	add_action( 'load-edit-tags.php', 'slimline_add_term_tinymce' );

	/**
	 * Add filters to profile page
	 *
	 * @link https://developer.wordpress.org/reference/hooks/load-plugin_page/
	 *       Documentation of `load-{$plugin_page}` hook
	 */
	add_action( 'load-profile.php', 'slimline_add_user_tinymce' );

	/**
	 * Add filters to user-edit page
	 *
	 * @link https://developer.wordpress.org/reference/hooks/load-plugin_page/
	 *       Documentation of `load-{$plugin_page}` hook
	 */
	add_action( 'load-user-edit.php', 'slimline_add_user_tinymce' );

}


/**
 * Init TinyMCE hooks for taxonomy pages.
 *
 * Adds the action hooks for replacing the term description textarea with a TinyMCE
 * editor. Using a separate function allows us to dynamically add the hooks to all
 * taxonomies.
 *
 * @link  https://github.com/slimline/tinymce/wiki/slimline_add_term_tinymce()
 * @since 0.1.0
 */
function slimline_add_term_tinymce() {

	/**
	 * @global string $taxnow The taxonomy slug for the current term
	 */
	global $taxnow;

	/**
	 * Exit function if taxonomy not set
	 */
	if ( ! $taxnow ) {
		return;
	} // if ( ! $taxnow )

	/**
	 * Filter which users are allowed to use TinyMCE for term descriptions
	 *
	 * By default, any user who can edit categories can also use TinyMCE in term
	 * descriptions. This is redundant since those users are also the only ones who
	 * can access the edit-tags.php page, but use of the filter allows developers to
	 * create more stringent or varied rules if they choose.
	 *
	 * @param bool Whether or not the current user is allowed to use the TinyMCE
	 *             Editor for term descriptions. Defaults to TRUE if the user can
	 *             manage categories, FALSE if they cannot.
	 * @link  https://codex.wordpress.org/Roles_and_Capabilities#manage_categories
	 *        Description of `manage_categories` capability
	 * @since 0.3.0
	 */
	$add_term_tinymce = apply_filters( 'slimline_add_term_tinymce', current_user_can( 'manage_categories' ) );

	if ( $add_term_tinymce ) {

		/**
		 * Remove default term description filter.
		 *
		 * The default wp_filter_kses for term descriptions strips most HTML content,
		 * rendering the TinyMCE editor useless. We will add the same HTML filter as
		 * is used with posts later.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/pre_term_description/
		 *       Description of `pre_term_description` filter
		 */
		remove_filter( 'pre_term_description', 'wp_filter_kses' );

		/**
		 * Start output buffering for add term form
		 *
		 * We force passing 0 parameters to prevent setting ob_start's callback
		 * parameter.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/taxonomy_pre_add_form/
		 *       Documentation of `{$taxnow}_pre_add_form` hook
		 */
		add_action( "{$taxnow}_pre_add_form", 'ob_start', 1000, 0 );

		/**
		 * Complete buffering and output content for add term form
		 *
		 * @link https://developer.wordpress.org/reference/hooks/taxonomy_pre_add_form/
		 *       Documentation of `{$taxnow}_add_form_fields` hook
		 */
		add_action( "{$taxnow}_add_form_fields", 'slimline_tinymce_output_wp_editor', 0 );

		/**
		 * Start output buffering for edit term form
		 *
		 * We force passing 0 parameters to prevent setting ob_start's callback
		 * parameter.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/taxonomy_pre_add_form/
		 *       Documentation of `{$taxnow}_pre_edit_form` hook
		 */
		add_action( "{$taxnow}_pre_edit_form", 'ob_start', 1000, 0 );

		/**
		 * Complete buffering and output content for edit term form
		 *
		 * @link https://developer.wordpress.org/reference/hooks/taxonomy_pre_add_form/
		 *       Documentation of `{$taxnow}_edit_form_fields` hook
		 */
		add_action( "{$taxnow}_edit_form_fields", 'slimline_tinymce_output_wp_editor', 0 );

		/**
		 * Add posts HTML filter to term descriptions.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/pre_term_description/
		 *       Documentation of `pre_term_description` filter
		 */
		add_filter( 'pre_term_description', 'slimline_wp_filter_kses' );

	} // if ( $add_term_tinymce )

}

/**
 * Init TinyMCE hooks for user pages.
 *
 * Adds the action hooks for replacing the bio / user description textarea with a
 * TinyMCE editor. Using a separate function allows us to dynamically add the hooks
 * based on user capabilities.
 *
 * @link  https://github.com/slimline/tinymce/wiki/slimline_add_user_tinymce()
 * @since 0.2.0
 */
function slimline_add_user_tinymce() {

	/**
	 * Filter which users are allowed to use TinyMCE for user bios
	 *
	 * By default, only users who can contribute to the site are allowed to use HTML
	 * in user bios.
	 *
	 * @param bool Whether or not the current user is allowed to use the TinyMCE
	 *             Editor for user bios. Defaults to TRUE is the user can also edit
	 *             posts, FALSE if they cannot.
	 * @link  https://codex.wordpress.org/Roles_and_Capabilities#edit_posts
	 *        Description of `edit_posts` capability
	 * @since 0.3.0
	 */
	$add_user_tinymce = apply_filters( 'slimline_add_user_tinymce', current_user_can( 'edit_posts' ) );

	if ( $add_user_tinymce ) {

		/**
		 * Remove default user description filter.
		 *
		 * The default wp_filter_kses for user descriptions strips most HTML content,
		 * rendering the TinyMCE editor useless. We will add the same HTML filter as
		 * is used with posts later.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/pre_user_description/
		 *       Documentation of `pre_user_description` filter
		 */
		remove_filter( 'pre_user_description', 'wp_filter_kses' );

		/**
		 * Replace default description textarea with a TinyMCE editor
		 *
		 * @link https://developer.wordpress.org/reference/hooks/edit_user_profile/
		 *       Documentation of `edit_user_profile` hook
		 */
		add_action( 'edit_user_profile', 'slimline_tinymce_output_wp_editor', 0 );

		/**
		 * Begin output buffering for profile description
		 *
		 * We force passing 0 parameters to prevent setting ob_start's callback
		 * parameter.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/personal_options/
		 *       Documentation of `personal_options` hook
		 */
		add_action( 'personal_options', 'ob_start', 1000, 0 );

		/**
		 * Replace default description textarea with a TinyMCE editor
		 *
		 * @link https://developer.wordpress.org/reference/hooks/show_user_profile/
		 *       Documentation of `show_user_profile` hook
		 */
		add_action( 'show_user_profile', 'slimline_tinymce_output_wp_editor', 0 );

		/**
		 * Add posts HTML filter to user descriptions.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/pre_user_description/
		 *       Documentation of `pre_user_description` filter
		 */
		add_filter( 'pre_user_description', 'slimline_wp_filter_kses' );

	} // if ( $add_user_tinymce )

}

/**
 * Enqueue custom admin styles and scripts.
 *
 * @link  https://github.com/slimline/tinymce/wiki/slimline_tinymce_admin_enqueue_scripts()
 * @since 0.1.2
 */
function slimline_tinymce_admin_enqueue_scripts() {

	/**
	 * Enqueues styles to keep quicktags from stretching too wide in the WP Editor.
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_enqueue_style/
	 *       Documentation of `wp_enqueue_style` function
	 * @link https://developer.wordpress.org/reference/functions/trailingslashit/
	 *       Documentation of `trailingslashit` function
	 * @link https://developer.wordpress.org/reference/functions/plugin_dir_url/
	 *       Documentation of `plugin_dir_url` function
	 */
	wp_enqueue_style( 'slimline-tinymce', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'slimline-tinymce.min.css', false, '0.1.0', 'all' );
}

/**
 * Replaces default taxonomy and profile description textareas with an instance of
 * the TinyMCE editor.
 *
 * @param object|string $object The term or user object being edited or the name of
 *                              the taxonomy if on an add term screen.
 * @link  https://github.com/slimline/tinymce/wiki/slimline_tinymce_output_wp_editor()
 * @since 0.1.0
 */
function slimline_tinymce_output_wp_editor( $object ) {

	/**
	 * End the buffering started in slimline_tinymce_ob_start and get buffer contents
	 */
	$form_fields = ob_get_clean();

	/**
	 * Set up wp_editor parameters based on which screen we are on.
	 *
	 * @link https://developer.wordpress.org/reference/functions/current_filter/
	 *       Documentation of `current_filter` function
	 */
	if ( strpos( current_filter(), 'add' ) ) {

		$description_id = 'tag-description';
		$description_text = ''; // empty since it has not been set yet

	} else { // if ( strpos( current_filter(), 'add' ) )

		$description_id = 'description';

		if ( $object instanceof WP_User ) {

			$description_text = get_the_author_meta( 'description', $object->ID );

		} else { // if ( $object instanceof WP_User )

			$description_text = get_term_field( 'description', $object->term_id, $object->taxonomy, 'raw' );

		} // if ( $object instanceof WP_User )

	} // if ( strpos( current_filter(), 'add' ) )

	/**
	 * Filter whether or not to show the media buttons
	 *
	 * @param bool Whether or not to include the TinyMCE media buttons. By default
	 *             this is TRUE if the user can upload files, FALSE if not.
	 * @link  https://codex.wordpress.org/Roles_and_Capabilities#upload_files
	 *        Description of `upload_files` capability
	 * @since 0.3.0
	 */
	$media_buttons = apply_filters( 'slimline_tinymce_media_buttons', current_user_can( 'upload_files' ) );

	/**
	 * Filter wp_editor args
	 *
	 * @param array Arguments for the wp_editor function. By default this will
	 *              contain only the media_buttons argument filtered previously.
	 * @link  https://developer.wordpress.org/reference/classes/_wp_editors/editor/
	 *        Description of editor arguments
	 * @since 0.3.0
	 */
	$wp_editor_args = apply_filters( 'slimline_tinymce_editor_args', array( 'media_buttons' => $media_buttons ) );

	/**
	 * Use 'description' as the textarea name for the editor or descriptions will not save properly
	 */
	$wp_editor_args[ 'textarea_name' ] = 'description';

	/**
	 * Use the output buffer to get the wp_editor markup
	 *
	 * We are buffering the contents because wp_editor is an echo-only function and
	 * we need to return it as a string so we can do a preg_replace later.
	 */
	ob_start();

	/**
	 * create the WordPress Editor
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_editor/
	 *       Documentation of `wp_editor` function
	 */
	wp_editor( $description_text, $description_id, $wp_editor_args );

	/**
	 * stop buffering and retrieve the markup
	 */
	$wp_editor = ob_get_clean();

	/**
	 * Replace the description textarea with the editor instance
	 */
	echo preg_replace( '#<textarea name="description"([^>]+)>(.*)</textarea>#is', $wp_editor, $form_fields );
}

/**
 * Replacement for wp_filter_kses that accepts the same HTML tags as are allowed for posts
 *
 * @param  string $data HTML markup to filter for user content (slashed -- must
 *                      stripslashes to edit then re-add slashes before returning)
 * @return string       HTML markup filtered through wp_kses()
 * @link   https://github.com/slimline/tinymce/wiki/slimline_wp_filter_kses()
 * @since  0.1.2
 */
function slimline_wp_filter_kses( $data ) {

	/**
	 * wp_kses call
	 *
	 * @param string Content to filter through wp_kses (the un-slashed description
	 *               content)
	 * @param array  Allowed HTML elements. Here we are retrieving the HTML elements
	 *               allowed for posts. Users can filter this array using the
	 *               `wp_kses_allowed_html` filter.
	 * @link  https://developer.wordpress.org/reference/functions/wp_kses/
	 *        Documentation of `wp_kses` function
	 * @link  https://developer.wordpress.org/reference/functions/wp_kses_allowed_html/
	 *        Documentation of `wp_kses` function
	 */
	return addslashes( wp_kses( stripslashes( $data ), wp_kses_allowed_html( 'post' ) ) );
}