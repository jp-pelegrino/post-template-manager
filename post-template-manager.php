<?php
/**
 * Plugin Name: Post Template Manager
 * Plugin URI: https://github.com/jp-pelegrino/post-template-manager
 * Description: A WordPress plugin that allows admins to create post templates with preset layouts, blocks, content, and featured images for easy content creation.
 * Version: 1.0.0
 * Author: JP Pelegrino
 * Author URI: https://github.com/jp-pelegrino
 * License: Unlicense
 * License URI: https://unlicense.org/
 * Text Domain: post-template-manager
 * Domain Path: /languages
 * Requires at least: 6.8
 * Tested up to: 6.8
 * Requires PHP: 8.2
 * Network: false
 *
 * @package PostTemplateManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PTM_VERSION', '1.0.0');
define('PTM_PLUGIN_FILE', __FILE__);
define('PTM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PTM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PTM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check WordPress and PHP version requirements
if (version_compare(get_bloginfo('version'), '6.8', '<') || version_compare(PHP_VERSION, '8.2', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Post Template Manager requires WordPress 6.8+ and PHP 8.2+', 'post-template-manager');
        echo '</p></div>';
    });
    return;
}

// Autoloader for classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'PostTemplateManager\\') === 0) {
        $class = str_replace('PostTemplateManager\\', '', $class);
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = PTM_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Main plugin class
class PostTemplateManager
{
    private static $instance = null;
    
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->init_hooks();
    }
    
    private function init_hooks()
    {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        add_action('enqueue_block_editor_assets', [$this, 'block_editor_assets']);
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public function load_textdomain()
    {
        load_plugin_textdomain('post-template-manager', false, dirname(PTM_PLUGIN_BASENAME) . '/languages');
    }
    
    public function init()
    {
        // Initialize core classes
        new PostTemplateManager\Core\PostType();
        new PostTemplateManager\Core\Taxonomy();
        new PostTemplateManager\Admin\AdminInterface();
        new PostTemplateManager\Frontend\TemplateSelector();
        new PostTemplateManager\Core\Ajax();
    }
    
    public function admin_scripts($hook)
    {
        // Load admin scripts only on relevant pages
        if (in_array($hook, ['post.php', 'post-new.php', 'edit.php'])) {
            wp_enqueue_script(
                'ptm-admin',
                PTM_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery', 'wp-api', 'wp-blocks', 'wp-editor'],
                PTM_VERSION,
                true
            );
            
            wp_enqueue_style(
                'ptm-admin',
                PTM_PLUGIN_URL . 'assets/css/admin.css',
                [],
                PTM_VERSION
            );
            
            wp_localize_script('ptm-admin', 'ptmAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ptm_nonce'),
                'strings' => [
                    'selectTemplate' => __('Select Template', 'post-template-manager'),
                    'useTemplate' => __('Use Template', 'post-template-manager'),
                    'confirmUse' => __('This will replace the current content. Are you sure?', 'post-template-manager'),
                    'loading' => __('Loading...', 'post-template-manager'),
                    'error' => __('An error occurred. Please try again.', 'post-template-manager'),
                ]
            ]);
        }
    }
    
    public function block_editor_assets()
    {
        // Enqueue block editor assets
        wp_enqueue_script(
            'ptm-block-editor',
            PTM_PLUGIN_URL . 'assets/js/block-editor.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'],
            PTM_VERSION,
            true
        );
        
        wp_localize_script('ptm-block-editor', 'ptmBlockEditor', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ptm_nonce'),
        ]);
    }
    
    public function activate()
    {
        // Create database tables if needed
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        add_option('ptm_version', PTM_VERSION);
    }
    
    public function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_tables()
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for template usage statistics (optional)
        $table_name = $wpdb->prefix . 'ptm_template_usage';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            used_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_id (template_id),
            KEY post_id (post_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
PostTemplateManager::get_instance();
