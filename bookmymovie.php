<?php
/**
 * @package bookmymovie
 */
/*
 Plugin Name: bookmymovie
 Plugin Uri: localhost
 Description: This is a sample plugin for movie seat booking
 Version 1.0.0
 Author : Ujwal Abhishek
 Text Domain: bookmymovie
 */

defined( 'ABSPATH' ) or die("Hey ! you are not allowed to access this folder.");

date_default_timezone_set('UTC');

define('BMM_DIR_PATH', plugin_dir_path(__FILE__));
define('BMM_DIR_URL', plugin_dir_url(__FILE__));

require_once(plugin_dir_path(__FILE__) . 'class-bookmymovie.php');

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook(__FILE__, array('BookMyMovie', 'activate'));
register_deactivation_hook(__FILE__, array('BookMyMovie', 'deactivate'));

BookMyMovie::get_instance();

add_action( 'plugins_loaded', 'bmm_include_plugin_files' );

function bmm_include_plugin_files()
{
    require_once(plugin_dir_path(__FILE__) . 'lib/Bmm_PreFormValidation.php');
}