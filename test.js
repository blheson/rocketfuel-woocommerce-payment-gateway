var crypto = require('crypto');
var pb  = `-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhZgr6EqGbfonL796ICFE
3Te8oN1oFrEpTsEwBEu0qTwIdE2mDeYZnSmgmMoxF8vCfd9YSOkQAG5O3gvWWGNy
pMeYQzbtb/+knT9RyND2hHndxUaKKklJXyhXwrRm4Z1LHq2SAbk53KaHGCtZs+/r
XMwndCToTyT2d6vhAmypylCZB0sDtSjX0YCtABekhE76xdPcCioW+i80SSSFFm4J
rHL35ZdYHFutz1RUxfiQTtu2yC5qkte6qMTDOE1KSA1UJs6iP0xAj3kT9uPfklcZ
84rDwrOwi4P1NvYEslAcKkUT2G0uN+bp7PtMI1JRnUcUxx4FoaLqYfyRDiN2Nt6N
7QIDAQAB
-----END PUBLIC KEY-----`;
var merchantAuth = function() {
    var buffer = Buffer.from("92a95f6c-5529-48d6-86b5-0790595665f2");
    var encrypted = crypto.publicEncrypt(pb, buffer);
    return encrypted.toString("base64");
}

console.log(merchantAuth());