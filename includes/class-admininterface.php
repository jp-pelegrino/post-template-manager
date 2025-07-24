<?php
/**
 * Admin Interface Class - Handles the admin dashboard functionality
 *
 * @package PostTemplateManager
 */

namespace PostTemplateManager\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminInterface
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_notices', [$this, 'admin_notices']);
        add_filter('post_row_actions', [$this, 'add_duplicate_link'], 10, 2);
        add_action('admin_action_ptm_duplicate_template', [$this, 'duplicate_template']);
    }
    
    public function add_admin_menu()
    {
        // Add main menu page that redirects to the post type listing
        add_menu_page(
            __('Post Templates', 'post-template-manager'),
            __('Post Templates', 'post-template-manager'),
            'manage_options',
            'ptm-templates',
            [$this, 'templates_page'],
            'dashicons-media-document',
            25
        );
        
        // Add submenu pages
        add_submenu_page(
            'ptm-templates',
            __('All Templates', 'post-template-manager'),
            __('All Templates', 'post-template-manager'),
            'manage_options',
            'ptm-templates',
            [$this, 'templates_page']
        );
        
        add_submenu_page(
            'ptm-templates',
            __('Add New Template', 'post-template-manager'),
            __('Add New', 'post-template-manager'),
            'manage_options',
            'post-new.php?post_type=ptm_template'
        );
        
        add_submenu_page(
            'ptm-templates',
            __('Template Settings', 'post-template-manager'),
            __('Settings', 'post-template-manager'),
            'manage_options',
            'ptm-settings',
            [$this, 'settings_page']
        );
        
        add_submenu_page(
            'ptm-templates',
            __('Template Usage Statistics', 'post-template-manager'),
            __('Usage Stats', 'post-template-manager'),
            'manage_options',
            'ptm-stats',
            [$this, 'stats_page']
        );
    }
    
    public function templates_page()
    {
        // Redirect to the actual post type listing page
        wp_redirect(admin_url('edit.php?post_type=ptm_template'));
        exit;
    }
    
    public function admin_init()
    {
        register_setting('ptm_settings', 'ptm_settings', [$this, 'sanitize_settings']);
        
        add_settings_section(
            'ptm_general_settings',
            __('General Settings', 'post-template-manager'),
            [$this, 'general_settings_callback'],
            'ptm_settings'
        );
        
        add_settings_field(
            'enable_for_post_types',
            __('Enable for Post Types', 'post-template-manager'),
            [$this, 'post_types_callback'],
            'ptm_settings',
            'ptm_general_settings'
        );
        
        add_settings_field(
            'default_template_category',
            __('Default Template Category', 'post-template-manager'),
            [$this, 'default_category_callback'],
            'ptm_settings',
            'ptm_general_settings'
        );
        
        add_settings_field(
            'enable_usage_tracking',
            __('Enable Usage Tracking', 'post-template-manager'),
            [$this, 'usage_tracking_callback'],
            'ptm_settings',
            'ptm_general_settings'
        );
        
        add_settings_field(
            'template_selector_position',
            __('Template Selector Position', 'post-template-manager'),
            [$this, 'selector_position_callback'],
            'ptm_settings',
            'ptm_general_settings'
        );
    }
    
    public function settings_page()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Post Template Manager Settings', 'post-template-manager'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ptm_settings');
                do_settings_sections('ptm_settings');
                submit_button();
                ?>
            </form>
            
            <div class="ptm-settings-info">
                <h2><?php _e('How to Use', 'post-template-manager'); ?></h2>
                <ol>
                    <li><?php _e('Create post templates by going to Post Templates > Add New', 'post-template-manager'); ?></li>
                    <li><?php _e('Assign categories to organize your templates', 'post-template-manager'); ?></li>
                    <li><?php _e('When creating a new post, use the "Use Template" button in the editor', 'post-template-manager'); ?></li>
                    <li><?php _e('The template content, blocks, and featured image will be applied to your new post', 'post-template-manager'); ?></li>
                </ol>
                
                <h3><?php _e('Template Features', 'post-template-manager'); ?></h3>
                <ul>
                    <li><?php _e('Full Gutenberg block support', 'post-template-manager'); ?></li>
                    <li><?php _e('Featured image copying', 'post-template-manager'); ?></li>
                    <li><?php _e('Category-based organization', 'post-template-manager'); ?></li>
                    <li><?php _e('Usage statistics tracking', 'post-template-manager'); ?></li>
                    <li><?php _e('Admin-only template management', 'post-template-manager'); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .ptm-settings-info {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .ptm-settings-info h2,
        .ptm-settings-info h3 {
            margin-top: 0;
        }
        
        .ptm-settings-info ul,
        .ptm-settings-info ol {
            margin-left: 20px;
        }
        </style>
        <?php
    }
    
    public function stats_page()
    {
        global $wpdb;
        
        // Get usage statistics
        $total_templates = wp_count_posts('ptm_template')->publish;
        $total_usage = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptm_template_usage");
        
        $most_used_templates = $wpdb->get_results("
            SELECT t.post_title, COUNT(u.id) as usage_count, t.ID
            FROM {$wpdb->prefix}ptm_template_usage u
            LEFT JOIN {$wpdb->posts} t ON u.template_id = t.ID
            WHERE t.post_status = 'publish'
            GROUP BY u.template_id
            ORDER BY usage_count DESC
            LIMIT 10
        ");
        
        $recent_usage = $wpdb->get_results("
            SELECT t.post_title as template_title, p.post_title as post_title, 
                   u.used_at, us.display_name
            FROM {$wpdb->prefix}ptm_template_usage u
            LEFT JOIN {$wpdb->posts} t ON u.template_id = t.ID
            LEFT JOIN {$wpdb->posts} p ON u.post_id = p.ID
            LEFT JOIN {$wpdb->users} us ON u.user_id = us.ID
            ORDER BY u.used_at DESC
            LIMIT 20
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Template Usage Statistics', 'post-template-manager'); ?></h1>
            
            <div class="ptm-stats-summary">
                <div class="ptm-stat-box">
                    <h3><?php _e('Total Templates', 'post-template-manager'); ?></h3>
                    <p class="ptm-stat-number"><?php echo intval($total_templates); ?></p>
                </div>
                
                <div class="ptm-stat-box">
                    <h3><?php _e('Total Usage', 'post-template-manager'); ?></h3>
                    <p class="ptm-stat-number"><?php echo intval($total_usage); ?></p>
                </div>
                
                <div class="ptm-stat-box">
                    <h3><?php _e('Average Usage', 'post-template-manager'); ?></h3>
                    <p class="ptm-stat-number">
                        <?php echo $total_templates > 0 ? round($total_usage / $total_templates, 1) : 0; ?>
                    </p>
                </div>
            </div>
            
            <div class="ptm-stats-tables">
                <div class="ptm-stats-column">
                    <h2><?php _e('Most Used Templates', 'post-template-manager'); ?></h2>
                    <?php if ($most_used_templates): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Template', 'post-template-manager'); ?></th>
                                    <th><?php _e('Usage Count', 'post-template-manager'); ?></th>
                                    <th><?php _e('Actions', 'post-template-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($most_used_templates as $template): ?>
                                    <tr>
                                        <td><?php echo esc_html($template->post_title); ?></td>
                                        <td><?php echo intval($template->usage_count); ?></td>
                                        <td>
                                            <a href="<?php echo get_edit_post_link($template->ID); ?>" class="button button-small">
                                                <?php _e('Edit', 'post-template-manager'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php _e('No template usage data available.', 'post-template-manager'); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="ptm-stats-column">
                    <h2><?php _e('Recent Usage', 'post-template-manager'); ?></h2>
                    <?php if ($recent_usage): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Template', 'post-template-manager'); ?></th>
                                    <th><?php _e('Post', 'post-template-manager'); ?></th>
                                    <th><?php _e('User', 'post-template-manager'); ?></th>
                                    <th><?php _e('Date', 'post-template-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_usage as $usage): ?>
                                    <tr>
                                        <td><?php echo esc_html($usage->template_title); ?></td>
                                        <td><?php echo esc_html($usage->post_title ?: __('(No title)', 'post-template-manager')); ?></td>
                                        <td><?php echo esc_html($usage->display_name); ?></td>
                                        <td><?php echo esc_html(wp_date(get_option('date_format'), strtotime($usage->used_at))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php _e('No recent usage data available.', 'post-template-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .ptm-stats-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .ptm-stat-box {
            flex: 1;
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        
        .ptm-stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .ptm-stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            margin: 0;
        }
        
        .ptm-stats-tables {
            display: flex;
            gap: 20px;
        }
        
        .ptm-stats-column {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .ptm-stats-summary,
            .ptm-stats-tables {
                flex-direction: column;
            }
        }
        </style>
        <?php
    }
    
    public function general_settings_callback()
    {
        echo '<p>' . __('Configure the general settings for Post Template Manager.', 'post-template-manager') . '</p>';
    }
    
    public function post_types_callback()
    {
        $options = get_option('ptm_settings', []);
        $enabled_post_types = isset($options['enable_for_post_types']) ? $options['enable_for_post_types'] : ['post'];
        
        $post_types = get_post_types(['public' => true], 'objects');
        
        foreach ($post_types as $post_type) {
            if ($post_type->name === 'ptm_template') continue;
            
            $checked = in_array($post_type->name, $enabled_post_types);
            ?>
            <label>
                <input type="checkbox" name="ptm_settings[enable_for_post_types][]" 
                       value="<?php echo esc_attr($post_type->name); ?>" <?php checked($checked); ?>>
                <?php echo esc_html($post_type->label); ?>
            </label><br>
            <?php
        }
        
        echo '<p class="description">' . __('Select which post types can use templates.', 'post-template-manager') . '</p>';
    }
    
    public function default_category_callback()
    {
        $options = get_option('ptm_settings', []);
        $default_category = isset($options['default_template_category']) ? $options['default_template_category'] : '';
        
        $categories = get_terms([
            'taxonomy' => 'ptm_template_category',
            'hide_empty' => false,
        ]);
        
        ?>
        <select name="ptm_settings[default_template_category]">
            <option value=""><?php _e('No default category', 'post-template-manager'); ?></option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo esc_attr($category->term_id); ?>" 
                        <?php selected($default_category, $category->term_id); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Default category for new templates.', 'post-template-manager'); ?></p>
        <?php
    }
    
    public function usage_tracking_callback()
    {
        $options = get_option('ptm_settings', []);
        $enabled = isset($options['enable_usage_tracking']) ? $options['enable_usage_tracking'] : true;
        
        ?>
        <label>
            <input type="checkbox" name="ptm_settings[enable_usage_tracking]" value="1" <?php checked($enabled); ?>>
            <?php _e('Track template usage statistics', 'post-template-manager'); ?>
        </label>
        <p class="description"><?php _e('Enable tracking of when and how often templates are used.', 'post-template-manager'); ?></p>
        <?php
    }
    
    public function selector_position_callback()
    {
        $options = get_option('ptm_settings', []);
        $position = isset($options['template_selector_position']) ? $options['template_selector_position'] : 'after_title';
        
        $positions = [
            'after_title' => __('After Title', 'post-template-manager'),
            'sidebar' => __('Sidebar', 'post-template-manager'),
            'above_editor' => __('Above Editor', 'post-template-manager'),
        ];
        
        ?>
        <select name="ptm_settings[template_selector_position]">
            <?php foreach ($positions as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($position, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Choose where the template selector appears in the post editor.', 'post-template-manager'); ?></p>
        <?php
    }
    
    public function sanitize_settings($input)
    {
        $sanitized = [];
        
        if (isset($input['enable_for_post_types']) && is_array($input['enable_for_post_types'])) {
            $sanitized['enable_for_post_types'] = array_map('sanitize_text_field', $input['enable_for_post_types']);
        } else {
            $sanitized['enable_for_post_types'] = ['post'];
        }
        
        if (isset($input['default_template_category'])) {
            $sanitized['default_template_category'] = intval($input['default_template_category']);
        }
        
        $sanitized['enable_usage_tracking'] = isset($input['enable_usage_tracking']) ? true : false;
        
        if (isset($input['template_selector_position'])) {
            $allowed_positions = ['after_title', 'sidebar', 'above_editor'];
            $sanitized['template_selector_position'] = in_array($input['template_selector_position'], $allowed_positions) 
                ? $input['template_selector_position'] : 'after_title';
        }
        
        return $sanitized;
    }
    
    public function admin_notices()
    {
        if (isset($_GET['ptm_duplicated'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Template duplicated successfully.', 'post-template-manager'); ?></p>
            </div>
            <?php
        }
    }
    
    public function add_duplicate_link($actions, $post)
    {
        if ($post->post_type === 'ptm_template' && current_user_can('manage_options')) {
            $actions['ptm_duplicate'] = sprintf(
                '<a href="%s">%s</a>',
                wp_nonce_url(admin_url('admin.php?action=ptm_duplicate_template&post=' . $post->ID), 'ptm_duplicate_template'),
                __('Duplicate', 'post-template-manager')
            );
        }
        
        return $actions;
    }
    
    public function duplicate_template()
    {
        if (!isset($_GET['post']) || !wp_verify_nonce($_GET['_wpnonce'], 'ptm_duplicate_template')) {
            wp_die(__('Security check failed.', 'post-template-manager'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to duplicate templates.', 'post-template-manager'));
        }
        
        $original_id = intval($_GET['post']);
        $original = get_post($original_id);
        
        if (!$original || $original->post_type !== 'ptm_template') {
            wp_die(__('Template not found.', 'post-template-manager'));
        }
        
        // Create duplicate
        $duplicate_args = [
            'post_title' => $original->post_title . ' ' . __('(Copy)', 'post-template-manager'),
            'post_content' => $original->post_content,
            'post_excerpt' => $original->post_excerpt,
            'post_status' => 'draft',
            'post_type' => 'ptm_template',
            'post_author' => get_current_user_id(),
        ];
        
        $duplicate_id = wp_insert_post($duplicate_args);
        
        if ($duplicate_id) {
            // Copy meta data
            $meta_keys = get_post_meta($original_id);
            foreach ($meta_keys as $key => $values) {
                foreach ($values as $value) {
                    update_post_meta($duplicate_id, $key, maybe_unserialize($value));
                }
            }
            
            // Copy taxonomies
            $taxonomies = get_object_taxonomies('ptm_template');
            foreach ($taxonomies as $taxonomy) {
                $terms = get_the_terms($original_id, $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    $term_ids = array_map(function($term) {
                        return $term->term_id;
                    }, $terms);
                    wp_set_object_terms($duplicate_id, $term_ids, $taxonomy);
                }
            }
            
            // Copy featured image
            if (has_post_thumbnail($original_id)) {
                set_post_thumbnail($duplicate_id, get_post_thumbnail_id($original_id));
            }
            
            wp_redirect(admin_url('edit.php?post_type=ptm_template&ptm_duplicated=1'));
        } else {
            wp_die(__('Failed to duplicate template.', 'post-template-manager'));
        }
        
        exit;
    }
}
