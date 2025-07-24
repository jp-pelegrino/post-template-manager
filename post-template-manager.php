...
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
    wp_localize_script(
        'ptm-admin-js',
        'ptmAdmin',
        array(
            'nonce' => wp_create_nonce( 'wp_rest' )
        )
    );
}
add_action( 'admin_enqueue_scripts', 'ptm_enqueue_admin_scripts' );
...