/**
 * Block Editor Integration for Post Template Manager
 */

(function() {
    'use strict';
    
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { PanelBody, Button, Modal, SelectControl } = wp.components;
    const { useState, useEffect } = wp.element;
    const { useSelect, useDispatch } = wp.data;
    const { __ } = wp.i18n;
    
    // Template Selector Component
    const TemplateSelector = () => {
        const [isModalOpen, setIsModalOpen] = useState(false);
        const [templates, setTemplates] = useState([]);
        const [categories, setCategories] = useState([]);
        const [selectedCategory, setSelectedCategory] = useState('all');
        const [selectedTemplate, setSelectedTemplate] = useState(null);
        const [isLoading, setIsLoading] = useState(false);
        
        const postType = useSelect(select => 
            select('core/editor').getCurrentPostType()
        );
        
        const { editPost, resetBlocks } = useDispatch('core/editor');
        
        // Load templates when modal opens
        useEffect(() => {
            if (isModalOpen) {
                loadTemplates();
                loadCategories();
            }
        }, [isModalOpen, selectedCategory]);
        
        const loadTemplates = async () => {
            setIsLoading(true);
            
            try {
                const response = await wp.apiFetch({
                    path: '/wp/v2/ptm_template',
                    data: {
                        post_type: postType,
                        category: selectedCategory !== 'all' ? selectedCategory : '',
                        per_page: 100,
                        status: 'publish'
                    }
                });
                
                setTemplates(response || []);
            } catch (error) {
                console.error('Failed to load templates:', error);
                setTemplates([]);
            }
            
            setIsLoading(false);
        };
        
        const loadCategories = async () => {
            try {
                const response = await wp.apiFetch({
                    path: '/wp/v2/template-categories'
                });
                
                setCategories(response || []);
            } catch (error) {
                console.error('Failed to load categories:', error);
                setCategories([]);
            }
        };
        
        const useTemplate = async () => {
            if (!selectedTemplate) return;
            
            setIsLoading(true);
            
            try {
                // Get template content
                const template = await wp.apiFetch({
                    path: `/wp/v2/ptm_template/${selectedTemplate}`
                });
                
                // Parse blocks from template content
                const blocks = wp.blocks.parse(template.content.rendered || template.content.raw);
                
                // Reset editor with template blocks
                resetBlocks(blocks);
                
                // Set excerpt if available
                if (template.excerpt && template.excerpt.raw) {
                    editPost({ excerpt: template.excerpt.raw });
                }
                
                // Set featured image if available and auto-apply is enabled
                const autoApplyImage = template.meta && template.meta._ptm_auto_apply_featured_image;
                if (autoApplyImage && template.featured_media) {
                    editPost({ featured_media: template.featured_media });
                }
                
                // Track usage
                trackTemplateUsage(selectedTemplate);
                
                // Close modal
                setIsModalOpen(false);
                setSelectedTemplate(null);
                
                // Show success notice
                wp.data.dispatch('core/notices').createSuccessNotice(
                    __('Template applied successfully!', 'post-template-manager'),
                    { type: 'snackbar' }
                );
                
            } catch (error) {
                console.error('Failed to apply template:', error);
                wp.data.dispatch('core/notices').createErrorNotice(
                    __('Failed to apply template. Please try again.', 'post-template-manager'),
                    { type: 'snackbar' }
                );
            }
            
            setIsLoading(false);
        };
        
        const trackTemplateUsage = async (templateId) => {
            const postId = useSelect(select => 
                select('core/editor').getCurrentPostId()
            );
            
            try {
                await jQuery.ajax({
                    url: ptmBlockEditor.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ptm_use_template',
                        template_id: templateId,
                        post_id: postId,
                        nonce: ptmBlockEditor.nonce
                    }
                });
            } catch (error) {
                console.error('Failed to track template usage:', error);
            }
        };
        
        const categoryOptions = [
            { value: 'all', label: __('All Categories', 'post-template-manager') },
            ...categories.map(cat => ({ 
                value: cat.id.toString(), 
                label: cat.name 
            }))
        ];
        
        return (
            <>
                <Button 
                    variant="primary" 
                    onClick={() => setIsModalOpen(true)}
                    style={{ width: '100%' }}
                >
                    {__('Use Template', 'post-template-manager')}
                </Button>
                
                {isModalOpen && (
                    <Modal
                        title={__('Select Post Template', 'post-template-manager')}
                        onRequestClose={() => setIsModalOpen(false)}
                        style={{ maxWidth: '800px' }}
                    >
                        <div style={{ marginBottom: '20px' }}>
                            <SelectControl
                                label={__('Filter by Category', 'post-template-manager')}
                                value={selectedCategory}
                                options={categoryOptions}
                                onChange={setSelectedCategory}
                            />
                        </div>
                        
                        {isLoading ? (
                            <div style={{ textAlign: 'center', padding: '40px' }}>
                                {__('Loading templates...', 'post-template-manager')}
                            </div>
                        ) : (
                            <>
                                {templates.length === 0 ? (
                                    <div style={{ textAlign: 'center', padding: '40px' }}>
                                        {__('No templates available for this post type.', 'post-template-manager')}
                                    </div>
                                ) : (
                                    <div style={{ 
                                        display: 'grid', 
                                        gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))', 
                                        gap: '15px',
                                        marginBottom: '20px'
                                    }}>
                                        {templates.map(template => (
                                            <TemplateCard
                                                key={template.id}
                                                template={template}
                                                isSelected={selectedTemplate === template.id}
                                                onSelect={() => setSelectedTemplate(template.id)}
                                            />
                                        ))}
                                    </div>
                                )}
                                
                                <div style={{ 
                                    display: 'flex', 
                                    justifyContent: 'flex-end', 
                                    gap: '10px',
                                    borderTop: '1px solid #ddd',
                                    paddingTop: '15px'
                                }}>
                                    <Button 
                                        variant="tertiary"
                                        onClick={() => setIsModalOpen(false)}
                                    >
                                        {__('Cancel', 'post-template-manager')}
                                    </Button>
                                    
                                    <Button 
                                        variant="primary"
                                        disabled={!selectedTemplate || isLoading}
                                        onClick={useTemplate}
                                    >
                                        {isLoading ? 
                                            __('Applying...', 'post-template-manager') : 
                                            __('Use Template', 'post-template-manager')
                                        }
                                    </Button>
                                </div>
                            </>
                        )}
                    </Modal>
                )}
            </>
        );
    };
    
    // Template Card Component
    const TemplateCard = ({ template, isSelected, onSelect }) => {
        const cardStyle = {
            border: isSelected ? '2px solid #0073aa' : '2px solid #ddd',
            borderRadius: '5px',
            padding: '15px',
            cursor: 'pointer',
            backgroundColor: isSelected ? '#f0f8ff' : '#fff',
            transition: 'all 0.3s ease'
        };
        
        const thumbnailStyle = {
            width: '100%',
            height: '80px',
            backgroundColor: '#f5f5f5',
            borderRadius: '3px',
            marginBottom: '10px',
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundImage: template.featured_media_url ? 
                `url(${template.featured_media_url})` : 'none'
        };
        
        return (
            <div style={cardStyle} onClick={onSelect}>
                <div style={thumbnailStyle}></div>
                
                <h4 style={{ margin: '0 0 8px 0', fontSize: '14px' }}>
                    {template.title.rendered}
                </h4>
                
                {template.excerpt.rendered && (
                    <div style={{ 
                        fontSize: '12px', 
                        color: '#666', 
                        lineHeight: '1.4'
                    }}
                    dangerouslySetInnerHTML={{ 
                        __html: wp.sanitize.strip(template.excerpt.rendered)
                    }}
                    />
                )}
            </div>
        );
    };
    
    // Plugin Sidebar Panel
    const TemplateManagerPanel = () => {
        const postType = useSelect(select => 
            select('core/editor').getCurrentPostType()
        );
        
        // Check if templates are enabled for this post type
        const isEnabled = ptmBlockEditor.enabledPostTypes?.includes(postType);
        
        if (!isEnabled) {
            return null;
        }
        
        return (
            <PluginDocumentSettingPanel
                name="post-template-manager"
                title={__('Post Templates', 'post-template-manager')}
                icon="media-document"
            >
                <PanelBody>
                    <p style={{ marginBottom: '15px' }}>
                        {__('Choose a template to quickly populate this post with predefined content.', 'post-template-manager')}
                    </p>
                    
                    <TemplateSelector />
                </PanelBody>
            </PluginDocumentSettingPanel>
        );
    };
    
    // Register the plugin
    registerPlugin('post-template-manager', {
        render: TemplateManagerPanel,
        icon: 'media-document',
    });
    
})();
