<?php

/*
 * Plugin Name:       Agitatie WP agenda integration
 * Plugin URI:        https://oyvey.nl
 * Description:       Integrates Agitatie agenda posts with monday
 * Version:           0
 * Author:            Sjerp van Wouden
 * Author URI:        https://oyvey.nl
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       awai
 * Domain Path:       /languages
 */


require_once(plugin_dir_path(__FILE__) . './rest.php');
if (is_admin()) {
    require_once(plugin_dir_path(__FILE__) . './register-plugin.php');
    require_once(plugin_dir_path(__FILE__) . './admin-messages.php');
    require_once(plugin_dir_path(__FILE__) . './admin-page.php');
}
