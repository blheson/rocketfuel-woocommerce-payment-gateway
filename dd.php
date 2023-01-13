
	<?php

    function get_encrypted($to_crypt)
    {

        $out = '';

        $cert ='-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2e4stIYooUrKHVQmwztC
/l0YktX6uz4bE1iDtA2qu4OaXx+IKkwBWa0hO2mzv6dAoawyzxa2jmN01vrpMkMj
rB+Dxmoq7tRvRTx1hXzZWaKuv37BAYosOIKjom8S8axM1j6zPkX1zpMLE8ys3dUX
FN5Dl/kBfeCTwGRV4PZjP4a+QwgFRzZVVfnpcRI/O6zhfkdlRah8MrAPWYSoGBpG
CPiAjUeHO/4JA5zZ6IdfZuy/DKxbcOlt9H+z14iJwB7eVUByoeCE+Bkw+QE4msKs
aIn4xl9GBoyfDZKajTzL50W/oeoE1UcuvVfaULZ9DWnHOy6idCFH1WbYDxYYIWLi
AQIDAQAB
-----END PUBLIC KEY-----';


        $public_key = openssl_pkey_get_public($cert);

        $key_length = openssl_pkey_get_details($public_key);

        $part_len = $key_length['bits'] / 8 - 11;
        $parts = str_split($to_crypt, $part_len);

        foreach ($parts as $part) {
            $encrypted_temp = '';
            openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
            $out .= $encrypted_temp;
        }

        return base64_encode($out);
    }
    // echo get_encrypted(json_encode(['email'=>'bb@gmail.com']));



    // QPz7cws2i+9kqUUYXEQA0/WGKHRMMpH4lPlNhpFTLW6rDhsBmj4o2SUOCFsrOTY84OGUWdeCif2tsMGR7Swb2QmFKeqiRVYwAa6q2u9zhGvmx5UyOAGAGos9RZ5c0bbo4k+jzwQbQRTm5GkHB6NZK7oTgKWhcxxZU3q5wF2VRNBn1gP3xfHnqabaIYZoUvhGWPJbAr11s/eXNdjkx8fdRby+dTnU7JbnmFp+x9azGnXUFTBlJmdcFtWtn1O7kkwl7Wqfn/tjo5PJUDVDJovrvoX9LBNrhDh0djA2Vdv9TabTBfR/AX4XyoRUFqrznWsgojsJsdGUrS+74YTIipy4Kw==  blessingworld007@gmail.com


    // I3qCtrErS7OBYKaxMVCYzzP/jCK6EDU5B0OmnJH825JE21EQXqe9l+u31voceuCKjr2MYstj8pqLwbN/SkfAHOz35YPNYQokxNfZgazVOtE1w+9pHM7k9Eehyl2w+S/GvsmJp730/AbfL5mKkzR8jdRxFfJVBS5+/Z6hZuDqqIdBSxEnAw8AMIPRIiAG/OSZAhWvX2SAUVLd00zZXaPyGMT/pVrNfzRfbJkVKvc2W4dSbeDNTM2ASSFlAQs55e0yUCgnAqYm014JL+/dAdQ9p/QqUtj5f46kzFYJ4r0YqyAvevwpGGB0nlsU0CymnngNJ2gmGgR7YtyhpP5zyo83ow== bb@gmail.com

$rest = [[
        "id"=> "167112521",
    "key"=> 0.8894154765240705,
    "num"=> 0,
    "name"=> "111",
    "price"=> 1,
    "quantity"=> 11,
    "totalPrice"=> 11,
    "localAmount"=> 1,
    "statusRefund"=> [
            "pending"=> 0,
        "success"=> 0,
        "rejected"=> 0
    ],
    "localCurrency"=> "USD"
]];

$result=array_map(function($element){

return $element['id'];
},$rest);

array_search()

var_dump($result);