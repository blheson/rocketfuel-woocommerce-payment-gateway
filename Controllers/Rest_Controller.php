<?php
namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Rest_Controller{
  
    public static function register(){
        add_action('rest_api_init',array(__CLASS__,'define_rest_route'));
    }
    /**
     * Define all rest route
     * @return void
     */
    public static function define_rest_route(){

        // address for rocketfuel callback
       $gateway= new Rocketfuel_Gateway_Controller();
        register_rest_route(
            Plugin::get_api_route_namespace(),
            'payment',
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array('Rocketfuel_Gateway\Controllers\Webhook_Controller', 'payment'),
                'permission_callback' => '__return_true'
            )
        );
          register_rest_route(
            Plugin::get_api_route_namespace(),
            'payment',
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array('Rocketfuel_Gateway\Controllers\Webhook_Controller', 'check_callback'),
                'permission_callback' => '__return_true'
            )
        );
        register_rest_route(
            Plugin::get_api_route_namespace(),
            'check',
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array('Rocketfuel_Gateway\Controllers\Webhook_Controller', 'check_callback'),
                'permission_callback' => '__return_true'
            )
        );
        register_rest_route(
            Plugin::get_api_route_namespace(),
            'auth',
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array('Rocketfuel_Gateway\Controllers\Process_Payment_Controller', 'auth'),
                'permission_callback' => '__return_true'
            )
        );
        register_rest_route(
            Plugin::get_api_route_namespace(),
            'update_order',
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array( $gateway, 'update_order'),
                'permission_callback' => '__return_true'
            )
        );
         register_rest_route(
            Plugin::get_api_route_namespace(),
            'merchant_auth',
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array( $gateway, 'merchant_auth'),
                'permission_callback' => '__return_true'
            )
        );
        unset($gateway);
    }
}