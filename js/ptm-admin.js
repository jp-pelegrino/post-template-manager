(function (wp, $) {
    if (!wp || !wp.data || !wp.apiFetch) return;

    // Use the nonce for all apiFetch requests
    if (window.ptmAdmin && window.ptmAdmin.nonce) {
        wp.apiFetch.use(wp.apiFetch.createNonceMiddleware(window.ptmAdmin.nonce));
    }

    async function fetchTemplates() {
        return await wp.apiFetch({ path: '/wp/v2/post_template?per_page=100' });
    }

    async function fetchTemplateContent(id) {
        return await wp.apiFetch({ path: `/ptm/v1/template/${id}` });
    }

    function insertContent(content) {
        wp.data.dispatch('core/editor').replaceBlocks(
            wp.blocks.parse(content)
        );
    }

    wp.plugins.registerPlugin('ptm-template-sidebar', {
        render: function () {
            const [templates, setTemplates] = wp.element.useState([]);
            const [loading, setLoading] = wp.element.useState(false);
            const [error, setError] = wp.element.useState(null);

            wp.element.useEffect(() => {
                setLoading(true);
                fetchTemplates()
                    .then((data) => {
                        setTemplates(data);
                        setLoading(false);
                    })
                    .catch((err) => {
                        setError('Failed to load templates');
                        setLoading(false);
                    });
            }, []);

            function onTemplateSelect(event) {
                const id = event.target.value;
                if (!id) return;
                fetchTemplateContent(id)
                    .then((tpl) => {
                        insertContent(tpl.content);
                    })
                    .catch(() => alert('Could not load template content'));
            }

            return wp.element.createElement(
                wp.editPost.PluginSidebar,
                {
                    name: 'ptm-template-sidebar',
                    title: 'Post Templates',
                },
                wp.element.createElement('div', {},
                    loading ? 'Loading...' :
                        error ? error :
                            wp.element.createElement('div', {},
                                wp.element.createElement('label', {}, 'Choose a template:'),
                                wp.element.createElement('select', { onChange: onTemplateSelect, defaultValue: '' },
                                    wp.element.createElement('option', { value: '' }, '-- Select Template --'),
                                    templates.map((tpl) =>
                                        wp.element.createElement('option', { value: tpl.id }, tpl.title.rendered)
                                    )
                                )
                            )
                )
            );
        }
    });
})(window.wp, window.jQuery);