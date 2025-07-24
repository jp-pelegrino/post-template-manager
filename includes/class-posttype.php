<?php
/**
 * Post Type Class - Handles the custom post type for templates
 *
 * @package PostTemplateManager
 */

namespace PostTemplateManager\Core;

if (!defined('ABSPATH')) {
    exit;
}

class PostType
{
    public function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
        add_filter('manage_ptm_template_posts_columns', [$this, 'custom_columns']);
        add_action('manage_ptm_template_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
    }
    
    public function register_post_type()
    {
        $labels = [
            'name'               => __('Post Templates', 'post-template-manager'),
            'singular_name'      => __('Post Template', 'post-template-manager'),
            'menu_name'          => __('Post Templates', 'post-template-manager'),
            'add_new'            => __('Add New Template', 'post-template-manager'),
            'add_new_item'       => __('Add New Post Template', 'post-template-manager'),
            'edit_item'          => __('Edit Post Template', 'post-template-manager'),
            'new_item'           => __('New Post Template', 'post-template-manager'),
            'view_item'          => __('View Post Template', 'post-template-manager'),
            'search_items'       => __('Search Post Templates', 'post-template-manager'),
            'not_found'          => __('No post templates found', 'post-template-manager'),
            'not_found_in_trash' => __('No post templates found in trash', 'post-template-manager'),
            'all_items'          => __('All Post Templates', 'post-template-manager'),
        ];
        
        $args = [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'capabilities'        => [
                'create_posts'       => 'manage_options',
                'edit_posts'         => 'manage_options',
                'edit_others_posts'  => 'manage_options',
                'publish_posts'      => 'manage_options',
                'read_private_posts' => 'manage_options',
                'delete_posts'       => 'manage_options',
                'delete_private_posts' => 'manage_options',
                'delete_published_posts' => 'manage_options',
                'delete_others_posts' => 'manage_options',
                'edit_private_posts' => 'manage_options',
                'edit_published_posts' => 'manage_options',
            ],
            'supports'            => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'custom-fields',
            ],
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-media-document',
            'show_in_rest'        => true,
            'rest_base'           => 'post-templates',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];
        
        register_post_type('ptm_template', $args);
    }
    
    public function add_meta_boxes()
    {
        add_meta_box(
            'ptm_template_settings',
            __('Template Settings', 'post-template-manager'),
            [$this, 'template_settings_callback'],
            'ptm_template',
            'side',
            'high'
        );
        
        add_meta_box(
            'ptm_template_usage',
            __('Template Usage', 'post-template-manager'),
            [$this, 'template_usage_callback'],
            'ptm_template',
            'side',
            'low'
        );
    }
    
    public function template_settings_callback($post)
    {
        wp_nonce_field('ptm_template_settings', 'ptm_template_settings_nonce');
        
        $target_post_types = get_post_meta($post->ID, '_ptm_target_post_types', true);
        $auto_apply_featured_image = get_post_meta($post->ID, '_ptm_auto_apply_featured_image', true);
        $template_description = get_post_meta($post->ID, '_ptm_template_description', true);
        
        if (!is_array($target_post_types)) {
            $target_post_types = ['post'];
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ptm_template_description"><?php _e('Description', 'post-template-manager'); ?></label>
                </th>
                <td>
                    <textarea id="ptm_template_description" name="ptm_template_description" rows="3" class="widefat"><?php echo esc_textarea($template_description); ?></textarea>
                    <p class="description"><?php _e('Brief description of this template\'s purpose.', 'post-template-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Target Post Types', 'post-template-manager'); ?></th>
                <td>
                    <?php
                    $post_types = get_post_types(['public' => true], 'objects');
                    foreach ($post_types as $post_type) {
                        if ($post_type->name === 'ptm_template') continue;
                        ?>
                        <label>
                            <input type="checkbox" name="ptm_target_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" 
                                   <?php checked(in_array($post_type->name, $target_post_types)); ?>>
                            <?php echo esc_html($post_type->label); ?>
                        </label><br>
                        <?php
                    }
                    ?>
                    <p class="description"><?php _e('Select which post types can use this template.', 'post-template-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="ptm_auto_apply_featured_image"><?php _e('Auto-apply Featured Image', 'post-template-manager'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="ptm_auto_apply_featured_image" name="ptm_auto_apply_featured_image" value="1" 
                           <?php checked($auto_apply_featured_image, '1'); ?>>
                    <p class="description"><?php _e('Automatically set the template\'s featured image when using this template.', 'post-template-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function template_usage_callback($post)
    {
        global $wpdb;
        
        $usage_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ptm_template_usage WHERE template_id = %d",
            $post->ID
        ));
        
        $recent_usage = $wpdb->get_results($wpdb->prepare(
            "SELECT p.post_title, u.used_at, us.display_name 
             FROM {$wpdb->prefix}ptm_template_usage u
             LEFT JOIN {$wpdb->posts} p ON u.post_id = p.ID
             LEFT JOIN {$wpdb->users} us ON u.user_id = us.ID
             WHERE u.template_id = %d 
             ORDER BY u.used_at DESC 
             LIMIT 5",
            $post->ID
        ));
        
        ?>
        <p><strong><?php _e('Usage Count:', 'post-template-manager'); ?></strong> <?php echo intval($usage_count); ?></p>
        
        <?php if ($recent_usage): ?>
            <h4><?php _e('Recent Usage:', 'post-template-manager'); ?></h4>
            <ul style="margin: 0;">
                <?php foreach ($recent_usage as $usage): ?>
                    <li style="margin-bottom: 5px;">
                        <strong><?php echo esc_html($usage->post_title ?: __('(No title)', 'post-template-manager')); ?></strong><br>
                        <small>
                            <?php echo esc_html($usage->display_name); ?> - 
                            <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($usage->used_at))); ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php
    }
    
    public function save_meta_boxes($post_id)
    {
        if (!isset($_POST['ptm_template_settings_nonce']) || 
            !wp_verify_nonce($_POST['ptm_template_settings_nonce'], 'ptm_template_settings')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Save template description
        if (isset($_POST['ptm_template_description'])) {
            update_post_meta($post_id, '_ptm_template_description', sanitize_textarea_field($_POST['ptm_template_description']));
        }
        
        // Save target post types
        $target_post_types = isset($_POST['ptm_target_post_types']) ? $_POST['ptm_target_post_types'] : ['post'];
        $target_post_types = array_map('sanitize_text_field', $target_post_types);
        update_post_meta($post_id, '_ptm_target_post_types', $target_post_types);
        
        // Save auto-apply featured image setting
        $auto_apply = isset($_POST['ptm_auto_apply_featured_image']) ? '1' : '0';
        update_post_meta($post_id, '_ptm_auto_apply_featured_image', $auto_apply);
    }
    
    public function custom_columns($columns)
    {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['template_category'] = __('Category', 'post-template-manager');
                $new_columns['target_post_types'] = __('Target Post Types', 'post-template-manager');
                $new_columns['usage_count'] = __('Usage Count', 'post-template-manager');
            }
        }
        
        return $new_columns;
    }
    
    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'template_category':
                $terms = get_the_terms($post_id, 'ptm_template_category');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = array_map(function($term) {
                        return $term->name;
                    }, $terms);
                    echo esc_html(implode(', ', $term_names));
                } else {
                    echo '—';
                }
                break;
                
            case 'target_post_types':
                $target_types = get_post_meta($post_id, '_ptm_target_post_types', true);
                if (is_array($target_types) && !empty($target_types)) {
                    $type_labels = [];
                    foreach ($target_types as $type) {
                        $post_type_obj = get_post_type_object($type);
                        if ($post_type_obj) {
                            $type_labels[] = $post_type_obj->label;
                        }
                    }
                    echo esc_html(implode(', ', $type_labels));
                } else {
                    echo '—';
                }
                break;
                
            case 'usage_count':
                global $wpdb;
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ptm_template_usage WHERE template_id = %d",
                    $post_id
                ));
                echo intval($count);
                break;
        }
    }
}
