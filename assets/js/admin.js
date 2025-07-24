/**
 * Admin JavaScript for Post Template Manager
 */

(function($) {
    'use strict';
    
    // Template selector functionality
    class PTMTemplateSelector {
        constructor() {
            this.selectedTemplate = null;
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initTemplateCards();
        }
        
        bindEvents() {
            $(document).on('click', '.ptm-template-card', this.selectTemplate.bind(this));
            $(document).on('click', '.ptm-use-template', this.showConfirmation.bind(this));
            $(document).on('click', '.ptm-confirm-use', this.useTemplate.bind(this));
            $(document).on('click', '.ptm-cancel-use', this.hideConfirmation.bind(this));
            
            // Modal events
            $(document).on('click', '.ptm-open-template-modal', this.openModal.bind(this));
            $(document).on('click', '.ptm-modal-close, .ptm-modal-cancel, .ptm-modal-overlay', this.closeModal.bind(this));
            $(document).on('click', '.ptm-category-filter', this.filterByCategory.bind(this));
            $(document).on('click', '.ptm-modal-use-template', this.useTemplateFromModal.bind(this));
        }
        
        initTemplateCards() {
            // Add click handlers and initial state
            $('.ptm-template-card').each(function() {
                const $card = $(this);
                if ($card.hasClass('selected')) {
                    this.selectedTemplate = $card.data('template-id');
                    $('.ptm-use-template').prop('disabled', false);
                }
            }.bind(this));
        }
        
        selectTemplate(e) {
            e.preventDefault();
            
            const $card = $(e.currentTarget);
            const templateId = $card.data('template-id');
            
            // Remove selection from other cards
            $('.ptm-template-card').removeClass('selected');
            
            // Select this card
            $card.addClass('selected');
            this.selectedTemplate = templateId;
            
            // Enable use button
            $('.ptm-use-template, .ptm-modal-use-template').prop('disabled', false);
        }
        
        showConfirmation(e) {
            e.preventDefault();
            
            if (!this.selectedTemplate) {
                alert(ptmAdmin.strings.selectTemplate);
                return;
            }
            
            // Check if there's existing content
            const hasContent = this.hasExistingContent();
            
            if (hasContent) {
                $('.ptm-use-template').hide();
                $('.ptm-template-warning').show();
            } else {
                this.useTemplate();
            }
        }
        
        hideConfirmation(e) {
            e.preventDefault();
            $('.ptm-use-template').show();
            $('.ptm-template-warning').hide();
        }
        
        hasExistingContent() {
            // Check for classic editor
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                const content = tinyMCE.activeEditor.getContent();
                return content.trim().length > 0;
            }
            
            // Check for block editor
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                const blocks = wp.data.select('core/editor').getBlocks();
                return blocks.length > 0;
            }
            
            // Fallback to textarea
            const $content = $('#content');
            if ($content.length) {
                return $content.val().trim().length > 0;
            }
            
            return false;
        }
        
        useTemplate() {
            if (!this.selectedTemplate) {
                return;
            }
            
            const postId = $('#post_ID').val() || $('input[name="post_ID"]').val();
            
            if (!postId) {
                alert(ptmAdmin.strings.error);
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: ptmAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ptm_use_template',
                    template_id: this.selectedTemplate,
                    post_id: postId,
                    nonce: ptmAdmin.nonce
                },
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.applyTemplateContent(response.data);
                        this.showSuccessMessage();
                        this.hideConfirmation();
                    } else {
                        alert(response.data || ptmAdmin.strings.error);
                    }
                },
                error: () => {
                    this.hideLoading();
                    alert(ptmAdmin.strings.error);
                }
            });
        }
        
        applyTemplateContent(data) {
            // Apply to block editor
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
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
                
                return;
            }
            
            // Apply to classic editor
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                tinyMCE.activeEditor.setContent(data.content);
                
                if (data.excerpt) {
                    $('#excerpt').val(data.excerpt);
                }
                
                return;
            }
            
            // Fallback to textarea
            $('#content').val(data.content);
            if (data.excerpt) {
                $('#excerpt').val(data.excerpt);
            }
        }
        
        showLoading() {
            $('.ptm-confirm-use, .ptm-use-template, .ptm-modal-use-template')
                .prop('disabled', true)
                .text(ptmAdmin.strings.loading);
        }
        
        hideLoading() {
            $('.ptm-confirm-use')
                .prop('disabled', false)
                .text(ptmAdmin.strings.confirmUse);
            
            $('.ptm-use-template, .ptm-modal-use-template')
                .prop('disabled', false)
                .text(ptmAdmin.strings.useTemplate);
        }
        
        showSuccessMessage() {
            const $message = $('<div class="notice notice-success is-dismissible"><p>' + 
                             ptmAdmin.strings.templateApplied + '</p></div>');
            
            $('.wrap h1').first().after($message);
            
            setTimeout(() => {
                $message.fadeOut();
            }, 3000);
        }
        
        // Modal functionality
        openModal(e) {
            e.preventDefault();
            $('#ptm-template-modal').show();
            this.loadModalTemplates();
        }
        
        closeModal(e) {
            if (e.target === e.currentTarget) {
                $('#ptm-template-modal').hide();
            }
        }
        
        loadModalTemplates(category = 'all') {
            const postType = $('#post_type').val() || 'post';
            
            $.ajax({
                url: ptmAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ptm_get_templates',
                    post_type: postType,
                    category: category,
                    nonce: ptmAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderModalTemplates(response.data);
                    }
                },
                error: () => {
                    $('#ptm-modal-templates').html('<p>' + ptmAdmin.strings.error + '</p>');
                }
            });
        }
        
        renderModalTemplates(templates) {
            const $container = $('#ptm-modal-templates');
            
            if (templates.length === 0) {
                $container.html('<p>' + ptmAdmin.strings.noTemplates + '</p>');
                return;
            }
            
            let html = '<div class="ptm-template-grid">';
            
            templates.forEach(template => {
                html += this.renderTemplateCard(template);
            });
            
            html += '</div>';
            $container.html(html);
        }
        
        renderTemplateCard(template) {
            let categoryHtml = '';
            if (template.categories.length > 0) {
                const categoryNames = template.categories.map(cat => cat.name).join(', ');
                categoryHtml = `<div class="ptm-template-meta">${categoryNames}</div>`;
            }
            
            let thumbnailHtml = '';
            if (template.thumbnail) {
                thumbnailHtml = `<div class="ptm-template-thumbnail" style="background-image: url('${template.thumbnail}');"></div>`;
            }
            
            let descriptionHtml = '';
            if (template.description) {
                descriptionHtml = `<div class="ptm-template-description">${template.description}</div>`;
            }
            
            return `
                <div class="ptm-template-card" data-template-id="${template.id}">
                    ${thumbnailHtml}
                    <h4>${template.title}</h4>
                    ${categoryHtml}
                    ${descriptionHtml}
                </div>
            `;
        }
        
        filterByCategory(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const category = $button.data('category');
            
            $('.ptm-category-filter').removeClass('active');
            $button.addClass('active');
            
            this.loadModalTemplates(category);
        }
        
        useTemplateFromModal() {
            this.useTemplate();
            this.closeModal({ target: document.getElementById('ptm-template-modal') });
        }
    }
    
    // Initialize when document is ready
    $(document).ready(() => {
        window.ptmTemplateSelector = new PTMTemplateSelector();
    });
    
})(jQuery);
