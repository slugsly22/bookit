<?php
//error_reporting(0);
/**
 * @wordpress-plugin
 * Plugin Name:       LeadTrail
 * Description:       Easily capture and sell leads by connecting forms from multiple third party sources.
 * Version:           1.0.1
 * Author:            GHAX
 * Author URI:        https://leadtrail.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       leadtrail
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

/* Plugin Name */
$lmPluginName = "LeadTrail";

/* Use Domain as the folder name */
$PluginTextDomain = "leadtrail";

/**
 * The code that runs during plugin activation.
 */
function activate_leadtrail_plugin()
{
  require_once plugin_dir_path(__FILE__) . 'includes/classes/GHAXlt-activate-class.php';
  leadtrail_Activator::activate_leadtrail();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_leadtrail_plugin()
{
  require_once plugin_dir_path(__FILE__) . 'includes/classes/GHAXlt-deactive-class.php';
  leadtrail_Deactivator::deactivate_leadtrail();
}

/* Register Hooks For Start And Deactivate */
register_activation_hook(__FILE__, 'activate_leadtrail_plugin');
register_deactivation_hook(__FILE__, 'deactivate_leadtrail_plugin');

/**
 * The core plugin class that is used to define internationalization,
 */
require plugin_dir_path(__FILE__) . 'includes/classes/classGHAXlt.php';

/*Include the Files in which we define the sortcodes for front End */
require plugin_dir_path(__FILE__) . 'public/short-codes.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_name()
{
  $plugin = new GHAXltClass();
  $plugin->run();
}
run_plugin_name();

/* Constant */
define('GHAX_VERSION', '1.0.0');
define('GHAX_LEADTRAIL_PLUGIN_DIR', plugin_dir_path(__DIR__));
define('GHAX_LEADTRAIL_ABSPATH', plugin_dir_path(__FILE__));
define('GHAX_LEADTRAIL_RELPATH', trailingslashit(plugin_dir_url(__FILE__)));

/** API ACCESS CREDS FOR EVERYONE - THIS IS NOT USERNAME OR PASSWORD RELATED TO SPECIFIC USER */
define('GHAX_LICENSE_PURCHASE_URL', 'https://leadtrail.io');
define('GHAX_LICENSE_PURCHASE_API_USERNAME', 'ck_ccb14c45db7dc1e77735eec66871ed50808ef49b'); //KEYS NOT SPECIFIC TO USER
define('GHAX_LICENSE_PURCHASE_API_PASSWORD', 'cs_93021ea3ab1769b56da53b960940bbf78e3796d7'); //KEYS NOT SPECIFIC TO USER

/*
 * Include Custom Feild Files
 */

//Declares Common Function File 
require plugin_dir_path(__FILE__) . 'includes/function/functions.php';
