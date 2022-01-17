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
 
    }
    public static function deactivate()
    {

    }
    public static function removeDetails($id)
    {
     
    }
}
