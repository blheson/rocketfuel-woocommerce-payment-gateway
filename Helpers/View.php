<?php
namespace Rocketfuel_Gateway\Helpers;

use Rocketfuel_Gateway\Plugin;

class View
{

    private static $base = 'views/';

    public static function render($view, $vars = [])
    {

        foreach ($vars as $var => $value) {
            $$var = $value;
        }

        if ($view_parts = explode('.', $view)) {
            include_once Plugin::get_path() . self::$base . implode('/', $view_parts) . '.php';
        } else {
            include_once Plugin::get_path() . self::$base . $view . '.php';
        }
    }
}?>
