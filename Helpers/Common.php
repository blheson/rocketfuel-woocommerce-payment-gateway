<?php
namespace Rocketfuel_Gateway\Helpers;

use Rocketfuel_Gateway\Plugin;

class Common
{


    public static function get_posts( $parsed_args ){

        $get_posts = new \WP_Query($parsed_args );
        
        return $get_posts;
     
    }
    public static function days_in_secs( $days ) {
		return 60 * 60 * 24 * (int) $days;
	}
}?>
