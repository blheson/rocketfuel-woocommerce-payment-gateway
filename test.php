<?php
$out = '';
$to_crypt  = json_encode(array('merchantId'=>'2abb4e12-7317-4d5c-a14d-a2cf9d445e47'));
// $to_crypt  = json_encode(array('orderId'=>'2947','subscriptionId'=>'ef2949ff918fccd1f72255cd78e60bac-2680'));
// $to_crypt  = 'ef2949ff918fccd1f72255cd78e60bac-2680';

    $cert ="-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjLrAnU5gsRRpAGmYiB9T
0EyWr0jAVLQ7EOlgn4qTwRrTgFJdWCRmTRW09cghe/Bu8AHf4fhLnx/cYEjsaJDk
m4L+RoZeuQUQAgLvcanvOJDLEYHArNYzgN8JFXeDdYHsZS0TdhCiiVrEJOr4t1A+
6rMpyguda9vCzag1pf81G91WJ9RlMq1yWkc9fmuOvyOhMBsJQ/UuqpFbGXk8g1s6
l4irLlaKcMhEdKZ1GjvNDQUPndvPKLyio4byjt6bSecgSGya4Ieuz7UhXn2vFcOk
Ob9EpV8AqkLnui3pY5w1V/fjckdjXdLx9CPjaPk2a1/tacKYvKNcAUdPJ5l/NtwX
fQIDAQAB
-----END PUBLIC KEY-----";

$public_key = openssl_pkey_get_public($cert);

$key_length = openssl_pkey_get_details($public_key);

$part_len = $key_length['bits'] / 8 - 11;
$parts = str_split($to_crypt, $part_len);

foreach ($parts as $part) {
    $encrypted_temp = '';
    openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
    $out .=  $encrypted_temp;
}

echo base64_encode($out);