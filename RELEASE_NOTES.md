## Post Template Manager v1.0.1-beta

### Changes
- Fixed "Could not load template content" bug: REST API now authenticates requests using a WordPress nonce, so admin/editor users can load templates as expected.

### Upgrade Notes
- Overwrite both `post-template-manager.php` and `js/ptm-admin.js`.
- Clear your browser cache and reload the post editor if you encounter issues after upgrading.

### License
This project remains released under The Unlicense (public domain). See LICENSE file for details.