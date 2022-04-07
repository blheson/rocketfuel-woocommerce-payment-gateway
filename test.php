<?php
$out = '';
$to_crypt  = '2335689b-7a4d-420a-9cc0-f5d765a96967';
    $cert ="-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2e4stIYooUrKHVQmwztC
/l0YktX6uz4bE1iDtA2qu4OaXx+IKkwBWa0hO2mzv6dAoawyzxa2jmN01vrpMkMj
rB+Dxmoq7tRvRTx1hXzZWaKuv37BAYosOIKjom8S8axM1j6zPkX1zpMLE8ys3dUX
FN5Dl/kBfeCTwGRV4PZjP4a+QwgFRzZVVfnpcRI/O6zhfkdlRah8MrAPWYSoGBpG
CPiAjUeHO/4JA5zZ6IdfZuy/DKxbcOlt9H+z14iJwB7eVUByoeCE+Bkw+QE4msKs
aIn4xl9GBoyfDZKajTzL50W/oeoE1UcuvVfaULZ9DWnHOy6idCFH1WbYDxYYIWLi
AQIDAQAB
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