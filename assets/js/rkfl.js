// version: 1.0.1
(function () {
  this.RocketFuel = function () {
    window.paymentdone = false;
    this.iframeInfo = {
      iframe: null,
      iframeData: null,
      iFrameId: 'Rocketfuel',
      iframeUrl: {
        prod: `https://iframe.rocketfuelblockchain.com`,
        stage2: `https://qa-iframe.rocketdemo.net/`,
        local: `http://192.168.0.181:8080/`,

        preprod: `https://preprod-iframe.rocketdemo.net/`,
        dev: `https://dev-iframe.rocketdemo.net/`,
        sandbox: `https://iframe-sandbox.rocketfuelblockchain.com`,
      },
      isOverlay: false
    };
    this.domain = {
      prod: `https://app.rocketfuelblockchain.com/api`,
      stage2: `https://qa-app.rocketdemo.net/api`,
      local: `http://c334-102-89-45-112.ngrok.io/api`,
      preprod: `https://preprod-app.rocketdemo.net/api`,
      dev: 'https://dev-app.rocketdemo.net/api',
      sandbox: `https://app-sandbox.rocketfuelblockchain.com/api`,
    };
    window.iframeInfo = this.iframeInfo;
    this.rkflToken = null
    var rocketFuelDefaultOptions = {
      uuid: null,
      token: null,  //rkfltoken 
      callback: null,
      merchantAuth: null,
      environment: 'prod',
      payload: null
    };
    if (arguments[0] && typeof arguments[0] == "object") {
      this.options = setDefaultConfiguration(
        rocketFuelDefaultOptions,
        arguments[0]
      );
    } else {
      this.options = defaultConfiguration;
    }

    if (arguments[0].uuid != null) {
      initializeEvents(this.iframeInfo, rocketFuelDefaultOptions);
      getUUIDInfo(rocketFuelDefaultOptions, this.domain, this.iframeInfo);
    }
  };
  //public methods
  this.RocketFuel.prototype.initPayment = function () {
    showOverlay(this.iframeInfo.iframe);
  };
  this.RocketFuel.prototype.addBank = async function (data, env) {
    const apiDomain = this.domain[env];
    const resp = await fetch(`${apiDomain}/stock-market/dwolla/add-bank`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'authorization': `Bearer ${getLocaLStorage('access')}` //getter and setter for localStorage
      },
      body: data
    }).then(res => res.json())
      .catch(err => console.log(err))
    return resp;
  }
  this.RocketFuel.prototype.fetchBanks = async function (env) {
    const apiDomain = this.domain[env];
    const resp = await fetch(`${apiDomain}/stock-market/my?update=false`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'authorization': `Bearer ${getLocaLStorage('access')}`
      },
    })
      .then(resp => resp.json())
      .catch(err => console.log(err));

    return resp;
  }

  this.RocketFuel.prototype.purchaseCheck = async function (data, env) {
    const accessToken = getLocaLStorage('access');
    const encryptOptions = {
      method: "POST",
      headers: {
        authorization: "Bearer " + accessToken,
        "Content-Type": "application/json",
      },
      body: JSON.parse(JSON.stringify(data))
    }
    const apiDomain = this.domain[env];
    const response = await (await fetch(`${apiDomain}/purchase/encrypt-check`, encryptOptions)).text();
    const { result } = await JSON.parse(response);
    const checkoptions = {
      method: "POST",
      headers: {
        authorization: "Bearer " + accessToken,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(result),
    };
    const check = await fetch(`${apiDomain}/purchase/check`, checkoptions);
  }
  this.RocketFuel.prototype.makePurchase = async function (data, env) {
    const accessToken = getLocaLStorage('access');
    const encryptOptions = {
      method: "POST",
      headers: {
        authorization: "Bearer " + accessToken,
        "Content-Type": "application/json",
      },
      body: JSON.parse(JSON.stringify(data))
    }
    const apiDomain = this.domain[env];
    const response = await (await fetch(`${apiDomain}/purchase/encrypt-check`, encryptOptions)).text();
    const { result } = await JSON.parse(response);
    const checkoptions = {
      method: "POST",
      headers: {
        authorization: "Bearer " + accessToken,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(result),
    };
    const purchaseResp = await fetch(`${apiDomain}/purchase`, checkoptions);
    return purchaseResp;
  }
  this.RocketFuel.prototype.rkflAutoSignUp = async function (data, env) {



    const rkflToken = await autoSignUp(data, this.domain, env);
    if (!rkflToken || !rkflToken.ok) {
      removeLocalStorage();
    setLocalStorage('rkfl_email', rkflToken.data.email);

      
      return null
    }
    setLocalStorage('access', rkflToken.result.access);
    setLocalStorage('refresh', rkflToken.result.refresh);
    if (rkflToken.result.rkflToken) {
      setLocalStorage('rkfl_token', rkflToken.result.rkflToken);
    }

    setLocalStorage('rkfl_status', rkflToken.result.status);


    this.rkflToken = rkflToken;

    if (data && data.merchantAuth) {
      setLocalStorage('merchant_auth', data.merchantAuth);
    }
    return rkflToken
  }

  //private methods
  function setDefaultConfiguration(source, properties) {
    var property;
    for (property in properties) {
      if (properties.hasOwnProperty(property)) {
        source[property] = properties[property];
      }
    }
    return source;
  }
  function getLocaLStorage(key) {
    return localStorage.getItem(key)
  }
  function setLocalStorage(key, value) {
    localStorage.setItem(key, value);
  }
  function decodeHTMLEntities(text) {
    // Create a new element or use one from cache, to save some element creation overhead
    const el = decodeHTMLEntities.__cache_data_element
      = decodeHTMLEntities.__cache_data_element
      || document.createElement('div');

    const enc = text
      // Prevent any mixup of existing pattern in text
      .replace(/⪪/g, '⪪#')
      // Encode entities in special format. This will prevent native element encoder to replace any amp characters
      .replace(/&([a-z1-8]{2,31}|#x[0-9a-f]+|#\d+);/gi, '⪪$1⪫');

    // Encode any HTML tags in the text to prevent script injection
    el.textContent = enc;

    // Decode entities from special format, back to their original HTML entities format
    el.innerHTML = el.innerHTML
      .replace(/⪪([a-z1-8]{2,31}|#x[0-9a-f]+|#\d+)⪫/gi, '&$1;')
      .replace(/#⪫/g, '⪫');

    // Get the decoded HTML entities
    const dec = el.textContent;

    // Clear the element content, in order to preserve a bit of memory (it is just the text may be pretty big)
    el.textContent = '';

    return dec;
  }
  function initializeEvents(iframeInfo, rocketFuelDefaultOptions) {
    window.addEventListener("message", async (event) => {
      if (event.data.type === "rocketfuel_new_height") {
        const iframe = document.getElementById(iframeInfo.iFrameId);
        if (!!iframe) {
          const windowHeight = window.innerHeight - 20;
          if (windowHeight < event.data.data) {
            iframe.style.height = windowHeight + "px";
            iframe.contentWindow.postMessage(
              {
                type: "rocketfuel_max_height",
                data: windowHeight,
              },
              "*"
            );
          } else {
            iframe.style.height = event.data.data + "px";
          }
        }
      }
      if (event.data.type === "rocketfuel_change_height") {
        document.getElementById(iframeInfo.iFrameId).style.height = event.data.data;
      }

      if (event.data.type === "rocketfuel_get_cart") {
        await sendCartToIframe();
      }
      if (event.data.type === "rocketfuel_iframe_close") {
        closeOverlay(iframeInfo);
        // if(window.paymentdone && window.redirectUrl) {
        if (window.redirectUrl) {
          setTimeout(function () {
            window.location.href = decodeHTMLEntities(window.redirectUrl);
          }, 2000);
        }
        console.log('[PUSH_MESSAGE_TO_ANDROID]', window.Android && window.Android.shareData(JSON.stringify(event.data)));
        window.Android && window.Android.shareData(JSON.stringify(event.data));
      }
      if (event.data.type === "rocketfuel_result_ok") {
        window.paymentdone = true;
        if (rocketFuelDefaultOptions.callback) {
          rocketFuelDefaultOptions.callback(event.data.response);
        }
        console.log('[PUSH_MESSAGE_TO_ANDROID]', window.Android && window.Android.shareData(JSON.stringify(event.data)));
        window.Android && window.Android.shareData(JSON.stringify(event.data));
      }
    });
  }

  function getUUIDInfo(rocketFuelDefaultOptions, domainInfo, iframeInfo) {
    if (!rocketFuelDefaultOptions.uuid) {
      // return error
    }
    // var myHeaders = new Headers();
    // myHeaders.append("authorization", "Bearer " + rocketFuelDefaultOptions.token);
    // myHeaders.append("mode", "no-cors");
    // myHeaders.append("cache-control", "no-cache");
    var requestOptions = {
      method: "GET",
      // headers: myHeaders,
      redirect: "follow",
    };
    const apiDomain = domainInfo[rocketFuelDefaultOptions.environment];
    fetch(`${apiDomain}/hosted-page?uuid=${rocketFuelDefaultOptions.uuid}`, requestOptions)
      .then((response) => response.text())
      .then((result) => {

        //update rfOrder handle the data
        const iframeResp = JSON.parse(result);
        if (iframeResp.ok !== undefined && iframeResp.ok) {
          iframeResp.result.returnval.merchantAuth = rocketFuelDefaultOptions.merchantAuth;
          iframeResp.result.returnval.uuid = rocketFuelDefaultOptions.uuid;
          iframeResp.result.returnval.token = rocketFuelDefaultOptions.token;
          iframeInfo.iframeData = iframeResp.result !== undefined ? iframeResp.result.returnval : undefined;
          window.redirectUrl = '';
          if (
            iframeResp.result
            && iframeResp.result.returnval
          ) {
            window.redirectUrl = iframeResp.result.returnval.redirectUrl || '';
            // add https self
            if (window.redirectUrl && !testIfValidURL(window.redirectUrl)) {
              window.redirectUrl = ('https://' + window.redirectUrl);
            }
            if (iframeResp.result.returnval.customerInfo
              && iframeResp.result.returnval.customerInfo.merchantAuth) {
              setLocalStorage('merchant_auth', iframeResp.result.returnval.customerInfo.merchantAuth);
            } else if (iframeResp.result.returnval.merchantAuth) {
              setLocalStorage('merchant_auth', iframeResp.result.returnval.merchantAuth);
            }

            if (iframeResp.result.returnval.customerInfo
              && iframeResp.result.returnval.customerInfo.rkflToken) {
              // Invoice SSO
              setLocalStorage('rkfl_token', iframeResp.result.returnval.customerInfo.rkflToken);
            }
          }
        }
        iframeInfo.iframe = createIFrame(iframeInfo, rocketFuelDefaultOptions);
        window.iframeInfo = iframeInfo;
      })
      .catch((error) => console.log("error", error));
  }

  async function autoSignUp(rocketFuelDefaultOptions, domainInfo, env) {
    var myHeaders = new Headers();
    myHeaders.append("authorization", "Bearer " + (rocketFuelDefaultOptions.accessToken || null));
    myHeaders.append('Content-Type', 'application/json');
    myHeaders.append('merchant-auth', rocketFuelDefaultOptions.merchantAuth);
    // myHeaders.append("cache-control", "no-cache");
    delete rocketFuelDefaultOptions.accessToken
    let payload = rocketFuelDefaultOptions, endpoint = 'autosignup';

    if (rocketFuelDefaultOptions.encryptedReq) {
      payload = { encryptedReq: rocketFuelDefaultOptions.encryptedReq };
      endpoint = 'sso';
    }

    var requestOptions = {
      method: "POST",
      headers: myHeaders,
      redirect: "follow",
      body: JSON.stringify(payload)
    };
    const apiDomain = domainInfo[env];
    let resp = await fetch(`${apiDomain}/auth/${endpoint}`, requestOptions);



    let rkflres = await resp.text()
    const iframeResp = JSON.parse(rkflres);


    // var requestOptions = {
    //   method: "POST",
    //   headers: myHeaders,
    //   redirect: "follow",
    //   body: JSON.stringify({
    //     rkflToken: iframeResp.result.rkflToken,
    //     merchantAuth: rocketFuelDefaultOptions.merchantAuth,
    //     skipMerchantAuth: true
    //   })
    // };
    // let iframeLoginResp = {};
    // try {
    //   const loginResp = await fetch(`${apiDomain}/auth/autologin`, requestOptions)
    //   let loginResult = await loginResp.text()
    //   iframeLoginResp = JSON.parse(loginResult);
    //   console.log({ iframeLoginResp })

    // } catch (error) {
    //   console.error(error)
    // }

    return iframeResp;
  }

  function showOverlay(iframe) {
    if (iframe && !this.isOverlay) {
      document.getElementById("iframeWrapper").appendChild(iframe)
      document.body.classList.add('blur-body');
      var styleElem = document.head.appendChild(document.createElement("style"));

      styleElem.innerHTML = ".blur-body:before {content: ''; width: 100%; position: fixed !important; background: #000000; height: 100%; z-index: 214748364 !important; opacity: 0.5; top: 0px !important; }";

      this.isOverlay = true;
    } else {
      setTimeout(function () {
        showOverlay(window.iframeInfo.iframe);
      }, 1000)
    }
  }

  function closeOverlay(iframeInfo) {
    isOverlay = false;
    document.getElementById(iframeInfo.iFrameId).remove();
    document.body.classList.remove('blur-body');
  }

  function checkExtension() {
    return typeof rocketfuel === "object";
  }

  function testIfValidURL(str) {
    // const pattern = new RegExp('^https?:\\/\\/' + // protocol
    //   '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
    //   '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
    //   '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
    //   '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
    //   '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator

    // return !!pattern.test(str);
    if (str.includes('https') || str.includes('http')) {
      return true;
    }
    return false;
  }

  function sendCartToIframe(iframe, iframeInfo) {
    if (iframe) {
      iframeInfo.iframeData.token = localStorage.getItem('rkfl_token') || null;
      iframeInfo.iframeData.merchantAuth = localStorage.getItem('merchant_auth') || null;
      iframeInfo.iframeData.access = getLocaLStorage('access') || null;
      iframeInfo.iframeData.refresh = getLocaLStorage('refresh') || null;
      iframeInfo.iframeData.status = getLocaLStorage('rkfl_status') || null;
      iframeInfo.iframeData.isSSO = true
      iframeInfo.iframeData.email = getLocaLStorage('rkfl_email') || null;

      iframe.contentWindow.postMessage(
        {
          type: "rocketfuel_send_cart",
          data: iframeInfo.iframeData,
        },
        "*"
      );
    }
  }

  function createIFrame(iframeInfo, rocketFuelDefaultOptions) {
    let iframe = document.createElement("iframe");
    iframe.title = iframeInfo.iFrameId;
    iframe.id = iframeInfo.iFrameId;
    iframe.style.display = "none";
    iframe.style.border = 0;
    iframe.style.width = "365px";
    iframe.src = iframeInfo.iframeUrl[rocketFuelDefaultOptions.environment];

    iframe.onload = async function () {
      iframe.style.display = "block";
      sendCartToIframe(iframe, iframeInfo);
    };
    return iframe;
  }

  //Make the DIV element draggagle:
  //     dragElement();
  document.addEventListener('DOMContentLoaded', dragElement);
  function dragElement() {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    let iframeWrapper = document.createElement("div");
    let iframeWrapperHeader = document.createElement("div");
    iframeWrapper.style.cssText = "width: 365px; position: fixed; z-index: 2147483647 !important ; top: 10px; right: 10px; box-shadow: 0px 4px 7px rgb(0 0 0 / 30%);"
    iframeWrapper.id = "iframeWrapper";
    iframeWrapperHeader.id = "iframeWrapperHeader"
    document.querySelector('body').appendChild(iframeWrapper).appendChild(iframeWrapperHeader);

    // document.getElementById("iframeWrapper").style.cssText = "width: 365px; position: fixed; z-index: 2147483647 !important ; top: 10px; right: 10px; box-shadow: 0px 4px 7px rgb(0 0 0 / 30%);";
  
    document.getElementById("iframeWrapperHeader").style.cssText = "padding: 10px; cursor: move; z-index: 10; position: absolute; width: 100%; height: 10px; left: 50px"

    document.getElementById("iframeWrapperHeader").onmousedown = dragMouseDown;

    function dragMouseDown(e) {
      e = e || window.event;
      e.preventDefault();
      // get the mouse cursor position at startup:
      pos3 = e.clientX;
      pos4 = e.clientY;
      document.onmouseup = closeDragElement;
      // call a function whenever the cursor moves:
      document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
      e = e || window.event;
      e.preventDefault();
      // calculate the new cursor position:
      pos1 = pos3 - e.clientX;
      pos2 = pos4 - e.clientY;
      pos3 = e.clientX;
      pos4 = e.clientY;
      // set the element's new position:
      iframeWrapper.style.top = (iframeWrapper.offsetTop - pos2) + "px";
      iframeWrapper.style.left = (iframeWrapper.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
      /* stop moving when mouse button is released:*/
      document.onmouseup = null;
      document.onmousemove = null;
    }
    function getRkflToken(data) {
      return data
    }

  }
     
})();
function removeLocalStorage() {
  localStorage.removeItem('access');
  localStorage.removeItem('refresh');
  localStorage.removeItem('rkfl_token');
  localStorage.removeItem('merchant_auth');
  localStorage.removeItem('rkfl_status');
  localStorage.removeItem('rkfl_email');

}
removeLocalStorage();
console.log('[ PLUGIN_VERSION ]', 'v3.2.1.32')