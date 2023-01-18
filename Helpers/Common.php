<?php
namespace Rocketfuel_Gateway\Helpers;

use Rocketfuel_Gateway\Plugin;

class Common
{


    public static function get_posts( $parsed_args ){

        $get_posts = new \WP_Query($parsed_args );
        
        return $get_posts;
     
    }
}?>
