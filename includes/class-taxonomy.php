<?php
/**
 * Taxonomy Class - Handles the template category taxonomy
 *
 * @package PostTemplateManager
 */

namespace PostTemplateManager\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Taxonomy
{
    public function __construct()
    {
        add_action('init', [$this, 'register_taxonomy']);
        add_action('ptm_template_category_add_form_fields', [$this, 'add_category_fields']);
        add_action('ptm_template_category_edit_form_fields', [$this, 'edit_category_fields']);
        add_action('created_ptm_template_category', [$this, 'save_category_fields']);
        add_action('edited_ptm_template_category', [$this, 'save_category_fields']);
        add_filter('manage_edit-ptm_template_category_columns', [$this, 'custom_columns']);
        add_action('manage_ptm_template_category_custom_column', [$this, 'custom_column_content'], 10, 3);
    }
    
    public function register_taxonomy()
    {
        $labels = [
            'name'              => __('Template Categories', 'post-template-manager'),
            'singular_name'     => __('Template Category', 'post-template-manager'),
            'search_items'      => __('Search Template Categories', 'post-template-manager'),
            'all_items'         => __('All Template Categories', 'post-template-manager'),
            'parent_item'       => __('Parent Template Category', 'post-template-manager'),
            'parent_item_colon' => __('Parent Template Category:', 'post-template-manager'),
            'edit_item'         => __('Edit Template Category', 'post-template-manager'),
            'update_item'       => __('Update Template Category', 'post-template-manager'),
            'add_new_item'      => __('Add New Template Category', 'post-template-manager'),
            'new_item_name'     => __('New Template Category Name', 'post-template-manager'),
            'menu_name'         => __('Categories', 'post-template-manager'),
        ];
        
        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rest_base'         => 'template-categories',
            'capabilities'      => [
                'manage_terms' => 'manage_options',
                'edit_terms'   => 'manage_options',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'manage_options',
            ],
        ];
        
        register_taxonomy('ptm_template_category', ['ptm_template'], $args);
        
        // Add default categories if they don't exist
        add_action('init', [$this, 'create_default_categories'], 20);
    }
    
    public function create_default_categories()
    {
        if (get_option('ptm_default_categories_created')) {
            return;
        }
        
        $default_categories = [
            [
                'name' => __('Job Postings', 'post-template-manager'),
                'slug' => 'job-postings',
                'description' => __('Templates for job posting announcements', 'post-template-manager'),
                'icon' => 'dashicons-businessman'
            ],
            [
                'name' => __('Procurement & Bidding', 'post-template-manager'),
                'slug' => 'procurement-bidding',
                'description' => __('Templates for procurement and bidding announcements', 'post-template-manager'),
                'icon' => 'dashicons-hammer'
            ],
            [
                'name' => __('News & Announcements', 'post-template-manager'),
                'slug' => 'news-announcements',
                'description' => __('Templates for news and general announcements', 'post-template-manager'),
                'icon' => 'dashicons-megaphone'
            ],
            [
                'name' => __('Events', 'post-template-manager'),
                'slug' => 'events',
                'description' => __('Templates for event announcements and invitations', 'post-template-manager'),
                'icon' => 'dashicons-calendar-alt'
            ],
        ];
        
        foreach ($default_categories as $category) {
            if (!term_exists($category['slug'], 'ptm_template_category')) {
                $term = wp_insert_term(
                    $category['name'],
                    'ptm_template_category',
                    [
                        'slug' => $category['slug'],
                        'description' => $category['description']
                    ]
                );
                
                if (!is_wp_error($term)) {
                    update_term_meta($term['term_id'], 'ptm_category_icon', $category['icon']);
                    update_term_meta($term['term_id'], 'ptm_category_color', '#0073aa');
                }
            }
        }
        
        update_option('ptm_default_categories_created', true);
    }
    
    public function add_category_fields()
    {
        ?>
        <div class="form-field term-ptm-icon-wrap">
            <label for="ptm_category_icon"><?php _e('Icon', 'post-template-manager'); ?></label>
            <select name="ptm_category_icon" id="ptm_category_icon">
                <option value=""><?php _e('Select an icon', 'post-template-manager'); ?></option>
                <?php $this->render_icon_options(); ?>
            </select>
            <p class="description"><?php _e('Choose an icon for this category.', 'post-template-manager'); ?></p>
        </div>
        
        <div class="form-field term-ptm-color-wrap">
            <label for="ptm_category_color"><?php _e('Color', 'post-template-manager'); ?></label>
            <input type="color" name="ptm_category_color" id="ptm_category_color" value="#0073aa">
            <p class="description"><?php _e('Choose a color for this category.', 'post-template-manager'); ?></p>
        </div>
        <?php
    }
    
    public function edit_category_fields($term)
    {
        $icon = get_term_meta($term->term_id, 'ptm_category_icon', true);
        $color = get_term_meta($term->term_id, 'ptm_category_color', true);
        
        if (empty($color)) {
            $color = '#0073aa';
        }
        ?>
        <tr class="form-field term-ptm-icon-wrap">
            <th scope="row">
                <label for="ptm_category_icon"><?php _e('Icon', 'post-template-manager'); ?></label>
            </th>
            <td>
                <select name="ptm_category_icon" id="ptm_category_icon">
                    <option value=""><?php _e('Select an icon', 'post-template-manager'); ?></option>
                    <?php $this->render_icon_options($icon); ?>
                </select>
                <p class="description"><?php _e('Choose an icon for this category.', 'post-template-manager'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-ptm-color-wrap">
            <th scope="row">
                <label for="ptm_category_color"><?php _e('Color', 'post-template-manager'); ?></label>
            </th>
            <td>
                <input type="color" name="ptm_category_color" id="ptm_category_color" value="<?php echo esc_attr($color); ?>">
                <p class="description"><?php _e('Choose a color for this category.', 'post-template-manager'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    private function render_icon_options($selected = '')
    {
        $icons = [
            'dashicons-businessman' => __('Businessman', 'post-template-manager'),
            'dashicons-hammer' => __('Hammer', 'post-template-manager'),
            'dashicons-megaphone' => __('Megaphone', 'post-template-manager'),
            'dashicons-calendar-alt' => __('Calendar', 'post-template-manager'),
            'dashicons-admin-post' => __('Post', 'post-template-manager'),
            'dashicons-admin-page' => __('Page', 'post-template-manager'),
            'dashicons-media-document' => __('Document', 'post-template-manager'),
            'dashicons-clipboard' => __('Clipboard', 'post-template-manager'),
            'dashicons-list-view' => __('List', 'post-template-manager'),
            'dashicons-portfolio' => __('Portfolio', 'post-template-manager'),
            'dashicons-book' => __('Book', 'post-template-manager'),
            'dashicons-groups' => __('Groups', 'post-template-manager'),
            'dashicons-building' => __('Building', 'post-template-manager'),
            'dashicons-awards' => __('Awards', 'post-template-manager'),
            'dashicons-star-filled' => __('Star', 'post-template-manager'),
        ];
        
        foreach ($icons as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($selected, $value, false),
                esc_html($label)
            );
        }
    }
    
    public function save_category_fields($term_id)
    {
        if (isset($_POST['ptm_category_icon'])) {
            update_term_meta($term_id, 'ptm_category_icon', sanitize_text_field($_POST['ptm_category_icon']));
        }
        
        if (isset($_POST['ptm_category_color'])) {
            $color = sanitize_hex_color($_POST['ptm_category_color']);
            if ($color) {
                update_term_meta($term_id, 'ptm_category_color', $color);
            }
        }
    }
    
    public function custom_columns($columns)
    {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'name') {
                $new_columns['icon'] = __('Icon', 'post-template-manager');
                $new_columns['color'] = __('Color', 'post-template-manager');
            }
        }
        
        return $new_columns;
    }
    
    public function custom_column_content($content, $column_name, $term_id)
    {
        switch ($column_name) {
            case 'icon':
                $icon = get_term_meta($term_id, 'ptm_category_icon', true);
                if ($icon) {
                    echo '<span class="dashicons ' . esc_attr($icon) . '"></span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'color':
                $color = get_term_meta($term_id, 'ptm_category_color', true);
                if ($color) {
                    echo '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border-radius: 3px; border: 1px solid #ddd;"></span>';
                } else {
                    echo '—';
                }
                break;
        }
        
        return $content;
    }
}
