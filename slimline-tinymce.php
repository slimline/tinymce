<?php
/**
 * Plugin Name: Slimline Term and User TinyMCE
 * Plugin URI: http://www.michaeldozark.com/wordpress/slimline/term-user-tinymce/
 * Description: Adds TinyMCE editor to Term Descriptions and User Bios
 * Author: Michael Dozark
 * Author URI: http://www.michaeldozark.com/
 * Version: 0.1.3
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
 * @package Slimline
 * @subpackage Term and User TinyMCE
 * @version 0.1.2
 * @author Michael Dozark <michael@michaeldozark.com>
 * @copyright Copyright (c) 2014, Michael Dozark
 * @link http://www.michaeldozark.com/wordpress/slimline/term-user-tinymce/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit; // exit if accessed directly

/**
 * Initialize plugin. This should be the only instance of add_action() outside of a defined function.
 */
add_action( 'wp_loaded', 'slimline_tinymce_core' );

/**
 * slimline_tinymce_core function
 *
 * Initialize plugin
 *
 * @since 0.1.0
 */
function slimline_tinymce_core() {

	if ( ! is_admin() )
		return; // stop processing if not in the dashboard

	remove_filter( 'pre_user_description', 'wp_filter_kses' );

	add_action( 'admin_enqueue_scripts', 'slimline_tinymce_admin_enqueue_scripts' ); // enqueue styles to fix wp editor on term and user pages
	add_action( 'edit_user_profile', 'slimline_tinymce_descriptions', 0 ); // replace default description textarea with a TinyMCE editor
	add_action( 'load-edit-tags.php', 'slimline_add_term_tinymce' ); // add filters to edit-tags page
	add_action( 'personal_options', 'slimline_ob_start', 0 ); // begin object buffering for profile description
	add_action( 'show_user_profile', 'slimline_tinymce_descriptions', 0 ); // replace default description textarea with a TinyMCE editor

	add_filter( 'pre_user_description', 'michaeldozark_wp_filter_kses' );
}


/**
 * slimline_add_term_tinymce function
 *
 * Adds the action hooks for replacing the term description textarea with a TinyMCE editor.
 * Using a separate function allows us to dynamically add the hooks to all taxonomies.
 *
 * @since 0.1.0
 */
function slimline_add_term_tinymce() {

	if ( ! isset( $_REQUEST[ 'taxonomy' ] ) )
		return; // need to know which taxonomy is being edited so we can set the proper hooks

	$taxonomy = $_REQUEST[ 'taxonomy' ];

	/**
	 * Add actions to priority 0 to hopefully fire before all other template and plugin actions.
	 */
	add_action( "{$taxonomy}_pre_add_form", 'slimline_ob_start', 0 );
	add_action( "{$taxonomy}_add_form_fields", 'slimline_tinymce_descriptions', 0 );
	add_action( "{$taxonomy}_pre_edit_form", 'slimline_ob_start', 0 );
	add_action( "{$taxonomy}_edit_form_fields", 'slimline_tinymce_descriptions', 0 );
}

/**
 * slimline_tinymce_admin_enqueue_scripts function
 *
 * Enqueues styles to keep quicktags from stretching too wide in the WP Editor.
 *
 * @since 0.1.2
 */
function slimline_tinymce_admin_enqueue_scripts() {

	wp_enqueue_style( 'slimline-tinymce', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'slimline-tinymce.min.css', false, '0.1.0', 'all' );
}

/**
 * slimline_ob_start action
 *
 * Begins object buffering. Must be wrapped in a hooked function rather than hooking directly
 * or it will not work.
 *
 * @since 0.1.0
 */
function slimline_ob_start() {

	ob_start();
}

/**
 * slimline_tinymce_descriptions action
 *
 * Replaces default taxonomy and profile description textareas with an instance of the TinyMCE editor.
 *
 * @param object $object The term or user object being edited or the name of the taxonomy if on an add term screen.
 * @since 0.1.0
 */
function slimline_tinymce_descriptions( $object ) {

	/**
	 * End the buffering started in slimline_ob_start and get buffer contents
	 */
	$form_fields = ob_get_clean();

	/**
	 * Set up wp_editor parameters based on which screen we are on.
	 */
	if ( strpos( current_filter(), 'add' ) ) {
		$description_id = 'tag-description';
		$description_text = ''; // empty since it has not been set yet

	} else { // if ( strpos( current_filter(), 'add' ) )
		$description_id = 'description';

		if ( is_a( $object, 'WP_User' ) ) {
			$description_text = get_the_author_meta( 'description', $object->ID );

		} else { // if ( is_a( $object, 'WP_User' ) )
			$description_text = get_term_field( 'description', $object->term_id, $object->taxonomy, 'raw' );

		} // if ( is_a( $object, 'WP_User' ) )

	} // if ( strpos( current_filter(), 'add' ) )

	/**
	 * Use the object buffer to get the wp_editor markup
	 */
	ob_start();
	wp_editor( $description_text, $description_id, array( 'textarea_name' => 'description' ) ); // @see http://codex.wordpress.org/Function_Reference/wp_editor
	$wp_editor = ob_get_clean();

	/**
	 * Replace the description textarea with the editor instance
	 */
	echo preg_replace( '#<textarea name="description"([^>]+)>(.*)</textarea>#is', $wp_editor, $form_fields );
}

/**
 * michaeldozark_wp_filter_kses function
 *
 * Replacement for wp_filter_kses that accepts the same HTML tags as are allowed for posts
 *
 * @param string $data HTML markup to filter
 * @return string HTML markup filtered through wp_kses()
 * @since 0.1.2
 * @uses wp_kses()
 * @uses wp_kses_allowed_html()
 */
function michaeldozark_wp_filter_kses( $data ) {

	return addslashes( wp_kses( stripslashes( $data ), wp_kses_allowed_html( 'post' ) ) );
}