# Post Template Manager

A comprehensive WordPress plugin that allows administrators to create post templates with preset layouts, blocks, content, and featured images. This makes it easy for writers and editors to create consistent, professional posts using predefined templates.

## Features

### 🎨 Template Creation & Management
- **Custom Post Type**: Dedicated interface for creating and managing post templates
- **Gutenberg Block Support**: Full compatibility with the WordPress block editor
- **Featured Image Templates**: Set default featured images that auto-apply when using templates
- **Template Categories**: Organize templates by purpose (Job Postings, Procurement, Events, etc.)
- **Template Descriptions**: Add detailed descriptions to help users choose the right template

### 👥 User-Friendly Interface
- **Template Selector**: Easy-to-use interface in the post editor for selecting and applying templates
- **Visual Template Cards**: See template previews with thumbnails and descriptions
- **Category Filtering**: Filter templates by category for quick access
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices

### 🔧 Advanced Functionality
- **Post Type Targeting**: Configure which post types can use specific templates
- **Usage Tracking**: Track how often templates are used and by whom
- **Duplicate Templates**: Easily duplicate existing templates for quick variations
- **Permission Control**: Admin-only access to template creation and management
- **Statistics Dashboard**: View comprehensive usage statistics and analytics

### 🛡️ Security & Compatibility
- **WordPress 6.8+ Compatible**: Built for the latest WordPress standards
- **PHP 8.2+ Support**: Modern PHP compatibility with type declarations
- **Security First**: Proper nonce verification and capability checks
- **HTTPS Ready**: Fully compatible with SSL/HTTPS deployments
- **Cloudflare Compatible**: Works seamlessly with Cloudflare tunnels

## Installation

### Method 1: Manual Installation
1. Download the plugin files
2. Upload the `post-template-manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to 'Post Templates' in your WordPress admin to start creating templates

### Method 2: WordPress Admin Upload
1. Go to Plugins > Add New in your WordPress admin
2. Click "Upload Plugin" and select the plugin ZIP file
3. Install and activate the plugin
4. Access 'Post Templates' from the admin menu

## Getting Started

### Creating Your First Template

1. **Navigate to Templates**: Go to `Post Templates > Add New` in your WordPress admin
2. **Create Content**: Use the block editor to design your template layout
3. **Set Categories**: Assign appropriate categories (Job Posting, Procurement, etc.)
4. **Configure Settings**: 
   - Choose target post types
   - Enable auto-apply for featured images
   - Add a helpful description
5. **Publish**: Save your template for use

### Using Templates

1. **Create New Post**: Start creating a new post or page
2. **Find Template Selector**: Look for the "Use Post Template" meta box
3. **Choose Template**: Browse available templates by category
4. **Apply Template**: Click "Use Selected Template" to apply the layout
5. **Customize**: Edit the applied content as needed

## Configuration

### Plugin Settings

Access plugin settings at `Post Templates > Settings`:

- **Enable for Post Types**: Choose which post types can use templates
- **Default Template Category**: Set a default category for new templates
- **Usage Tracking**: Enable/disable template usage statistics
- **Template Selector Position**: Choose where the template selector appears

### Template Categories

Organize your templates with built-in categories:

- **Job Postings**: Templates for job announcements
- **Procurement & Bidding**: Templates for procurement processes
- **News & Announcements**: Templates for general announcements
- **Events**: Templates for event invitations and announcements

Create custom categories at `Post Templates > Categories`.

## Usage Examples

### Job Posting Template
```
[Block: Heading] Job Title: [Position Name]
[Block: Paragraph] Department: [Department]
[Block: List] 
- Required qualifications
- Responsibilities
- Benefits
[Block: Button] Apply Now
```

### Procurement Notice Template
```
[Block: Heading] Invitation to Bid: [Project Name]
[Block: Paragraph] Deadline: [Date]
[Block: Table] Bid requirements and specifications
[Block: Contact] Contact information for inquiries
```

## Developer Documentation

### Hooks and Filters

#### Actions
- `ptm_template_applied`: Fired when a template is applied to a post
- `ptm_template_created`: Fired when a new template is created
- `ptm_template_usage_tracked`: Fired when template usage is tracked

#### Filters
- `ptm_copyable_meta_keys`: Filter meta keys that should be copied from template to post
- `ptm_template_content`: Filter template content before applying
- `ptm_enabled_post_types`: Filter which post types can use templates

### Custom Development

#### Adding Custom Meta Fields to Templates
```php
add_filter('ptm_copyable_meta_keys', function($keys) {
    $keys[] = '_custom_field_name';
    return $keys;
});
```

#### Custom Template Content Processing
```php
add_filter('ptm_template_content', function($content, $template_id, $post_id) {
    // Process template content before applying
    return $content;
}, 10, 3);
```

### REST API Endpoints

The plugin extends WordPress REST API with:

- `GET /wp-json/wp/v2/ptm_template` - List templates
- `GET /wp-json/wp/v2/template-categories` - List template categories
- `POST /wp-json/wp/v2/ptm_template` - Create template (admin only)

## File Structure

```
post-template-manager/
├── post-template-manager.php      # Main plugin file
├── includes/                      # Core plugin classes
│   ├── class-posttype.php         # Custom post type handler
│   ├── class-taxonomy.php         # Template categories
│   ├── class-admininterface.php   # Admin dashboard
│   ├── class-templateselector.php # Template selection UI
│   └── class-ajax.php             # AJAX handlers
├── assets/                        # Frontend assets
│   ├── js/                        # JavaScript files
│   │   ├── admin.js               # Admin functionality
│   │   ├── block-editor.js        # Block editor integration
│   │   └── template-selector.js   # Template selection
│   └── css/                       # Stylesheets
│       └── admin.css              # Admin styles
├── languages/                     # Translation files
├── README.md                      # This file
├── CHANGELOG.md                   # Version history
└── LICENSE                        # Unlicense
```

## Database Schema

### Template Usage Tracking
```sql
CREATE TABLE wp_ptm_template_usage (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    template_id bigint(20) NOT NULL,
    post_id bigint(20) NOT NULL,
    user_id bigint(20) NOT NULL,
    used_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## Requirements

- **WordPress**: 6.8 or higher
- **PHP**: 8.2 or higher
- **MySQL**: 5.7 or higher (or MariaDB equivalent)
- **Browser**: Modern browsers with ES6 support

## Frequently Asked Questions

### Can I customize template layouts after applying them?
Yes! Once a template is applied, you can edit the content just like any regular post. The template provides a starting point.

### Will templates work with custom post types?
Absolutely! You can configure templates to work with any public post type, including custom post types.

### Can I restrict template access to specific user roles?
Currently, template creation is limited to administrators. Template usage follows standard post editing permissions.

### Do templates work with page builders?
The plugin is designed for the WordPress block editor (Gutenberg). Compatibility with page builders may vary.

### Can I export/import templates?
Templates can be exported/imported using WordPress's standard export tools since they're stored as custom posts.

## Contributing

We welcome contributions! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Make your changes with proper documentation
4. Test thoroughly
5. Submit a pull request

### Coding Standards
- Follow WordPress Coding Standards
- Use proper sanitization and validation
- Include docblocks for all functions
- Write meaningful commit messages

## Support

### Getting Help
- Check the plugin documentation
- Review the FAQ section
- Search existing GitHub issues
- Create a new issue with detailed information

### Bug Reports
When reporting bugs, please include:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior
- Error messages (if any)

## License

This plugin is released into the public domain under the Unlicense.

```
This is free and unencumbered software released into the public domain.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a compiled
binary, for any purpose, commercial or non-commercial, and by any
means.

In jurisdictions that recognize copyright laws, the author or authors
of this software dedicate any and all copyright interest in the
software to the public domain. We make this dedication for the benefit
of the public at large and to the detriment of our heirs and
successors. We intend this dedication to be an overt act of
relinquishment in perpetuity of all present and future rights to this
software under copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

For more information, please refer to <https://unlicense.org/>
```

## Changelog

### Version 1.0.0
- Initial release
- Custom post type for templates
- Template categories and organization
- Block editor integration
- Usage tracking and statistics
- Admin interface for template management
- Multi-post-type support
- Featured image template support

## Roadmap

### Upcoming Features
- Template versioning
- Template import/export functionality
- Advanced template variables
- Role-based template access
- Template scheduling
- Multi-site network support
- Template marketplace integration

---

**Developed by JP Pelegrino** | [GitHub Repository](https://github.com/jp-pelegrino/post-template-manager)
