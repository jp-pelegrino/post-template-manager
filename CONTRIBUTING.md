# Contributing to Post Template Manager

Thank you for your interest in contributing to Post Template Manager! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Making Contributions](#making-contributions)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Release Process](#release-process)

## Code of Conduct

This project is released into the public domain under the Unlicense. By participating in this project, you agree to be respectful and constructive in all interactions.

### Our Standards

- Use welcoming and inclusive language
- Be respectful of differing viewpoints and experiences
- Gracefully accept constructive criticism
- Focus on what is best for the community
- Show empathy towards other community members

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- WordPress 6.8 or higher
- Node.js 18+ (for development tools)
- Git
- Composer (optional, for development dependencies)

### Development Environment

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR-USERNAME/post-template-manager.git
   cd post-template-manager
   ```

3. **Set up WordPress development environment**:
   - Use Local by Flywheel, XAMPP, or Docker
   - Create a new WordPress installation
   - Symlink or copy the plugin to `wp-content/plugins/`

4. **Install development dependencies** (optional):
   ```bash
   # Install Composer dependencies
   composer install
   
   # Install Node dependencies
   npm install
   ```

## Development Setup

### Local WordPress Installation

The easiest way to develop is with a local WordPress installation:

1. **Using Local by Flywheel** (Recommended):
   - Create a new site with WordPress 6.8+
   - Navigate to the plugins directory
   - Clone or symlink the plugin

2. **Using Docker**:
   ```bash
   # Use the provided docker-compose (if available) or create your own
   docker-compose up -d
   ```

3. **Using XAMPP/MAMP**:
   - Set up a local server
   - Download and install WordPress
   - Place plugin in `wp-content/plugins/`

### Plugin Activation

1. Navigate to WordPress admin
2. Go to Plugins > Installed Plugins
3. Activate "Post Template Manager"
4. Go to "Post Templates" in the admin menu

## Making Contributions

### Types of Contributions

We welcome various types of contributions:

- **Bug fixes**
- **Feature enhancements**
- **Documentation improvements**
- **Translations**
- **Code optimizations**
- **Security improvements**

### Contribution Workflow

1. **Create an issue** first (unless it's a small fix):
   - Use the appropriate issue template
   - Provide detailed information
   - Wait for feedback before starting work

2. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/bug-description
   ```

3. **Make your changes**:
   - Follow coding standards
   - Write tests if applicable
   - Update documentation

4. **Test your changes**:
   ```bash
   # Run PHP syntax check
   find . -name "*.php" -exec php -l {} \;
   
   # Run coding standards check (if composer installed)
   composer run lint
   
   # Test manually in WordPress
   ```

5. **Commit your changes**:
   ```bash
   git add .
   git commit -m "feat: add new template feature"
   # or
   git commit -m "fix: resolve template loading issue"
   ```

6. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

7. **Create a Pull Request**:
   - Use the PR template
   - Provide clear description
   - Link to related issues
   - Include screenshots if applicable

### Commit Message Format

Use conventional commits format:

```
type(scope): description

[optional body]

[optional footer(s)]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or modifying tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(templates): add template versioning system
fix(ajax): resolve nonce verification issue
docs(readme): update installation instructions
style(css): improve responsive design
```

## Coding Standards

### PHP Standards

Follow WordPress Coding Standards:

- **Indentation**: Use tabs, not spaces
- **Line endings**: Use Unix line endings (LF)
- **File encoding**: UTF-8 without BOM
- **PHP tags**: Always use `<?php`, never short tags
- **Naming**: Use snake_case for functions, CamelCase for classes

### JavaScript Standards

- **Indentation**: 4 spaces
- **Semicolons**: Always use semicolons
- **Quotes**: Use single quotes for strings
- **ES6+**: Use modern JavaScript features appropriately

### CSS Standards

- **Indentation**: 4 spaces
- **Properties**: One property per line
- **Ordering**: Group related properties together
- **Comments**: Use comments for complex styles

### File Organization

```
post-template-manager/
├── post-template-manager.php      # Main plugin file
├── includes/                      # PHP classes
│   ├── class-*.php               # Individual class files
├── assets/                        # Frontend assets
│   ├── js/                       # JavaScript files
│   ├── css/                      # Stylesheets
│   └── images/                   # Images
├── languages/                     # Translation files
├── tests/                        # Test files
└── docs/                         # Additional documentation
```

## Testing

### Manual Testing

1. **Basic functionality**:
   - Create templates
   - Apply templates to posts
   - Test different post types
   - Verify permissions

2. **Browser testing**:
   - Test in multiple browsers
   - Check responsive design
   - Verify JavaScript functionality

3. **WordPress compatibility**:
   - Test with different themes
   - Test with common plugins
   - Verify multisite compatibility (if applicable)

### Automated Testing

If you're adding complex functionality, consider adding tests:

```bash
# Run PHP tests (if available)
composer run test

# Run JavaScript tests (if available)
npm test
```

### Performance Testing

- Monitor database queries
- Check memory usage
- Test with large datasets
- Profile JavaScript performance

## Documentation

### Code Documentation

- **PHP**: Use PHPDoc blocks for all functions and classes
- **JavaScript**: Use JSDoc for complex functions
- **CSS**: Comment complex or non-obvious styles

### User Documentation

Update relevant documentation:

- **README.md**: For user-facing changes
- **CHANGELOG.md**: For all changes
- **Inline help**: For admin interface changes

### Example PHPDoc Block

```php
/**
 * Apply a template to a post
 *
 * @since 1.0.0
 * @param int    $template_id The template ID to apply
 * @param int    $post_id     The post ID to apply template to
 * @param array  $options     Optional. Additional options
 * @return bool|WP_Error      True on success, WP_Error on failure
 */
public function apply_template($template_id, $post_id, $options = []) {
    // Implementation
}
```

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Automated Releases

Releases are automated via GitHub Actions:

1. **Push to main branch** triggers CI/CD
2. **Version bump** based on commit messages
3. **Changelog generation** from commits
4. **ZIP package creation** for WordPress
5. **GitHub release** with artifacts

### Manual Release Checklist

If doing a manual release:

- [ ] Update version in `post-template-manager.php`
- [ ] Update version in `package.json`
- [ ] Update `CHANGELOG.md`
- [ ] Test thoroughly
- [ ] Create Git tag
- [ ] Create GitHub release
- [ ] Upload to WordPress.org (if applicable)

## Getting Help

### Resources

- **Documentation**: Check the README.md and inline documentation
- **Issues**: Browse existing issues for similar problems
- **Discussions**: Use GitHub Discussions for questions
- **WordPress Codex**: For WordPress-specific questions

### Asking Questions

When asking for help:

1. **Search first**: Check existing issues and documentation
2. **Be specific**: Provide exact error messages and steps
3. **Include context**: WordPress version, PHP version, etc.
4. **Provide code**: Use code blocks for snippets

### Getting Support

- **GitHub Issues**: For bugs and feature requests
- **GitHub Discussions**: For general questions
- **Email**: For security-related issues

## Recognition

Contributors will be:

- Listed in the README.md contributors section
- Mentioned in release notes
- Given credit in commit history

Thank you for contributing to Post Template Manager! 🎉
