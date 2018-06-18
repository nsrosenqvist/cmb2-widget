<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
Plugin Name: CMB2 Widgets
Description: Allow CMB2 forms to be used in Wordpress widgets
Version: 1.0.0
Author: Niklas Rosenqvist
Author URI: https://www.nsrosenqvist.com/
*/

if (! class_exists('CMB2_Widgets')) {
    class CMB2_Widgets
    {
        static function init()
        {
            if (! class_exists('CMB2')) {
                return;
            }

            // Include files
            require_once __DIR__.'/src/CMB2_Widget.php';
        }
    }
}
add_action('init', [CMB2_Widgets::class, 'init']);
