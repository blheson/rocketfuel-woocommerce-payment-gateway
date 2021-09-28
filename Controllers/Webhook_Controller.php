<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Webhook_Controller
{
    public static function payment($request_data)
    {
        $body = $request_data->get_params();
        $data= $body['data'];
	
        $signature= $body['signature'];
	
	 file_put_contents("serialize.txt", serialize($body)."\n\n\n",FILE_APPEND);
			 file_put_contents("knowtext.txt", $data['data']."\n\n\n",FILE_APPEND);

        if (!self::verify_callback($data['data'],$signature)) {
	 file_put_contents("not-status.txt",'not verified');
			
            return false;
        }
	 file_put_contents("true-status.txt",'verified');

        $order = wc_get_order($data['offerId']);

        if (!$order) {
            return false;
        }
        $status = (int)$data['paymentStatus'];
        if ($status === 0) {
            return true;
        }
        if ($status === -1) {
            $order->update_status('failed', 'Rocketfuel could not verify the payment');
            return true;
        }
        if ($status === 1) {
            $order->update_status('completed');
            return true;
        }
        if ($status === 101) {
            $order->update_status('partial-payment');
            return true;
        }
    }
    public static function check_callback($request_data)
    {

        return rest_ensure_response(array(
            'callback_status' => 'ok'
        ));
    }
    public static function verify_callback($body, $signature)
    {

        $signature_buffer = base64_decode($signature);        
	
        return (1 == openssl_verify($body, $signature_buffer,self::get_callback_public_key(), OPENSSL_ALGO_SHA256));
    }
    private static function get_gateway()
    {
        return new Rocketfuel_Gateway_Controller();
    }
    public static function get_callback_public_key()
    {
     
		return "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2e4stIYooUrKHVQmwztC
/l0YktX6uz4bE1iDtA2qu4OaXx+IKkwBWa0hO2mzv6dAoawyzxa2jmN01vrpMkMj
rB+Dxmoq7tRvRTx1hXzZWaKuv37BAYosOIKjom8S8axM1j6zPkX1zpMLE8ys3dUX
FN5Dl/kBfeCTwGRV4PZjP4a+QwgFRzZVVfnpcRI/O6zhfkdlRah8MrAPWYSoGBpG
CPiAjUeHO/4JA5zZ6IdfZuy/DKxbcOlt9H+z14iJwB7eVUByoeCE+Bkw+QE4msKs
aIn4xl9GBoyfDZKajTzL50W/oeoE1UcuvVfaULZ9DWnHOy6idCFH1WbYDxYYIWLi
AQIDAQAB
-----END PUBLIC KEY-----";
    }
}