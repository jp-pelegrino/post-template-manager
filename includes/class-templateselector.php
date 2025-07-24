<?php
/**
 * Template Selector Class - Handles the template selection interface in post editor
 *
 * @package PostTemplateManager
 */

namespace PostTemplateManager\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class TemplateSelector
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_template_selector_meta_box']);
        add_action('admin_footer', [$this, 'template_selector_modal']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
    }
    
    public function add_template_selector_meta_box()
    {
        $options = get_option('ptm_settings', []);
        $enabled_post_types = isset($options['enable_for_post_types']) ? $options['enable_for_post_types'] : ['post'];
        $position = isset($options['template_selector_position']) ? $options['template_selector_position'] : 'after_title';
        
        foreach ($enabled_post_types as $post_type) {
            if ($post_type === 'ptm_template') continue;
            
            // Skip if current user can't edit this post type
            $post_type_obj = get_post_type_object($post_type);
            if (!$post_type_obj || !current_user_can($post_type_obj->cap->edit_posts)) {
                continue;
            }
            
            $context = 'side';
            $priority = 'high';
            
            if ($position === 'above_editor') {
                $context = 'normal';
                $priority = 'high';
            } elseif ($position === 'after_title') {
                $context = 'normal';
                $priority = 'high';
            }
            
            add_meta_box(
                'ptm_template_selector',
                __('Use Post Template', 'post-template-manager'),
                [$this, 'template_selector_callback'],
                $post_type,
                $context,
                $priority
            );
        }
    }
    
    public function template_selector_callback($post)
    {
        // Get available templates for this post type
        $templates = $this->get_available_templates($post->post_type);
        
        if (empty($templates)) {
            ?>
            <p><?php _e('No templates available for this post type.', 'post-template-manager'); ?></p>
            <p>
                <a href="<?php echo admin_url('post-new.php?post_type=ptm_template'); ?>" class="button">
                    <?php _e('Create a Template', 'post-template-manager'); ?>
                </a>
            </p>
            <?php
            return;
        }
        
        ?>
        <div id="ptm-template-selector">
            <p><?php _e('Choose a template to quickly populate this post with predefined content:', 'post-template-manager'); ?></p>
            
            <div class="ptm-template-grid">
                <?php foreach ($templates as $template): ?>
                    <?php $this->render_template_card($template); ?>
                <?php endforeach; ?>
            </div>
            
            <div class="ptm-template-actions">
                <button type="button" class="button button-primary ptm-use-template" disabled>
                    <?php _e('Use Selected Template', 'post-template-manager'); ?>
                </button>
                <span class="ptm-template-warning" style="display: none;">
                    <?php _e('This will replace the current content. Are you sure?', 'post-template-manager'); ?>
                    <button type="button" class="button ptm-confirm-use"><?php _e('Yes, Use Template', 'post-template-manager'); ?></button>
                    <button type="button" class="button ptm-cancel-use"><?php _e('Cancel', 'post-template-manager'); ?></button>
                </span>
            </div>
        </div>
        
        <style>
        .ptm-template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .ptm-template-card {
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .ptm-template-card:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .ptm-template-card.selected {
            border-color: #0073aa;
            background: #f0f8ff;
        }
        
        .ptm-template-card h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #333;
        }
        
        .ptm-template-card .ptm-template-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .ptm-template-card .ptm-template-description {
            font-size: 12px;
            color: #888;
            line-height: 1.4;
        }
        
        .ptm-template-card .ptm-template-thumbnail {
            width: 100%;
            height: 80px;
            background: #f5f5f5;
            border-radius: 3px;
            margin-bottom: 8px;
            background-size: cover;
            background-position: center;
        }
        
        .ptm-template-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .ptm-template-warning {
            display: inline-block;
            margin-left: 10px;
            color: #d63638;
            font-size: 12px;
        }
        
        @media (max-width: 782px) {
            .ptm-template-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    private function get_available_templates($post_type)
    {
        $args = [
            'post_type' => 'ptm_template',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_ptm_target_post_types',
                    'value' => $post_type,
                    'compare' => 'LIKE'
                ]
            ],
            'orderby' => 'menu_order title',
            'order' => 'ASC'
        ];
        
        return get_posts($args);
    }
    
    private function render_template_card($template)
    {
        $description = get_post_meta($template->ID, '_ptm_template_description', true);
        $categories = get_the_terms($template->ID, 'ptm_template_category');
        $thumbnail_id = get_post_thumbnail_id($template->ID);
        
        ?>
        <div class="ptm-template-card" data-template-id="<?php echo esc_attr($template->ID); ?>">
            <?php if ($thumbnail_id): ?>
                <div class="ptm-template-thumbnail" 
                     style="background-image: url('<?php echo esc_url(wp_get_attachment_image_url($thumbnail_id, 'medium')); ?>');">
                </div>
            <?php endif; ?>
            
            <h4><?php echo esc_html($template->post_title); ?></h4>
            
            <div class="ptm-template-meta">
                <?php if ($categories && !is_wp_error($categories)): ?>
                    <span class="ptm-template-category">
                        <?php
                        $category_names = array_map(function($cat) {
                            return $cat->name;
                        }, $categories);
                        echo esc_html(implode(', ', $category_names));
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if ($description): ?>
                <div class="ptm-template-description">
                    <?php echo esc_html(wp_trim_words($description, 15)); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function template_selector_modal()
    {
        global $current_screen;
        
        if (!$current_screen || !in_array($current_screen->base, ['post', 'page'])) {
            return;
        }
        
        $options = get_option('ptm_settings', []);
        $enabled_post_types = isset($options['enable_for_post_types']) ? $options['enable_for_post_types'] : ['post'];
        
        if (!in_array($current_screen->post_type, $enabled_post_types)) {
            return;
        }
        
        ?>
        <div id="ptm-template-modal" style="display: none;">
            <div class="ptm-modal-overlay"></div>
            <div class="ptm-modal-content">
                <div class="ptm-modal-header">
                    <h2><?php _e('Select Template', 'post-template-manager'); ?></h2>
                    <button type="button" class="ptm-modal-close">&times;</button>
                </div>
                
                <div class="ptm-modal-body">
                    <div class="ptm-template-categories">
                        <button type="button" class="ptm-category-filter active" data-category="all">
                            <?php _e('All Templates', 'post-template-manager'); ?>
                        </button>
                        <?php
                        $categories = get_terms([
                            'taxonomy' => 'ptm_template_category',
                            'hide_empty' => true,
                        ]);
                        
                        foreach ($categories as $category):
                            $icon = get_term_meta($category->term_id, 'ptm_category_icon', true);
                            $color = get_term_meta($category->term_id, 'ptm_category_color', true);
                        ?>
                            <button type="button" class="ptm-category-filter" 
                                    data-category="<?php echo esc_attr($category->term_id); ?>"
                                    style="<?php echo $color ? 'border-color: ' . esc_attr($color) : ''; ?>">
                                <?php if ($icon): ?>
                                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                                <?php endif; ?>
                                <?php echo esc_html($category->name); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <div id="ptm-modal-templates"></div>
                </div>
                
                <div class="ptm-modal-footer">
                    <button type="button" class="button button-primary ptm-modal-use-template" disabled>
                        <?php _e('Use Template', 'post-template-manager'); ?>
                    </button>
                    <button type="button" class="button ptm-modal-cancel">
                        <?php _e('Cancel', 'post-template-manager'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        #ptm-template-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100000;
        }
        
        .ptm-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .ptm-modal-content {
            position: relative;
            background: #fff;
            width: 90%;
            max-width: 900px;
            height: 80%;
            margin: 5% auto;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
        }
        
        .ptm-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ptm-modal-header h2 {
            margin: 0;
        }
        
        .ptm-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .ptm-modal-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .ptm-template-categories {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .ptm-category-filter {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #fff;
            cursor: pointer;
            border-radius: 3px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .ptm-category-filter.active {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        
        .ptm-category-filter:hover {
            border-color: #0073aa;
        }
        
        .ptm-modal-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        </style>
        <?php
    }
    
    public function enqueue_block_editor_assets()
    {
        global $current_screen;
        
        if (!$current_screen) {
            return;
        }
        
        $options = get_option('ptm_settings', []);
        $enabled_post_types = isset($options['enable_for_post_types']) ? $options['enable_for_post_types'] : ['post'];
        
        if (!in_array($current_screen->post_type, $enabled_post_types)) {
            return;
        }
        
        wp_enqueue_script(
            'ptm-template-selector',
            PTM_PLUGIN_URL . 'assets/js/template-selector.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-api-fetch'],
            PTM_VERSION,
            true
        );
        
        wp_localize_script('ptm-template-selector', 'ptmTemplateSelector', [
            'postType' => $current_screen->post_type,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ptm_nonce'),
            'strings' => [
                'useTemplate' => __('Use Template', 'post-template-manager'),
                'selectTemplate' => __('Select Template', 'post-template-manager'),
                'confirmReplace' => __('This will replace the current content. Are you sure?', 'post-template-manager'),
                'loading' => __('Loading...', 'post-template-manager'),
                'error' => __('An error occurred. Please try again.', 'post-template-manager'),
                'noTemplates' => __('No templates available for this post type.', 'post-template-manager'),
            ]
        ]);
    }
}
