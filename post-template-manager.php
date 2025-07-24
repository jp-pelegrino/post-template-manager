<?php
/*
Plugin Name: Post Template Manager
Description: Allows admins to create post templates with preset layouts, blocks, content, and featured images. Editors and writers can use these templates for standardized posts such as Job Postings or Invitations to Procurement/Bidding.
Version: 1.0.0-beta
Author: Your Name
Requires at least: 6.8
Requires PHP: 7.4
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Register Custom Post Type for Post Templates
function ptm_register_post_template_cpt() {
    $labels = array(
        'name'               => __( 'Post Templates', 'post-template-manager' ),
        'singular_name'      => __( 'Post Template', 'post-template-manager' ),
        'add_new'            => __( 'Add New Template', 'post-template-manager' ),
        'add_new_item'       => __( 'Add New Post Template', 'post-template-manager' ),
        'edit_item'          => __( 'Edit Post Template', 'post-template-manager' ),
        'new_item'           => __( 'New Post Template', 'post-template-manager' ),
        'view_item'          => __( 'View Post Template', 'post-template-manager' ),
        'search_items'       => __( 'Search Post Templates', 'post-template-manager' ),
        'not_found'          => __( 'No post templates found', 'post-template-manager' ),
        'not_found_in_trash' => __( 'No post templates found in Trash', 'post-template-manager' ),
        'menu_name'          => __( 'Post Templates', 'post-template-manager' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
        'menu_icon'          => 'dashicons-layout',
        'show_in_rest'       => true, // Enable Gutenberg
    );

    register_post_type( 'post_template', $args );
}
add_action( 'init', 'ptm_register_post_template_cpt' );

// Add 'Use Template' option to the Add New Post screen (Gutenberg)
function ptm_enqueue_admin_scripts( $hook ) {
    if ( 'post-new.php' !== $hook && 'edit.php' !== $hook ) return;

    wp_enqueue_script(
        'ptm-admin-js',
        plugins_url( 'js/ptm-admin.js', __FILE__ ),
        array( 'wp-api', 'wp-edit-post' ),
        '1.0.0-beta',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'ptm_enqueue_admin_scripts' );

// REST API endpoint to fetch template content
add_action( 'rest_api_init', function () {
    register_rest_route( 'ptm/v1', '/template/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => function( $request ) {
            $id = $request['id'];
            $post = get_post( $id );
            if ( $post && $post->post_type === 'post_template' ) {
                return array(
                    'title'   => $post->post_title,
                    'content' => $post->post_content,
                    'featured_image' => get_the_post_thumbnail_url( $id, 'full' ),
                );
            }
            return new WP_Error( 'not_found', 'Template not found', array( 'status' => 404 ) );
        },
        'permission_callback' => function () {
            return current_user_can( 'edit_posts' );
        }
    ));
});