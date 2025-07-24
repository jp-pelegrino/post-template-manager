/**
 * Template Selector for Post Editor
 */

(function($) {
    'use strict';
    
    class PTMTemplateSelector {
        constructor() {
            this.selectedTemplate = null;
            this.isBlockEditor = typeof wp !== 'undefined' && wp.data;
            this.init();
        }
        
        init() {
            if (this.isBlockEditor) {
                this.initBlockEditor();
            } else {
                this.initClassicEditor();
            }
        }
        
        initBlockEditor() {
            // Block editor integration is handled by block-editor.js
            // This is for additional functionality if needed
        }
        
        initClassicEditor() {
            this.bindClassicEditorEvents();
        }
        
        bindClassicEditorEvents() {
            $(document).on('click', '.ptm-template-card', this.selectTemplate.bind(this));
            $(document).on('click', '.ptm-use-template', this.useTemplate.bind(this));
            $(document).on('click', '.ptm-open-template-modal', this.openTemplateModal.bind(this));
        }
        
        selectTemplate(e) {
            const $card = $(e.currentTarget);
            const templateId = $card.data('template-id');
            
            $('.ptm-template-card').removeClass('selected');
            $card.addClass('selected');
            
            this.selectedTemplate = templateId;
            $('.ptm-use-template').prop('disabled', false);
        }
        
        async useTemplate() {
            if (!this.selectedTemplate) {
                return;
            }
            
            const postId = this.getCurrentPostId();
            if (!postId) {
                alert(ptmTemplateSelector.strings.error);
                return;
            }
            
            // Show confirmation if there's existing content
            if (this.hasExistingContent()) {
                const confirmed = confirm(ptmTemplateSelector.strings.confirmReplace);
                if (!confirmed) {
                    return;
                }
            }
            
            try {
                const response = await this.applyTemplate(this.selectedTemplate, postId);
                
                if (response.success) {
                    this.updateEditor(response.data);
                    this.showSuccessMessage();
                } else {
                    alert(response.data || ptmTemplateSelector.strings.error);
                }
            } catch (error) {
                console.error('Template application failed:', error);
                alert(ptmTemplateSelector.strings.error);
            }
        }
        
        async applyTemplate(templateId, postId) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: ptmTemplateSelector.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ptm_use_template',
                        template_id: templateId,
                        post_id: postId,
                        nonce: ptmTemplateSelector.nonce
                    },
                    success: resolve,
                    error: reject
                });
            });
        }
        
        updateEditor(data) {
            if (this.isBlockEditor) {
                // Update block editor
                const blocks = wp.blocks.parse(data.content);
                wp.data.dispatch('core/editor').resetBlocks(blocks);
                
                if (data.excerpt) {
                    wp.data.dispatch('core/editor').editPost({ excerpt: data.excerpt });
                }
                
                if (data.featured_image_id) {
                    wp.data.dispatch('core/editor').editPost({ 
                        featured_media: data.featured_image_id 
                    });
                }
            } else {
                // Update classic editor
                if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                    tinyMCE.activeEditor.setContent(data.content);
                } else {
                    $('#content').val(data.content);
                }
                
                if (data.excerpt) {
                    $('#excerpt').val(data.excerpt);
                }
                
                if (data.featured_image_id) {
                    this.setFeaturedImage(data.featured_image_id);
                }
            }
        }
        
        setFeaturedImage(imageId) {
            // Trigger the featured image setting in classic editor
            if (typeof wp !== 'undefined' && wp.media) {
                const frame = wp.media({
                    title: ptmTemplateSelector.strings.selectTemplate,
                    library: { type: 'image' },
                    multiple: false
                });
                
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('#set-post-thumbnail img').attr('src', attachment.url);
                    $('#set-post-thumbnail').show();
                    $('#remove-post-thumbnail').show();
                    $('input[name="_thumbnail_id"]').val(attachment.id);
                });
            }
        }
        
        hasExistingContent() {
            if (this.isBlockEditor) {
                const blocks = wp.data.select('core/editor').getBlocks();
                return blocks.length > 0 && blocks.some(block => 
                    block.name !== 'core/paragraph' || 
                    (block.attributes && block.attributes.content && block.attributes.content.trim())
                );
            } else {
                // Classic editor
                if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                    const content = tinyMCE.activeEditor.getContent();
                    return content.trim().length > 0;
                }
                
                const $content = $('#content');
                return $content.length && $content.val().trim().length > 0;
            }
        }
        
        getCurrentPostId() {
            if (this.isBlockEditor) {
                return wp.data.select('core/editor').getCurrentPostId();
            }
            
            return $('#post_ID').val() || $('input[name="post_ID"]').val();
        }
        
        showSuccessMessage() {
            if (this.isBlockEditor) {
                wp.data.dispatch('core/notices').createSuccessNotice(
                    ptmTemplateSelector.strings.templateApplied || 'Template applied successfully!',
                    { type: 'snackbar', isDismissible: true }
                );
            } else {
                const $message = $('<div class="notice notice-success is-dismissible"><p>' + 
                                 (ptmTemplateSelector.strings.templateApplied || 'Template applied successfully!') + 
                                 '</p></div>');
                
                $('.wrap h1').first().after($message);
                
                setTimeout(() => {
                    $message.fadeOut();
                }, 3000);
            }
        }
        
        openTemplateModal() {
            // This would open a modal to select templates
            // For now, we'll use the existing interface
            console.log('Opening template modal...');
        }
    }
    
    // Initialize when document is ready
    $(document).ready(() => {
        if (typeof ptmTemplateSelector !== 'undefined') {
            window.ptmTemplateSelectorInstance = new PTMTemplateSelector();
        }
    });
    
})(jQuery);
