function setCookieMonster(setAll) {
	if (setAll === undefined) {
		setAll = false;
	}

	var optionValues = {},
		cookieOptions = document.querySelectorAll(".cmnstr-options input[type='checkbox']");

	for (var i = 0; i < cookieOptions.length; i++) {
		optionValues[cookieOptions[i].getAttribute("name")] = (setAll) ? true : cookieOptions[i].checked;
	}

	var optionString = JSON.stringify(optionValues);
	var host = getDomain();

	if (optionValues.statistics == false) {
		document.cookie = '_gat=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + host;
		document.cookie = '_ga=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + host;
		document.cookie = '_gid=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + host;
	}

	document.cookie = "cmnstr=" + optionString + "; path=/; max-age=31536000; domain=." + host;

	if (typeof window.updateGoogleConsent === 'function') {
		window.updateGoogleConsent();
	}

	document.dispatchEvent(new CustomEvent('cookieConsentChanged', {
		detail: { categories: optionValues }
	}));

	var cmnstrBanner = document.querySelector(".cmnstr");
	cmnstrBanner.parentNode.removeChild(cmnstrBanner);
	location.reload();
}

function getDomain() {
   var i=0,domain=document.domain,p=domain.split('.'),s='_gd'+(new Date()).getTime();
   while(i<(p.length-1) && document.cookie.indexOf(s+'='+s)==-1){
      domain = p.slice(-1-(++i)).join('.');
      document.cookie = s+"="+s+";domain="+domain+";";
   }
   document.cookie = s+"=;expires=Thu, 01 Jan 1970 00:00:01 GMT;domain="+domain+";";
   return domain;
};

// Tabs
window.onload = function(){
	var tabLinks = document.querySelectorAll(".cmnstr-tabs-nav li a");
	
	for (var i = 0; i < tabLinks.length; i++) {
		var link = tabLinks[i];
		link.addEventListener("click", function(e){
			e.preventDefault();
			if (!this.classList.contains("is-active")) {
				var activeElements = document.querySelectorAll(".cmnstr-tabs *.is-active");
				for (var i = 0; i < activeElements.length; i++) {
					activeElements[i].classList.remove("is-active");
				}
				
				this.classList.add("is-active");
				var targetElem = document.querySelector(this.getAttribute("href"));
				if (!targetElem.classList.contains("is-active")) {
					targetElem.classList.add("is-active");
				}
			}
		});
    }
	var display = document.querySelector(".cmnstr");
	display.className += " cmnstr-activated";
}