// version: 1.0.1
(function () {
  this.RocketFuel = function () {
    this.iframeInfo = {
      iframe: null,
      iframeData: null,
      iFrameId: 'Rocketfuel',
      iframeUrl: {
        prod: `https://iframe.rocketfuelblockchain.com`,
        stage2: `https://qa-iframe.rocketdemo.net/`,
        local: `http://localhost:8080`,
        preprod: `https://preprod-iframe.rocketdemo.net/`,
        dev: `https://dev-iframe.rocketdemo.net/`,
        sandbox: `https://iframe-sandbox.rocketfuelblockchain.com`,
      },
      isOverlay: false
    };
    this.domain = {
      prod: `https://app.rocketfuelblockchain.com/api`,
      stage2: `https://qa-app.rocketdemo.net/api`,
      local: `http://localhost:3001/api`,
      preprod: `https://preprod-app.rocketdemo.net/api`,
      dev: 'https://dev-app.rocketdemo.net/api',
      sandbox: 'https://app-sandbox.rocketfuelblockchain.com/api',
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
    if (rkflToken.result) {
      setLocalStorage('access', rkflToken.result.access);
      setLocalStorage('refresh', rkflToken.result.refresh);
      setLocalStorage('rkfl_token', rkflToken.result.rkflToken);
    }

    this.rkflToken = rkflToken
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
        // TODO destroy iframe
        closeOverlay(iframeInfo);
      }
      if (event.data.type === "rocketfuel_result_ok") {
       
        console.log('Event is shown: ',event.data);

        if (rocketFuelDefaultOptions.callback) {
          rocketFuelDefaultOptions.callback(event.data.response);
        }
      }
    });
  }

  function getUUIDInfo(rocketFuelDefaultOptions, domainInfo, iframeInfo) {
    if (!rocketFuelDefaultOptions.uuid) {
      // return error
    }
    var myHeaders = new Headers();
    myHeaders.append("authorization", "Bearer " + rocketFuelDefaultOptions.token);
    // myHeaders.append("cache-control", "no-cache");
    var requestOptions = {
      method: "GET",
      headers: myHeaders,
      redirect: "follow",
    };
    const apiDomain = domainInfo[rocketFuelDefaultOptions.environment];
    fetch(`${apiDomain}/hosted-page?uuid=${rocketFuelDefaultOptions.uuid}`, requestOptions)
      .then((response) => response.text())
      .then((result) => {

        //update rfOrder handle the data
        const iframeResp = JSON.parse(result);
        if (iframeResp.ok !== undefined && iframeResp.ok) {
          iframeResp.result.returnval.token = rocketFuelDefaultOptions.token;
          iframeResp.result.returnval.merchantAuth = rocketFuelDefaultOptions.merchantAuth;
          iframeInfo.iframeData = iframeResp.result !== undefined ? iframeResp.result.returnval : undefined;
        }
        iframeInfo.iframe = createIFrame(iframeInfo, rocketFuelDefaultOptions);
      })
      .catch((error) => console.log("error", error));
  }

  async function autoSignUp(rocketFuelDefaultOptions, domainInfo, env) {
    var myHeaders = new Headers();
    myHeaders.append("authorization", "Bearer " + null);
    myHeaders.append('Content-Type', 'application/json');
    // myHeaders.append("cache-control", "no-cache");
    var requestOptions = {
      method: "POST",
      headers: myHeaders,
      redirect: "follow",
      body: JSON.stringify(rocketFuelDefaultOptions)
    };
    const apiDomain = domainInfo[env];
    let resp = await fetch(`${apiDomain}/auth/autosignup`, requestOptions)
    let rkflres = await resp.text()
    const iframeResp = JSON.parse(rkflres);
    return iframeResp;
  }

  function showOverlay(iframe) {
    if (iframe && !this.isOverlay) {
      document.getElementById("iframeWrapper").appendChild(iframe)
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
  }

  function checkExtension() {
    return typeof rocketfuel === "object";
  }

  function sendCartToIframe(iframe, iframeInfo) {
    if (iframe) {
      iframeInfo.iframeData.token = localStorage.getItem('rkfl_token') || null;
      iframeInfo.iframeData.merchantAuth = localStorage.getItem('merchant_auth') || null;
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
  document.addEventListener('DOMContentLoaded', dragElement);


  function dragElement() {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    let iframeWrapper = document.createElement("div");
    let iframeWrapperHeader = document.createElement("div");
    iframeWrapper.id = "iframeWrapper";
    iframeWrapperHeader.id = "iframeWrapperHeader"
    document.querySelector('body').appendChild(iframeWrapper).appendChild(iframeWrapperHeader);

    document.getElementById("iframeWrapper").style.cssText = "width: 365px; position: absolute; z-index: 9; top: 10px; right: 10px; box-shadow: 0px 4px 7px rgb(0 0 0 / 30%);";
    document.getElementById("iframeWrapperHeader").style.cssText = "padding: 10px; cursor: move; z-index: 10; position: absolute; width: 50%; height: 62px"

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
}
removeLocalStorage();