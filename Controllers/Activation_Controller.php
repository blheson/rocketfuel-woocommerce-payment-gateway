<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Activation_Controller
{
    public static function register($file)
    {
        register_activation_hook($file, array(__CLASS__, 'activate'));
        register_deactivation_hook($file, array(__CLASS__, 'deactivate'));
    }
    public static function activate()
    {
        $id = get_option(Plugin::$prefix . 'process_payment_page_id', false);
        if ($id) {
          return;
        }
        $args = array(
            'post_content' => '<!-- wp:shortcode -->[rocketfuel_process_payment]<!-- /wp:shortcode -->',
            'post_title' => 'Rocketfuel Process Payment',
            'post_status' => 'publish',
            'post_type' => 'page',
        );

        $post_id = wp_insert_post($args);
        update_option(Plugin::$prefix . 'process_payment_page_id', $post_id);
    }
    public static function deactivate()
    {
        // register_deactivation_hook
        $id = get_option(Plugin::$prefix . 'process_payment_page_id');
        self::removeDetails($id);
    }
    public static function removeDetails($id)
    {
        wp_delete_post($id);
        delete_option(Plugin::$prefix . 'process_payment_page_id');
    }
}
