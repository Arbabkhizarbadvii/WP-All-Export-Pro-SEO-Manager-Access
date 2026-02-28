<?php
/**
 * Plugin Name: WP All Export Pro - SEO Manager Access
 * Plugin URI: https://othership.com
 * Description: Grants WP All Export Pro access to SEO Manager role
 * Version: 4.0.0
 * Author: ARBAB KHIZAR
 * Author URI: https://othership.com
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-all-export-seo-manager-access
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WP_All_Export_SEO_Manager_Access {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Hook early to override admin menu
        add_action('init', array($this, 'override_admin_menu'), 1);
        add_action('plugins_loaded', array($this, 'override_admin_menu'), 999);
        
        // Override PMXE capability checks using simple role check (same as URL Meta Overrides)
        add_filter('user_has_cap', array($this, 'override_pmxe_capability_check'), 10, 4);
        
        // Hide Settings menu and other admin menus for SEO Managers
        add_action('admin_menu', array($this, 'hide_admin_menus_for_seo_manager'), 999);
    }
    
    /**
     * Hide admin menus for SEO Managers (Settings, etc.)
     */
    public function hide_admin_menus_for_seo_manager() {
        if (!$this->is_seo_manager()) {
            return;
        }
        
        global $menu, $submenu;
        
        // Hide WordPress Settings menu
        remove_menu_page('options-general.php');
        
        // Hide WP Tabs (it uses custom post type sp_wp_tabs)
        remove_menu_page('edit.php?post_type=sp_wp_tabs');
        
        // Also remove from global menu array to prevent gaps and fix spacing
        if (is_array($menu)) {
            $menus_to_remove = array(
                'options-general.php',
                'edit.php?post_type=sp_wp_tabs'
            );
            
            foreach ($menu as $key => $item) {
                if (isset($item[2])) {
                    $menu_slug = $item[2];
                    
                    // Remove specified menus
                    if (in_array($menu_slug, $menus_to_remove)) {
                        unset($menu[$key]);
                        // Also remove submenus if they exist
                        if (isset($submenu[$menu_slug])) {
                            unset($submenu[$menu_slug]);
                        }
                    }
                }
            }
            
            // Reindex the menu array to remove gaps and fix spacing
            $menu = array_values($menu);
        }
    }
    
    /**
     * Check if current user is SEO Manager
     */
    private function is_seo_manager() {
        if (!function_exists('wp_get_current_user')) {
            return false;
        }
        
        $user = wp_get_current_user();
        if (!is_object($user) || !isset($user->roles)) {
            return false;
        }
        
        return in_array('wpseo_manager', (array) $user->roles);
    }
    
    /**
     * Check if current user has permission to access PMXE
     * Allows both administrators (manage_options) and SEO Managers (wpseo_manager role)
     * Same logic as URL Meta Overrides plugin
     * 
     * @param WP_User|null $user Optional user object to check
     */
    private function can_access_pmxe($user = null) {
        // Safety check - ensure WordPress is loaded
        if (!function_exists('wp_get_current_user')) {
            return false;
        }
        
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        // Safety check - ensure user object is valid
        if (!is_object($user) || !isset($user->roles)) {
            return false;
        }
        
        // Check if user is administrator (has manage_options in their capabilities)
        // OR has wpseo_manager role
        // We check capabilities directly to avoid infinite loops
        $user_caps = isset($user->allcaps) ? $user->allcaps : array();
        return !empty($user_caps['manage_options']) || in_array('wpseo_manager', (array) $user->roles);
    }
    
    /**
     * Override WP All Export Pro admin menu
     */
    public function override_admin_menu() {
        // Safety check
        if (!class_exists('PMXE_Plugin')) {
            return;
        }
        
        // Remove original menu hook
        if (has_action('admin_menu', 'pmxe_admin_menu')) {
            remove_action('admin_menu', 'pmxe_admin_menu', 10);
        }
        
        // Add our custom menu
        add_action('admin_menu', array($this, 'custom_admin_menu'), 5);
    }
    
    /**
     * Custom admin menu for WP All Export Pro
     */
    public function custom_admin_menu() {
        // Safety check
        if (!class_exists('PMXE_Plugin')) {
            return;
        }
        
        global $menu, $submenu;
        $icon_base64 = "PHN2ZyBjbGFzcz0iaW1nLWZsdWlkIiBpZD0ib3V0cHV0c3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMjAiIHdpZHRoPSIyMCIgdmlld0JveD0iMCAwIDQwIDQwIj48cGF0aCBmaWxsPSIjZjBmMGYxIiBzdHJva2U9Im5vbmUiIGQ9Ik0zNS40MjA4IDE5Ljk4NTNDMzUuMjI0NCAxOS44NTQyIDM0Ljk3MzUgMTkuODEwNSAzNC43NTU0IDE5Ljg4N0wzMC42ODY1IDIxLjIyQzMwLjI4MjkgMjEuMzUxMiAzMC4wNjQ3IDIxLjc4ODIgMzAuMTk1NiAyMi4xODE2QzMwLjMyNjUgMjIuNTg1OSAzMC43NjI5IDIyLjgwNDQgMzEuMTU1NiAyMi42NzMzTDMyLjc0ODIgMjIuMTQ4OEMzMS41NzAxIDIzLjg4NjIgMjguNjU3NSAyNy41NDY3IDI1LjM1MjMgMjYuOTg5NUMyNC43NTIzIDI2Ljg5MTEgMjQuMzA1MSAyNi41MTk2IDI0LjE4NTEgMjYuMDM4OEMyNC4wMTA1IDI1LjM3MjMgMjQuNDU3OCAyNC41NzQ2IDI1LjM5NTkgMjMuODIwNkMyOS4wMzkzIDIwLjkzNTkgMzEuMDM1NiAxNy4xMTE1IDMxLjAzNTYgMTMuMDU3NkMzMS4wMjQ3IDUuODU2ODIgMjUuMTc3NyAwIDE3Ljk3ODIgMEMxMC43Nzg2IDAgNC45NDI1NiA1Ljg1NjgyIDQuOTQyNTYgMTMuMDY4NkM0Ljk0MjU2IDE3LjEyMjUgNi45NDk3MiAyMC45NDY5IDEwLjU4MjIgMjMuODMxNkMxMS41MjA0IDI0LjU3NDYgMTEuOTU2NyAyNS4zODMyIDExLjc5MzEgMjYuMDQ5N0MxMS42NzMxIDI2LjUzMDUgMTEuMjE0OSAyNi45MDIgMTAuNjI1OSAyNy4wMDA0QzcuMzMxNTEgMjcuNTU3NiA0LjQwODA1IDIzLjg5NzEgMy4yMjk5MyAyMi4xNTk4TDQuODExNjYgMjIuNjg0MkM1LjIxNTI3IDIyLjgxNTQgNS42NDA3IDIyLjU5NjggNS43NzE2IDIyLjE5MjVDNS45MDI1MSAyMS43ODgyIDUuNjg0MzQgMjEuMzYyMSA1LjI4MDcyIDIxLjIzMUwxLjIxMTg3IDE5Ljg4N0MwLjk4Mjc5MiAxOS44MTA1IDAuNzQyODA2IDE5Ljg0MzMgMC41NDY0NTQgMTkuOTg1M0MwLjM1MDEwMSAyMC4xMTY0IDAuMjMwMTA4IDIwLjMzNSAwLjIxOTIgMjAuNTc1NEwwLjAwMTAzMDggMjUuMDAwOEMtMC4wMjA3ODYxIDI1LjQyNjkgMC4zMDY0NjggMjUuNzc2NiAwLjcyMDk4OSAyNS43OTg0SDAuNzY0NjIzQzEuMTY4MjQgMjUuNzk4NCAxLjUwNjQgMjUuNDgxNSAxLjUyODIxIDI1LjA2NjNMMS42MTU0OCAyMy4yNjM0QzIuOTEzNTkgMjUuMTY0NyA2LjAxMTU5IDI5IDkuOTM4NjMgMjlDMTAuMjY1OSAyOSAxMC42MDQxIDI4Ljk3ODEgMTAuOTUzMSAyOC45MTI2QzEyLjMyNzYgMjguNjgzMSAxMy4zNzQ4IDI3Ljc2NTMgMTMuNzAyMSAyNi41MDg3QzE0LjA3MjkgMjUuMDU1NCAxMy4zODU3IDIzLjUxNDcgMTEuODE0OSAyMi4yNjlDOS45ODIyNyAyMC44MTU4IDYuOTE2OTkgMTcuNjkwNyA2LjkxNjk5IDEzLjA0NjdDNi45MTY5OSA2LjkyNzY2IDExLjg5MTIgMS45NDQ5OSAxOCAxLjk0NDk5QzI0LjEwODcgMS45NDQ5OSAyOS4wODMgNi45Mjc2NiAyOS4wODMgMTMuMDQ2N0MyOS4wODMgMTcuNjkwNyAyNi4wMTc3IDIwLjgxNTggMjQuMTg1MSAyMi4yNjlDMjIuNjE0MyAyMy41MTQ3IDIxLjkxNjEgMjUuMDY2MyAyMi4yOTc5IDI2LjUwODdDMjIuNjE0MyAyNy43NTQzIDIzLjY3MjQgMjguNjcyMiAyNS4wNDY4IDI4LjkxMjZDMjkuNDUzOSAyOS42NTU2IDMyLjk3NzMgMjUuMzI4NiAzNC4zODQ1IDIzLjI2MzRMMzQuNDcxOCAyNS4wNTU0QzM0LjQ5MzYgMjUuNDU5NyAzNC44MzE3IDI1Ljc4NzUgMzUuMjM1MyAyNS43ODc1SDM1LjI3OUMzNS43MDQ0IDI1Ljc2NTYgMzYuMDIwOCAyNS40MDUgMzUuOTk4OSAyNC45ODk4TDM1Ljc4MDggMjAuNTY0NEMzNS43MzcxIDIwLjM0NTkgMzUuNjA2MiAyMC4xMTY0IDM1LjQyMDggMTkuOTg1M1pNMTMuNSAxOUMxNC4zMjg0IDE5IDE1IDE4LjMyODQgMTUgMTcuNUMxNSAxNi42NzE2IDE0LjMyODQgMTYgMTMuNSAxNkMxMi42NzE2IDE2IDEyIDE2LjY3MTYgMTIgMTcuNUMxMiAxOC4zMjg0IDEyLjY3MTYgMTkgMTMuNSAxOVpNMjIuNSAxOUMyMy4zMjg0IDE5IDI0IDE4LjMyODQgMjQgMTcuNUMyNCAxNi42NzE2IDIzLjMyODQgMTYgMjIuNSAxNkMyMS42NzE2IDE2IDIxIDE2LjY3MTYgMjEgMTcuNUMyMSAxOC4zMjg0IDIxLjY3MTYgMTkgMjIuNSAxOVpNMjQuMjM2NCAzMi40MzE2QzI0LjYxMTkgMzIuMjk0NyAyNS4wMjkyIDMyLjUwNTMgMjUuMTU0MyAzMi44OTQ3TDI2LjQ2ODcgMzYuODEwNUMyNi41MzEzIDM3LjAyMTEgMjYuNSAzNy4yNjMyIDI2LjM3NDggMzcuNDUyNkMyNi4yNDk2IDM3LjY0MjEgMjYuMDQxIDM3Ljc1NzkgMjUuODExNSAzNy43Njg0TDIxLjU4NjggMzhIMjEuNTQ1QzIxLjE1OTEgMzggMjAuODM1NyAzNy42OTQ3IDIwLjgxNDggMzcuMzA1M0MyMC43OTQgMzYuODk0NyAyMS4wOTY1IDM2LjU0NzQgMjEuNTAzMyAzNi41MjYzTDIzLjIzNDkgMzYuNDMxNkMyMC42NDc5IDM0Ljg2MzIgMTguOTQ3NiAzMi45NTc5IDE3Ljk5ODMgMzAuNTA1M0MxNy4wNDkgMzIuOTU3OSAxNS4zNDg3IDM0Ljg2MzIgMTIuNzYxNyAzNi40MzE2TDE0LjUwMzcgMzYuNTI2M0MxNC45MDAxIDM2LjU0NzQgMTUuMjEzMSAzNi44OTQ3IDE1LjE5MjIgMzcuMzA1M0MxNS4xNzE0IDM3LjY5NDcgMTQuODQ4IDM4IDE0LjQ2MiAzOEgxNC40MjAzTDEwLjE5NTUgMzcuNzY4NEM5Ljk2NjAzIDM3Ljc1NzkgOS43Njc4MyAzNy42NDIxIDkuNjMyMjIgMzcuNDUyNkM5LjQ5NjYxIDM3LjI2MzIgOS40NjUzMSAzNy4wMzE2IDkuNTM4MzQgMzYuODEwNUwxMC44MjE0IDMyLjg4NDJDMTAuOTQ2NiAzMi41MDUzIDExLjM1MzQgMzIuMjk0NyAxMS43Mzk0IDMyLjQyMTFDMTIuMTE0OSAzMi41NDc0IDEyLjMyMzYgMzIuOTU3OSAxMi4xOTg0IDMzLjM0NzRMMTEuNjAzOCAzNS4xNTc5QzE1Ljk1MzcgMzIuNjMxNiAxNi44NTA4IDI5LjUwNTMgMTcuMTQyOSAyNi43NTc5QzE3LjE5NTEgMjYuMzI2MyAxNy41NDk3IDI2IDE3Ljk3NzQgMjZDMTguNDA1MSAyNiAxOC43NzAyIDI2LjMyNjMgMTguODEyIDI2Ljc1NzlDMTkuMTA0IDI5LjUxNTggMjAuMDAxMiAzMi42NDIxIDI0LjM3MiAzNS4xNzg5TDIzLjc3NzQgMzMuMzU3OUMyMy42NDE4IDMyLjk3ODkgMjMuODUwNCAzMi41NTc5IDI0LjIzNjQgMzIuNDMxNloiLz48L3N2Zz4=";
        
        // Check if user has access (admin or SEO Manager) - same logic as URL Meta Overrides
        if ($this->can_access_pmxe()) {
            // Use 'read' capability for menu visibility (SEO Managers have this)
            // The actual access is controlled by our capability override
            $menu_cap = 'read';
            
            add_menu_page(
                __('WP All Export', 'wp_all_export_plugin'),
                __('All Export', 'wp_all_export_plugin'),
                $menu_cap,
                'pmxe-admin-home',
                array(PMXE_Plugin::getInstance(), 'adminDispatcher'),
                'data:image/svg+xml;base64,' . $icon_base64,
                111
            );
            
            // Workaround to rename 1st option to `Home`
            $submenu['pmxe-admin-home'] = array();
            
            add_submenu_page(
                'pmxe-admin-home',
                esc_html__('Export to XML', 'wp_all_export_plugin') . ' &lsaquo; ' . __('WP All Export', 'wp_all_export_plugin'),
                __('New Export', 'wp_all_export_plugin'),
                $menu_cap,
                'pmxe-admin-export',
                array(PMXE_Plugin::getInstance(), 'adminDispatcher')
            );
            
            add_submenu_page(
                'pmxe-admin-home',
                esc_html__('Manage Exports', 'wp_all_export_plugin') . ' &lsaquo; ' . __('WP All Export', 'wp_all_export_plugin'),
                __('Manage Exports', 'wp_all_export_plugin'),
                $menu_cap,
                'pmxe-admin-manage',
                array(PMXE_Plugin::getInstance(), 'adminDispatcher')
            );
            
            // Only show Settings for admins, not for SEO Manager
            if (current_user_can('manage_options')) {
                add_submenu_page(
                    'pmxe-admin-home',
                    esc_html__('Settings', 'wp_all_export_plugin') . ' &lsaquo; ' . __('WP All Export', 'wp_all_export_plugin'),
                    __('Settings', 'wp_all_export_plugin'),
                    PMXE_Plugin::$capabilities,
                    'pmxe-admin-settings',
                    array(PMXE_Plugin::getInstance(), 'adminDispatcher')
                );
            }
            
        } elseif (!current_user_can(PMXE_Plugin::$capabilities) && current_user_can(PMXE_Plugin::CLIENT_MODE_CAP)) {
            // Client mode (existing functionality)
            add_menu_page(
                __('WP All Export', 'wp_all_export_plugin'),
                esc_html__('All Export', 'wp_all_export_plugin'),
                PMXE_Plugin::CLIENT_MODE_CAP,
                'pmxe-admin-manage',
                array(PMXE_Plugin::getInstance(), 'adminDispatcher'),
                'data:image/svg+xml;base64,' . $icon_base64,
                111
            );
        }
    }
    
    /**
     * Override PMXE capability check - only grant when PMXE code checks it
     * This prevents other plugins from appearing during menu rendering
     */
    public function override_pmxe_capability_check($allcaps, $caps, $args, $user) {
        // Safety checks
        if (!class_exists('PMXE_Plugin')) {
            return $allcaps;
        }
        
        if (!is_object($user) || !isset($user->roles)) {
            return $allcaps;
        }
        
        // Prevent infinite loops - don't process if we're already checking this
        static $processing = false;
        if ($processing) {
            return $allcaps;
        }
        
        $pmxe_cap = PMXE_Plugin::$capabilities;
        $requested_cap = !empty($args) && isset($args[0]) ? $args[0] : '';
        
        // Only process if checking for PMXE capability
        if ($requested_cap !== $pmxe_cap) {
            return $allcaps;
        }
        
        // Check if user can access PMXE (admin or SEO Manager)
        if (!$this->can_access_pmxe($user)) {
            return $allcaps;
        }
        
        // CRITICAL: Check if we're on a PMXE page
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        $pmxe_pages = array(
            'pmxe-admin-home',
            'pmxe-admin-export',
            'pmxe-admin-manage',
            'pmxe-admin-settings'
        );
        
        $is_pmxe_page = in_array($current_page, $pmxe_pages);
        
        // Check AJAX requests first
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
            if (strpos($action, 'wpae_') === 0 || 
                strpos($action, 'wpallexport') === 0 || 
                strpos($action, 'pmxe') !== false) {
                // This is a PMXE AJAX request - allow it
                $allcaps[$pmxe_cap] = true;
                return $allcaps;
            }
        }
        
        // Check backtrace to see if called from PMXE code
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);
        $called_from_pmxe = false;
        
        foreach ($backtrace as $trace) {
            // Check if called from PMXE file (any PMXE file - be more lenient)
            if (isset($trace['file']) && strpos($trace['file'], 'wp-all-export-pro') !== false) {
                $called_from_pmxe = true;
                break;
            }
            
            // Check if called from PMXE class or methods
            if (isset($trace['class']) && strpos($trace['class'], 'PMXE') !== false) {
                $called_from_pmxe = true;
                break;
            }
            
            // Check if called from PMXE controller methods
            if (isset($trace['function']) && (
                $trace['function'] === 'onlyAllowAdmin' ||
                $trace['function'] === 'adminDispatcher' ||
                $trace['function'] === 'userHasAccessToItem' ||
                $trace['function'] === 'pmxe_admin_menu'
            )) {
                $called_from_pmxe = true;
                break;
            }
        }
        
        // Check if we're in WordPress menu rendering
        $in_menu_rendering = false;
        foreach ($backtrace as $trace) {
            if (isset($trace['file'])) {
                $file = $trace['file'];
                // Check for WordPress core menu files
                if (strpos($file, 'wp-admin/menu.php') !== false ||
                    strpos($file, 'wp-admin/includes/menu.php') !== false ||
                    strpos($file, 'wp-admin/includes/plugin.php') !== false) {
                    $in_menu_rendering = true;
                    break;
                }
            }
            
            // Check for menu rendering functions
            if (isset($trace['function']) && (
                $trace['function'] === 'add_menu_page' ||
                $trace['function'] === 'add_submenu_page' ||
                $trace['function'] === '_wp_menu_output'
            )) {
                // If it's not for PMXE menu, it's menu rendering for other plugins
                if (isset($trace['args']) && is_array($trace['args'])) {
                    $menu_slug = isset($trace['args'][3]) ? $trace['args'][3] : (isset($trace['args'][1]) ? $trace['args'][1] : '');
                    if (!empty($menu_slug) && strpos($menu_slug, 'pmxe') !== 0) {
                        // This is for another plugin - deny
                        return $allcaps;
                    }
                } else {
                    $in_menu_rendering = true;
                }
            }
        }
        
        // CRITICAL: If in menu rendering, NEVER grant
        // WordPress checks capabilities for ALL plugins during menu rendering
        // Granting here causes Settings, WP Tabs, and other plugins to appear
        if ($in_menu_rendering) {
            // Never grant during menu rendering - this prevents other plugins from appearing
            return $allcaps;
        }
        
        // NOT in menu rendering - safe to grant for PMXE functionality
        // Grant if:
        // 1. Called from PMXE code (definitive), OR
        // 2. On PMXE page (for PMXE functionality - this ensures PMXE pages work)
        // This ensures PMXE pages work even if backtrace detection fails
        if ($called_from_pmxe || $is_pmxe_page) {
            // Grant it for PMXE functionality
            $allcaps[$pmxe_cap] = true;
        }
        // Otherwise deny to prevent other plugins from appearing
        
        return $allcaps;
    }
}

// Initialize the plugin
WP_All_Export_SEO_Manager_Access::get_instance();
