<?php
/**
 * Ajax Class - Handles AJAX requests for template operations
 *
 * @package PostTemplateManager
 */

namespace PostTemplateManager\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_ptm_get_templates', [$this, 'get_templates']);
        add_action('wp_ajax_ptm_use_template', [$this, 'use_template']);
        add_action('wp_ajax_ptm_get_template_content', [$this, 'get_template_content']);
        add_action('wp_ajax_ptm_search_templates', [$this, 'search_templates']);
    }
    
    public function get_templates()
    {
        check_ajax_referer('ptm_nonce', 'nonce');
        
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied.', 'post-template-manager'));
        }
        
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
        
        // Add category filter if specified
        if (!empty($category) && $category !== 'all') {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'ptm_template_category',
                    'field' => 'term_id',
                    'terms' => intval($category)
                ]
            ];
        }
        
        $templates = get_posts($args);
        $template_data = [];
        
        foreach ($templates as $template) {
            $description = get_post_meta($template->ID, '_ptm_template_description', true);
            $categories = get_the_terms($template->ID, 'ptm_template_category');
            $thumbnail_id = get_post_thumbnail_id($template->ID);
            
            $category_data = [];
            if ($categories && !is_wp_error($categories)) {
                foreach ($categories as $cat) {
                    $category_data[] = [
                        'id' => $cat->term_id,
                        'name' => $cat->name,
                        'icon' => get_term_meta($cat->term_id, 'ptm_category_icon', true),
                        'color' => get_term_meta($cat->term_id, 'ptm_category_color', true),
                    ];
                }
            }
            
            $template_data[] = [
                'id' => $template->ID,
                'title' => $template->post_title,
                'description' => $description,
                'categories' => $category_data,
                'thumbnail' => $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '',
                'excerpt' => $template->post_excerpt,
                'modified' => get_the_modified_time('Y-m-d H:i:s', $template->ID),
            ];
        }
        
        wp_send_json_success($template_data);
    }
    
    public function use_template()
    {
        check_ajax_referer('ptm_nonce', 'nonce');
        
        $template_id = intval($_POST['template_id'] ?? 0);
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('Permission denied.', 'post-template-manager'));
        }
        
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'ptm_template') {
            wp_send_json_error(__('Template not found.', 'post-template-manager'));
        }
        
        $target_post = get_post($post_id);
        if (!$target_post) {
            wp_send_json_error(__('Post not found.', 'post-template-manager'));
        }
        
        // Check if this template supports the target post type
        $target_post_types = get_post_meta($template_id, '_ptm_target_post_types', true);
        if (!is_array($target_post_types) || !in_array($target_post->post_type, $target_post_types)) {
            wp_send_json_error(__('This template is not compatible with this post type.', 'post-template-manager'));
        }
        
        try {
            // Update post content
            $update_data = [
                'ID' => $post_id,
                'post_content' => $template->post_content,
            ];
            
            // Also copy excerpt if it exists
            if (!empty($template->post_excerpt)) {
                $update_data['post_excerpt'] = $template->post_excerpt;
            }
            
            $result = wp_update_post($update_data, true);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            // Copy featured image if setting is enabled
            $auto_apply_featured_image = get_post_meta($template_id, '_ptm_auto_apply_featured_image', true);
            if ($auto_apply_featured_image && has_post_thumbnail($template_id)) {
                set_post_thumbnail($post_id, get_post_thumbnail_id($template_id));
            }
            
            // Copy any custom fields that should be templated
            $this->copy_template_meta($template_id, $post_id);
            
            // Track usage if enabled
            $this->track_template_usage($template_id, $post_id);
            
            // Get the updated content for the response
            $updated_post = get_post($post_id);
            
            wp_send_json_success([
                'message' => __('Template applied successfully.', 'post-template-manager'),
                'content' => $updated_post->post_content,
                'excerpt' => $updated_post->post_excerpt,
                'featured_image_id' => get_post_thumbnail_id($post_id),
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to apply template: ', 'post-template-manager') . $e->getMessage());
        }
    }
    
    public function get_template_content()
    {
        check_ajax_referer('ptm_nonce', 'nonce');
        
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied.', 'post-template-manager'));
        }
        
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'ptm_template') {
            wp_send_json_error(__('Template not found.', 'post-template-manager'));
        }
        
        // Parse blocks for block editor
        $blocks = parse_blocks($template->post_content);
        $rendered_content = '';
        
        foreach ($blocks as $block) {
            $rendered_content .= render_block($block);
        }
        
        $response_data = [
            'id' => $template->ID,
            'title' => $template->post_title,
            'content' => $template->post_content,
            'rendered_content' => $rendered_content,
            'excerpt' => $template->post_excerpt,
            'blocks' => $blocks,
            'featured_image_id' => get_post_thumbnail_id($template_id),
            'meta' => [
                'description' => get_post_meta($template_id, '_ptm_template_description', true),
                'auto_apply_featured_image' => get_post_meta($template_id, '_ptm_auto_apply_featured_image', true),
            ]
        ];
        
        wp_send_json_success($response_data);
    }
    
    public function search_templates()
    {
        check_ajax_referer('ptm_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied.', 'post-template-manager'));
        }
        
        if (empty($search_term)) {
            wp_send_json_error(__('Search term is required.', 'post-template-manager'));
        }
        
        $args = [
            'post_type' => 'ptm_template',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $search_term,
            'meta_query' => [
                [
                    'key' => '_ptm_target_post_types',
                    'value' => $post_type,
                    'compare' => 'LIKE'
                ]
            ],
            'orderby' => 'relevance',
            'order' => 'DESC'
        ];
        
        $templates = get_posts($args);
        $template_data = [];
        
        foreach ($templates as $template) {
            $description = get_post_meta($template->ID, '_ptm_template_description', true);
            $categories = get_the_terms($template->ID, 'ptm_template_category');
            
            $category_names = [];
            if ($categories && !is_wp_error($categories)) {
                $category_names = array_map(function($cat) {
                    return $cat->name;
                }, $categories);
            }
            
            $template_data[] = [
                'id' => $template->ID,
                'title' => $template->post_title,
                'description' => $description,
                'categories' => $category_names,
                'thumbnail' => get_the_post_thumbnail_url($template->ID, 'medium'),
                'excerpt' => wp_trim_words($template->post_content, 20),
            ];
        }
        
        wp_send_json_success($template_data);
    }
    
    private function copy_template_meta($template_id, $post_id)
    {
        // Define which meta fields should be copied from template to post
        $copyable_meta_keys = apply_filters('ptm_copyable_meta_keys', [
            // Add any custom fields that should be copied
            // '_custom_field_name',
        ]);
        
        foreach ($copyable_meta_keys as $meta_key) {
            $meta_value = get_post_meta($template_id, $meta_key, true);
            if (!empty($meta_value)) {
                update_post_meta($post_id, $meta_key, $meta_value);
            }
        }
    }
    
    private function track_template_usage($template_id, $post_id)
    {
        $options = get_option('ptm_settings', []);
        $track_usage = isset($options['enable_usage_tracking']) ? $options['enable_usage_tracking'] : true;
        
        if (!$track_usage) {
            return;
        }
        
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'ptm_template_usage',
            [
                'template_id' => $template_id,
                'post_id' => $post_id,
                'user_id' => get_current_user_id(),
                'used_at' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s']
        );
        
        // Also update a simple counter for quick access
        $usage_count = get_post_meta($template_id, '_ptm_usage_count', true);
        $usage_count = intval($usage_count) + 1;
        update_post_meta($template_id, '_ptm_usage_count', $usage_count);
        update_post_meta($template_id, '_ptm_last_used', current_time('mysql'));
    }
}
