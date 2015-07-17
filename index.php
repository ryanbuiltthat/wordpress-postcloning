<?php
/**
 * Plugin Name: EPK to Project
 * Description: A quick and easy way to copy an EPK to a Project
 * Version:    1.2.0
 * Author:        Ryan Harris
 * Author URI:    http://brandstoryexperts.com/
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * Created by PhpStorm.
 * User: Ryan
 * Date: 7/17/2015
 * Time: 1:59 AM
 */
require('class/EPKADV.php');

/**
 * Main function to get things running
 * @return void
 */
function __post_clone_advance()
{
    global $wpdb;
    new EPKADV($wpdb);
}

/**
 * Initialise the plugin
 */
add_action('plugins_loaded', '__post_clone_advance');