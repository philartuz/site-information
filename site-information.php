<?php
/*
Plugin Name: Site Information
Plugin URI: [Your Plugin URL]
Description: Replicates the functionality of the site health info and displays the output on a WordPress admin page.
Version: 1.0.0
Author: [Your Name]
Author URI: [Your Website URL]
License: [License Name]
*/

// Add a custom admin menu item
function plugin_list_replica_admin_menu() {
    add_menu_page(
        'Site Information',
        'Site Information',
        'manage_options',
        'plugin_list_replica',
        'plugin_list_replica_page',
        'dashicons-info',
        75
    );
}
add_action('admin_menu', 'plugin_list_replica_admin_menu');
// Create the system information page
function plugin_list_replica_page() {
    global $wpdb; // Include this line to access $wpdb object
    require_once(ABSPATH . 'wp-load.php');

    // Check if the button was clicked
    if (isset($_POST['toggle_format'])) {
        $display_json = isset($_POST['display_json']);
        update_option('display_json', $display_json);
    } else {
        $display_json = get_option('display_json', false);
    }

    // Output the system information here
    echo '<div class="wrap">';
    echo '<h1>Site Information</h1>';

    // Display the button
    echo '<form method="post" action="">';
    echo '<input type="hidden" name="toggle_format" value="1" />';
    echo '<label for="display_json"><input type="checkbox" name="display_json" id="display_json" ' . checked($display_json, true, false) . ' /> Display JSON format</label>';
    echo '<input type="submit" value="Toggle Format" class="button button-primary" />';
    echo '</form>';
    if ($display_json){
        // Generate JSON data from the system information
        $json_data = array(
            'wp-core' => array(
                'version' => get_bloginfo('version'),
                'site_language' => get_bloginfo('language'),
                'user_language' => get_user_locale(),
                'timezone' => get_option('timezone_string'),
                'permalink' => get_option('permalink_structure'),
                'https_status' => (is_ssl() ? 'true' : 'false'),
                'multisite' => (is_multisite() ? 'true' : 'false')
            ),
            'wp-theme-list' => array(),
            'wp-plugin-list' => array(),
            'wp-server' => array(
                'server_architecture' => $_SERVER['SERVER_SOFTWARE'],
                'php_version' => phpversion(),
                'php_sapi' => php_sapi_name(),
                'max_input_variables' => ini_get('max_input_vars'),
                'time_limit' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit'),
                'max_input_time' => ini_get('max_input_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
                'curl_version' => curl_version()['version'],
                'suhosin' => (extension_loaded('suhosin') ? 'true' : 'false'),
                'imagick_availability' => (extension_loaded('imagick') ? 'true' : 'false')
            ),
            'wp-database' => array(
                'extension' => 'mysqli',
                'server_version' => $wpdb->db_version(),
                'client_version' => $wpdb->db_server_info()
            ),
            'wp-constants' => array(
                'WP_HOME' => get_option('home'), // Replace WP_HOME with get_option('home')
                'WP_SITEURL' => get_option('siteurl'), // Replace WP_SITEURL with get_option('siteurl')
                'WP_CONTENT_DIR' => WP_CONTENT_DIR,
                'WP_PLUGIN_DIR' => WP_PLUGIN_DIR,
                'WP_MEMORY_LIMIT' => WP_MEMORY_LIMIT,
                'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
                'WP_DEBUG' => (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false'),
                'WP_DEBUG_DISPLAY' => (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'true' : 'false'),
                'WP_DEBUG_LOG' => (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'true' : 'false'),
                'SCRIPT_DEBUG' => (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'true' : 'false'),
                'WP_CACHE' => (defined('WP_CACHE') && WP_CACHE ? 'true' : 'false'),
                'CONCATENATE_SCRIPTS' => (defined('CONCATENATE_SCRIPTS') ? 'true' : 'false'),
                'COMPRESS_SCRIPTS' => (defined('COMPRESS_SCRIPTS') ? 'true' : 'false'),
                'COMPRESS_CSS' => (defined('COMPRESS_CSS') ? 'true' : 'false'),
                'WP_ENVIRONMENT_TYPE' => (defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : ''),
                'AUTOMATIC_UPDATER_DISABLED' => (defined('AUTOMATIC_UPDATER_DISABLED') && AUTOMATIC_UPDATER_DISABLED ? 'true' : 'false')
            ),
            'wp-filesystem' => array(
                'wordpress' => (is_writable(ABSPATH) ? 'writable' : 'not writable'),
                'wp-content' => (is_writable(WP_CONTENT_DIR) ? 'writable' : 'not writable'),
                'uploads' => (is_writable(wp_upload_dir()['path']) ? 'writable' : 'not writable'),
                'plugins' => (is_writable(WP_PLUGIN_DIR) ? 'writable' : 'not writable'),
                'themes' => (is_writable(get_theme_root()) ? 'writable' : 'not writable')
            ),
            'wp-media' => array(
                'image_editor' => 'WP_Image_Editor_GD',
                'imagick_module_version' => 'Not available',
                'imagemagick_version' => 'Not available',
                'imagick_version' => 'Not available',
                'file_uploads' => (ini_get('file_uploads') ? 'File uploads is turned on' : 'File uploads is turned off'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'max_effective_size' => size_format(wp_max_upload_size()),
                'max_file_uploads' => ini_get('max_file_uploads'),
                'gd_version' => 'bundled (' . (function_exists('gd_info') ? gd_info()['GD Version'] : 'GD library not available') . ' compatible)'
            )
        );
        
        // Get theme list
        $themes = wp_get_themes();
        foreach ($themes as $theme) {
            // Get the theme slug
            $theme_slug = $theme->get_stylesheet();

            $json_data['wp-theme-list'][] = array(
                'slug' => $theme_slug,
                'status' => (get_stylesheet() === get_option('stylesheet') ? 'active' : 'inactive'),
                'update' => 'none',
                'version' => $theme->get('Version')
            );
        }

        // Get plugin list
        $plugins = get_plugins();
        foreach ($plugins as $plugin_file => $plugin_data) {
            // Get the plugin slug from the plugin file path
            $plugin_slug = basename(dirname($plugin_file));

            $json_data['wp-plugin-list'][] = array(
                'name' => $plugin_slug,
                'status' => (is_plugin_active($plugin_file) ? 'active' : 'inactive'),
                'update' => (get_plugin_updates() ? 'available' : 'none'),
                'version' => $plugin_data['Version']
            );
        }


        $json_string = json_encode($json_data, JSON_PRETTY_PRINT);
        $json_string_formatted = nl2br($json_string); // Convert newlines to HTML line breaks

        echo '<pre>'; // Add pre tag to preserve formatting
        echo $json_string_formatted;
        echo '</pre>'; // Close pre tag
    }
    else {
        // Display system information in the default format
        echo '<div id="system-information">';
        echo '<pre>'; // Add pre tag to preserve formatting
        // Output the system information here
        echo '<div class="wrap">';
        echo '<h1>System Information</h1>';

        // Display system information in the default format
        echo '<div id="system-information">';

        // Display wp-core information
        echo '<h2>wp-core</h2>';
        echo 'Version: ' . get_bloginfo('version') . '<br>';
        echo 'Site Language: ' . get_bloginfo('language') . '<br>';
        echo 'User Language: ' . get_user_locale() . '<br>';
        echo 'Timezone: ' . get_option('timezone_string') . '<br>';
        echo 'Permalink: ' . get_option('permalink_structure') . '<br>';
        echo 'HTTPS Status: ' . (is_ssl() ? 'true' : 'false') . '<br>';
        echo 'Multisite: ' . (is_multisite() ? 'true' : 'false') . '<br>';

        // Display wp theme list
        echo '<h2>wp theme list</h2>';
        $themes = wp_get_themes();
        echo '<table>';
        echo '<tr><th>Name</th><th>Status</th><th>Update</th><th>Version</th></tr>';
        foreach ($themes as $theme) {
            echo '<tr>';
            echo '<td>' . $theme->get_stylesheet() . '</td>'; // Display theme slug
            echo '<td>' . (get_stylesheet() === get_option('stylesheet') ? 'active' : 'inactive') . '</td>';
            echo '<td>none</td>';
            echo '<td>' . $theme->get('Version') . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Display wp plugin list
        echo '<h2>wp plugin list</h2>';
        $plugins = get_plugins();
        echo '<table>';
        echo '<tr><th>Name</th><th>Status</th><th>Update</th><th>Version</th></tr>';
        foreach ($plugins as $plugin_file => $plugin_data) {
            $plugin_slug = dirname($plugin_file); // Get plugin slug
            echo '<tr>';
            echo '<td>' . $plugin_slug . '</td>'; // Display plugin slug
            echo '<td>' . (is_plugin_active($plugin_file) ? 'active' : 'inactive') . '</td>';
            echo '<td>' . (get_plugin_updates() ? 'available' : 'none') . '</td>';
            echo '<td>' . $plugin_data['Version'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Display wp-server information
        echo '<h2>wp-server</h2>';
        echo 'Server Architecture: ' . $_SERVER['SERVER_SOFTWARE'] . '<br>';
        echo 'PHP Version: ' . phpversion() . '<br>';
        echo 'PHP SAPI: ' . php_sapi_name() . '<br>';
        echo 'Max Input Variables: ' . ini_get('max_input_vars') . '<br>';
        echo 'Time Limit: ' . ini_get('max_execution_time') . '<br>';
        echo 'Memory Limit: ' . ini_get('memory_limit') . '<br>';
        echo 'Max Input Time: ' . ini_get('max_input_time') . '<br>';
        echo 'Upload Max Filesize: ' . ini_get('upload_max_filesize') . '<br>';
        echo 'PHP Post Max Size: ' . ini_get('post_max_size') . '<br>';
        echo 'cURL Version: ' . curl_version()['version'] . '<br>';
        echo 'Suhosin: ' . (extension_loaded('suhosin') ? 'true' : 'false') . '<br>';
        echo 'Imagick Availability: ' . (extension_loaded('imagick') ? 'true' : 'false') . '<br>';

        // Display wp-database information
        echo '<h2>wp-database</h2>';
        global $wpdb;
        echo 'Extension: mysqli<br>';
        echo 'Server Version: ' . $wpdb->db_version() . '<br>';
        echo 'Client Version: ' . $wpdb->db_server_info() . '<br>';

        // Define WP_HOME constant
        if (!defined('WP_HOME')) {
            define('WP_HOME', get_home_url());
        }

        // Define WP_SITEURL constant
        if (!defined('WP_SITEURL')) {
            define('WP_SITEURL', get_site_url());
        }

        // WP Constants
        $wp_constants = array(
            'WP_HOME' => WP_HOME,
            'WP_SITEURL' => WP_SITEURL,
            'WP_CONTENT_DIR' => WP_CONTENT_DIR,
            'WP_PLUGIN_DIR' => WP_PLUGIN_DIR,
            'WP_MEMORY_LIMIT' => WP_MEMORY_LIMIT,
            'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
            'WP_DEBUG' => WP_DEBUG,
            'WP_DEBUG_DISPLAY' => WP_DEBUG_DISPLAY,
            'WP_DEBUG_LOG' => WP_DEBUG_LOG,
            'SCRIPT_DEBUG' => SCRIPT_DEBUG,
            'WP_CACHE' => WP_CACHE,
            'CONCATENATE_SCRIPTS' => defined('CONCATENATE_SCRIPTS') ? CONCATENATE_SCRIPTS : 'undefined',
            'COMPRESS_SCRIPTS' => defined('COMPRESS_SCRIPTS') ? COMPRESS_SCRIPTS : 'undefined',
            'COMPRESS_CSS' => defined('COMPRESS_CSS') ? COMPRESS_CSS : 'undefined',
            'WP_ENVIRONMENT_TYPE' => WP_ENVIRONMENT_TYPE,
            'DB_CHARSET' => defined('DB_CHARSET') ? DB_CHARSET : 'undefined',
            'DB_COLLATE' => defined('DB_COLLATE') ? DB_COLLATE : 'undefined'
        );

        // Display wp-constants information
        echo '<h2>wp-constants</h2>';
        foreach ($wp_constants as $constant => $value) {
            echo $constant . ': ' . $value . '<br>';
        }

        // Display wp-filesystem information
        echo '<h2>wp-filesystem</h2>';
        echo 'wordpress: ' . (is_writable(ABSPATH) ? 'writable' : 'not writable') . '<br>';
        echo 'wp-content: ' . (is_writable(WP_CONTENT_DIR) ? 'writable' : 'not writable') . '<br>';
        echo 'uploads: writable<br>';
        echo 'plugins: writable<br>';
        echo 'themes: writable<br>';

        // Display wp-media information
        echo '<h2>wp-media</h2>';
        echo 'Image Editor: WP_Image_Editor_GD<br>';
        echo 'imagick_module_version: Not available<br>';
        echo 'imagemagick_version: Not available<br>';
        echo 'imagick_version: Not available<br>';
        echo 'file_uploads: ' . (ini_get('file_uploads') ? 'File uploads is turned on' : 'File uploads is turned off') . '<br>';
        echo 'post_max_size: ' . ini_get('post_max_size') . '<br>';
        echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . '<br>';
        echo 'max_effective_size: ' . size_format(wp_max_upload_size()) . '<br>';
        echo 'max_file_uploads: ' . ini_get('max_file_uploads') . '<br>';
        echo 'gd_version: bundled (' . (function_exists('gd_info') ? gd_info()['GD Version'] : 'GD library not available') . ' compatible)<br>';
        
        echo '</div>';
    }

    echo '</pre>'; // Close pre tag
    echo '</div>';
}