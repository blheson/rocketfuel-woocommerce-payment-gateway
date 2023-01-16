<?php

namespace Rocketfuel_Gateway;

use Rocketfuel_Gateway\Controllers\Activation_Controller;
use Rocketfuel_Gateway\Controllers\Rest_Controller;
use Rocketfuel_Gateway\Controllers\Metabox_Controller;
use Rocketfuel_Gateway\Controllers\Woocommerce_Controller;

class Plugin
{
    public static $prefix = 'rocketfuel_gateway_';

    /**
     * Get the plugin's absolute path
     * Comes with trailing slash
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function get_path()
    {
        return plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR;
    }
    /**
     * Get the plugin's absolute path
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function get_url($url)
    {
        return plugin_dir_url(__FILE__) . $url;
    }
    /**
     * Get the plugin's version
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function get_ver()
    {
        return ROCKETFUEL_VER || time();
    }
    public static function get_prefix()
    {
        return self::$prefix;
    }
    public static function get_api_route_namespace()
    {
        return 'rocketfuel/v1';
    }
    public static function init($file)
    {
        Activation_Controller::register($file);
        add_action('init', array(__CLASS__, 'boot'));
    }
    public static function boot()
    {
        Rest_Controller::register();
        Woocommerce_Controller::register();
      
    }
}
