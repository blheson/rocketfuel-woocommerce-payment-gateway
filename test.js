var crypto = require('crypto');
var pb  = `-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2e4stIYooUrKHVQmwztC
/l0YktX6uz4bE1iDtA2qu4OaXx+IKkwBWa0hO2mzv6dAoawyzxa2jmN01vrpMkMj
rB+Dxmoq7tRvRTx1hXzZWaKuv37BAYosOIKjom8S8axM1j6zPkX1zpMLE8ys3dUX
FN5Dl/kBfeCTwGRV4PZjP4a+QwgFRzZVVfnpcRI/O6zhfkdlRah8MrAPWYSoGBpG
CPiAjUeHO/4JA5zZ6IdfZuy/DKxbcOlt9H+z14iJwB7eVUByoeCE+Bkw+QE4msKs
aIn4xl9GBoyfDZKajTzL50W/oeoE1UcuvVfaULZ9DWnHOy6idCFH1WbYDxYYIWLi
AQIDAQAB
-----END PUBLIC KEY-----`;
var merchantAuth = function() {
    var buffer = Buffer.from("92a95f6c-5529-48d6-86b5-0790595665f2");
    var encrypted = crypto.publicEncrypt(pb, buffer);
    return encrypted.toString("base64");
}

console.log(merchantAuth());