# WP All Export Pro - SEO Manager Access

A WordPress plugin that grants **WP All Export Pro** (PMXE) access to users with the **SEO Manager** role (`wpseo_manager`), in addition to administrators.

## Description

By default, WP All Export Pro restricts its admin menu and functionality to users with the `manage_options` capability (typically Administrators). This plugin allows users with the **SEO Manager** role to access WP All Export Pro so they can create and manage XML/data exports without needing full admin rights.

### Features

- **Role-based access**: SEO Managers can access the WP All Export menu (New Export, Manage Exports).
- **Settings restricted**: The WP All Export "Settings" submenu is shown only to Administrators, not to SEO Managers.
- **Clean admin**: Hides the main WordPress "Settings" menu and "WP Tabs" menu for SEO Managers to reduce clutter.
- **Capability override**: Uses WordPress `user_has_cap` filter so PMXE capability checks pass for SEO Managers only when needed (PMXE pages/AJAX), avoiding side effects for other plugins.

## Requirements

- **WordPress**: 5.0 or higher  
- **PHP**: 7.0 or higher  
- **WP All Export Pro**: Must be installed and active (plugin checks for `PMXE_Plugin` class).  
- **SEO Manager role**: The role `wpseo_manager` must exist (e.g. from an SEO/role plugin that defines it).

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/YOUR_USERNAME/wp-all-export-seo-manager-access.git
   ```
2. In WordPress admin, go to **Plugins** and activate **"WP All Export Pro - SEO Manager Access"**.

## Usage

- No configuration needed. Once the plugin is active:
  - Users with the **Administrator** role continue to have full access to WP All Export Pro (including Settings).
  - Users with the **SEO Manager** role see **All Export** in the admin menu and can use **New Export** and **Manage Exports**. They do not see **Settings** or the main WordPress **Settings** / **WP Tabs** menus.

## How It Works

- **Menu**: Replaces the default PMXE admin menu with a custom one that uses the `read` capability for visibility, so SEO Managers (who have `read`) see the export menu.
- **Capability**: Hooks into `user_has_cap` and grants PMXE’s required capability only when the request is for a PMXE page or PMXE AJAX action, and the user is either an Administrator or has the `wpseo_manager` role. This prevents other plugins from incorrectly gaining PMXE access.
- **Admin cleanup**: For SEO Managers, removes the **Settings** and **WP Tabs** menu items via `admin_menu`.

## Changelog

### 4.0.0
- Initial release: SEO Manager access to WP All Export Pro, Settings hidden from SEO Managers, admin menu cleanup.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) file.

## Author

**ARBAB KHIZAR**
