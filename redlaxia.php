<?php

/**
 *
 * @link              https://redlaxia.com
 * @since             1.0.0
 * @package           Redlaxia
 *
 * @wordpress-plugin
 * Plugin Name:       Redlaxia
 * Plugin URI:        https://redlaxia.com/wordpress
 * Description:       Promociona tu tienda y productos en nuestra app de tiendas online.
 * Version:           1.0.5
 * Author:            Redlaxia
 * Author URI:        https://redlaxia.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       redla
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
/*
 * ********************** DEFINES ************************************
 */

define("REDLA_URL", plugin_dir_url(__FILE__));
define("REDLA_PATH", dirname(__FILE__));
define("REDLA_URL_APP", "https://redlaxia.com/");
define("REDLA_NAME_APP", "Redlaxia");
define("REDLA_SLUG_APP", "redlaxia");
/*
 * ********************** INCLUDES ************************************
 */

include_once("includes/function.php");
include_once("includes/feed_woocommerce.php");
include_once("includes/new_order.php");
include_once("includes/redlaxia_order.php");


register_deactivation_hook( __FILE__, 'redla_desactivar_plugin' );


function redla_conectar_tienda() {
    add_menu_page(
            REDLA_NAME_APP,
            REDLA_NAME_APP,
            'manage_options',
            REDLA_SLUG_APP,
            'redla_conectar_tienda_form',
            plugins_url( 'redlaxia/images/logo-r-menu.png' ),
            26
    );
}

add_action('admin_menu', 'redla_conectar_tienda');

//wp_remote_get
function redla_conectar_tienda_form() {
    include('pages/conectar_tienda.php');
}

add_action("admin_enqueue_scripts", "redla_insert_script_upload");

function redla_insert_script_upload() {
    wp_enqueue_media();
    wp_register_script('senciya_upload', plugin_dir_url(__FILE__) . '/js/senciya_upload.js', array('jquery'), '1', true);
    wp_enqueue_script('senciya_upload');
}
