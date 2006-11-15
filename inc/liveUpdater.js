// autocomplete and related changes
// Copyright 2004 Leslie A. Hensley
// hensleyl@papermountain.org
// you have a license to do what ever you like with this code
// orginally from Avai Bryant 
// http://www.cincomsmalltalk.com/userblogs/avi/blogView?entry=3268075684

if (navigator.userAgent.indexOf("Safari") > 0)
{
  isSafari = true;
  isMoz = false;
  isIE = false;
}
else if (navigator.product == "Gecko")
{
  isSafari = false;
  isMoz = true;
  isIE = false;
}
else
{
  isSafari = false;
  isMoz = false;
  isIE = true;
}
    
function liveUpdaterUri(id, uri)
{
    function constructUri()
    {
        var separator = "?";
        if(uri.indexOf("?") >= 0)
            separator = "&";
        return uri + separator + "s=" + escape(document.getElementById(id).innerHTML);
    }
    return liveUpdater(constructUri, function () {});
}

function liveUpdaterUriFunc(id, uri, postFunc, preFunc)
{
    function constructUri()
    {
        var separator = "?";
        if(uri.indexOf("?") >= 0)
            separator = "&";
        return uri + separator + "s=" + escape(document.getElementById(id).innerHTML);
    }
    return createLiveUpdaterFunction(constructUri, postFunc, preFunc);
}


/*
  liveUpdater returns the live update function to use
  uriFunc: The function to generate the uri
  postFunc: <optional> Function to run after processing is complete
  preFunc: <option> Function to run before processing starts
*/
function liveUpdater(uriFunc, postFunc, preFunc)
{
  if(!postFunc) postFunc = function () {};
  if(!preFunc) preFunc = function () {};

  return createLiveUpdaterFunction(uriFunc, postFunc, preFunc);
}

function recreateTBODY(parentElement, subtree) {
  for(var i = parentElement.childNodes.length-1; i>=0; i--)
  {
    parentElement.removeChild(parentElement.childNodes[i]);
  }

  for(var i=0; i<subtree.childNodes.length; i++)
  {
    var row = document.createElement(subtree.childNodes[i].nodeName);
    copyAttributes(subtree.childNodes[i], row);
    recreateTR(row,subtree.childNodes[i]);
    parentElement.appendChild(row);
  }
}

function recreateTR(parentElement, subtree)
{
  for(var i = parentElement.childNodes.length-1; i>=0; i--)
  {
    parentElement.removeChild(parentElement.childNodes[i]);
  }

  for(var i=0; i<subtree.childNodes.length; i++)
  {
    var cell = document.createElement(subtree.childNodes[i].nodeName);
    copyAttributes(subtree.childNodes[i], cell);
    cell.innerHTML = flattenChildren(subtree.childNodes[i].childNodes)
    parentElement.appendChild(cell);
  }
}

function createLiveUpdaterFunction(uriFunc, postFunc, preFunc)
{
    var request = false;
    if (window.XMLHttpRequest) {
        request = new XMLHttpRequest();
    }
    
    function update()
    {
        if(request && request.readyState < 4)
            request.abort();

            
        if(!window.XMLHttpRequest)
            request = new ActiveXObject("Microsoft.XMLHTTP");

        preFunc();
        request.onreadystatechange = processRequestChange;
        request.open("GET", uriFunc());
        request.send(null);
        return true;
    }

	function processRequestChange()
	{
		if(request.readyState == 4) {
			var xmlDoc = request.responseXML;
			var body = xmlDoc.getElementsByTagName("body");

			if(body.length > 0) {
				var nodes = body[0].childNodes;

				for(var i=0; i < nodes.length; i++) {
					if(nodes[i].nodeType == 1 && nodes[i].getAttribute("id") != null) {
						var id = nodes[i].getAttribute("id");

						if (id == 'autocomplete-org-popup') {
							// inputField.className = '';
							var truc;
							truc = document.getElementById("orgsearchkey");
							truc.className = '';
						} else if (id == 'autocomplete-client-popup') {
							// inputField.className = '';
							var truc;
							truc = document.getElementById("clientsearchkey");
							truc.className = '';
						} else {
							// inputField.className = '';
							var truc;
							truc = document.getElementById("casesearchkey");
							truc.className = '';
						}

						if(isIE && nodes[i].nodeName == 'tr') {
							recreateTR(document.getElementById(id), nodes[i]);
						} else if(isIE && nodes[i].nodeName == 'tbody') {
							recreateTBODY(document.getElementById(id), nodes[i]); 
						} else {
							document.getElementById(id).innerHTML = flattenChildren(nodes[i].childNodes)
						}
					}
				}
			} else {
				// document.getElementById(id).innerHTML = request.responseText;
				alert('text = ' + request.responseText);
			}

			//evalScripts(xmlDoc);
			var scripts = xmlDoc.getElementsByTagName("script");
			for(var i = 0; i < scripts.length; i++) {
				if(scripts[i].firstChild!=null) {
					var script = scripts[i].firstChild.nodeValue
						if(script != null) {
							eval(script)
						}
				}
			}
        
			postFunc();
		}
	}

	return update;
}

function evalScripts(node) {
	for(var i=0;i<node.childNodes.length;i++) {
		if(node.childNodes[i].tagName == "script") {
			if(node.childNodes[i].firstChild!=null) {
				var script = node.childNodes[i].firstChild.nodeValue
  	    if(script != null) {
					eval(script);
      	}
      }
		} else {
			evalScripts(node.childNodes[i]);
		}
	}
}

/*
  id: id of the element doing the search
  uri: URI the live search makes a call to results
  field_event_type: event in which the live search is fired
  preFunc: <option> Function to run before processing starts
  postFunc: <optional> Function to run after processing is complete
*/
function liveSearch(id, uri, field_event_type, preFunc, postFunc)
{
    function constructUri()
    {
      //TODO this needs to be refactored, possibly a type => value_getter()
      var elementValue;
      if( document.getElementById(id).type == 'checkbox' && !document.getElementById(id).checked)
      {
        // I tried to get the default value for the checbox that is in the hidden, but there is no direct coorelation
        elementValue = "false";
      }
      else if(document.getElementById(id).type == 'select-multiple')
      {
        var buffer = "";
        var aSelect = document.getElementById(id);
        for(var i=0; i<aSelect.options.length; i++)
        {
          if(aSelect.options[i].selected)
          {
            buffer = buffer + aSelect.options[i].value + "|";
          }
        }
        elementValue = escape(buffer);
      }
      else
      {
        elementValue = escape(document.getElementById(id).value);
      }

      var separator = "?";
      if(uri.indexOf("?") >= 0)
          separator = "&";
      return uri + separator + "s=" + elementValue + "&z=" + new Date().getTime();
    }

    var updater = liveUpdater(constructUri, postFunc, preFunc);
    var timeout = false;
        
    function start() {
     if (timeout)
         window.clearTimeout(timeout);
     timeout = window.setTimeout(updater, 300);
    }

  if(field_event_type == 'CHANGE')
  {
    addListener(document.getElementById(id), 'change', updater);
  }
  else if(field_event_type == 'CLICK')
  {
    addListener(document.getElementById(id), 'click', updater);
  }
  else if(field_event_type == 'BLUR')
  {
    addListener(document.getElementById(id), 'blur', updater);
  }
  else
  {
    addKeyListener(document.getElementById(id), start);
  }
}

/*
  id: id of the form
  uri: URI the live form makes a get request to (POST->GET conversion)
*/
function liveForm(id, uri) {
    var form = document.getElementById(id);
    var submit;
		
    function constructUri() {
			var keyvalues = Array();
     	for(var j=0; j< form.elements.length; j++) {
     		var element = form.elements[j];
	      var elementValue;
	      if( element.type == 'checkbox' && !element.checked) {
	        elementValue = "false";
  	    } else if(element.type == 'select-multiple') {
		      var buffer = "";
    	    for(var i=0; i<element.options.length; i++) {
        	  if(element.options[i].selected) {
          	  buffer = buffer + element.options[i].value + "|";
          	}
        	}
        	elementValue = escape(buffer);
	      } else {
  	    	elementValue = escape(element.value);
    	  }
    	  if(element.type != 'submit') {
	    	  keyvalues.push(element.name+"="+elementValue);
	    	}
     	}
			keyvalues.push(submit.name+"="+submit.value);
      var separator = "?";
      if(uri.indexOf("?") >= 0)
          separator = "&";
      return uri + separator + "s=" + submit.value + "&" + keyvalues.join("&") + "&z=" + new Date().getTime();
    }

    var updater = liveUpdater(constructUri); 

    /* see http://joust.kano.net/weblog/archive/2005/08/08/a-huge-gotcha-with-javascript-closures/ */
    function createListener(element) {
      return function(){submit = element; updater();}
    }

    for(var i=0;i<form.elements.length;i++) {
    	var element = form.elements[i];
    	if(element.type == 'submit') {
		    addListener(element , 'click', createListener(element));
		  }
	  }
}

function autocomplete(id, popupId, uri, popupData, hideAlt)
{
    var inputField = document.getElementById(id);
    var popup      = document.getElementById(popupId);
	var dataField  = document.getElementById(popupData); // [ML]
	var altField   = document.getElementById(hideAlt); // [ML]
    var options = new Array(); 
    var current = 0;
    var originalPopupTop = popup.offsetTop; 

    function constructUri()
    {
        var separator = "?";
		var action = "find_name_client";

        if(uri.indexOf("?") >= 0)
            separator = "&";

		if (id == "clientsearchkey")
			action = "find_name_client";
		else if (id == "orgsearchkey")
			action = "find_name_org";
		else if (id == "casesearchkey")
			action = "find_name_case";
			
        return uri + separator + action + "=" + (inputField.value); /* [ML] removed escape() for cyrillic ?? */
    }
   
    function hidePopup()
    {
      popup.style.visibility = 'hidden';
    }

    function handlePopupOver()
    {
      removeListener(inputField, 'blur', hidePopup);
    }
    
    function handlePopupOut()
    {
      if(popup.style.visibility == 'visible')
      {
        addListener(inputField, 'blur', hidePopup);
      }
    }
    
    function handleClick(e)
    {
	  	var foo = eventElement(e).innerHTML.split(': ');
		var action = "id_client";

        popup.style.visibility = 'hidden';
        inputField.focus();

		if (foo[0] > 0) {
			inputField.value = foo[1];
			altField.style.display = 'none'; // [ML] 

			// [ML] experiments
			function updateClient()
			{
				if (xmlHttp1.readyState == 4) {
					var response = xmlHttp1.responseText;
					dataField.innerHTML = response;
					document.getElementById('input_case_title').value = inputField.value;
				}
			}

			if (id == 'clientsearchkey')
				action = 'id_client';
			else if (id == 'orgsearchkey')
				action = 'id_org';
			else if (id == 'casesearchkey')
				action = 'id_case';

			xmlHttp1 = new XMLHttpRequest();
			xmlHttp1.open('GET', 'ajax.php?' + action + '=' + foo[0], true);
			xmlHttp1.onreadystatechange = updateClient;
			xmlHttp1.send(null);
		}

		popup.style.visibility = 'hidden';
		inputField.focus();
    }
    
    function handleOver(e)
    {
      options[current].className = '';
      current = eventElement(e).index;
      options[current].className = 'selected';
    }
    
    function post()
    {
        current = 0;
        options = popup.getElementsByTagName("li");
        if((options.length > 1)
           || (options.length == 1 
               && options[0].innerHTML != inputField.value))
        {
          setPopupStyles();
          for(var i = 0; i < options.length; i++)
          {
            options[i].index = i;
            addOptionHandlers(options[i]);
          }
          options[0].className = 'selected';
        }
        else
        {
          popup.style.visibility = 'hidden';
        }
    }
  
    function setPopupStyles()
    {
      var maxHeight
      if(isIE)
      {
        maxHeight = 200;
        popup.style.left = '0px';
        popup.style.top = (originalPopupTop + inputField.offsetHeight) + 'px';
      }
      else
      {
        maxHeight = window.outerHeight/3;
      }
      if(popup.offsetHeight < maxHeight)
      {
        popup.style.overflow = 'hidden';
      }
      else if(isMoz)
      {
        popup.style.maxHeight = maxHeight + 'px';
        popup.style.overflow = '-moz-scrollbars-vertical';
      }
      else
      {
        popup.style.height = maxHeight + 'px';
        popup.style.overflowY = 'auto';
      }
      popup.scrollTop = 0;
      popup.style.visibility = 'visible';
    }
    
    function addOptionHandlers(option)
    {
      addListener(option, "click", handleClick);
      addListener(option, "mouseover", handleOver);
    }
    
    var updater = liveUpdater(constructUri, post);
    var timeout = false;
   
	function start(e) {
		if (timeout)
			window.clearTimeout(timeout);
		//up arrow
		if(e.keyCode == 38)
		{
			if(current > 0)
			{
				options[current].className = '';
				current--;
				options[current].className = 'selected';
				options[current].scrollIntoView(false);
			}
		}
		//down arrow
		else if(e.keyCode == 40)
		{
			if(current < options.length - 1)
			{
				options[current].className = '';
				current++;
				options[current].className = 'selected';
				options[current].scrollIntoView(false);
			}
		}
		//enter or tab
		else if((e.keyCode == 13 || e.keyCode == 9) && popup.style.visibility == 'visible')
		{
			var foo = options[current].innerHTML.split(': ');
			var action = "id_client";

			// [ML] This is redundant with lines ~ 350
			popup.style.visibility = 'hidden';

			if (foo[0] > 0) {
				inputField.value = foo[1];
				altField.style.display = 'none'; // [ML]

				// [ML] experiments
				function updateClient2()
				{
					if (xmlHttp1.readyState == 4) {
						var response = xmlHttp1.responseText;
						dataField.innerHTML = response;
						document.getElementById('input_case_title').value = inputField.value;
					}
				}

				if (id == 'clientsearchkey')
					action = 'id_client';
				else if (id == 'orgsearchkey') 
					action = 'id_org';
				else if (id == 'casesearchkey')
					action = 'id_case';

				xmlHttp1 = new XMLHttpRequest();
				xmlHttp1.open('GET', 'ajax.php?test=1&' + action + '=' + foo[0], true);
				xmlHttp1.onreadystatechange = updateClient2;
				xmlHttp1.send(null);
			}

			if(isIE) {
				event.returnValue = false;
			} else {
				e.preventDefault();
			}
		} else {
			inputField.className = 'ac_loading'; // [ML]
			timeout = window.setTimeout(updater, 300);
		}
	}

	addKeyListener(inputField, start);
	addListener(popup, 'mouseover', handlePopupOver);
	addListener(popup, 'mouseout', handlePopupOut);
}

/* Functions to handle browser incompatibilites */
function eventElement(event)
{
  if(isMoz)
  {
    return event.currentTarget;
  }
  else
  {
    return event.srcElement;
  }
}

function addKeyListener(element, listener)
{
  if (isSafari)
    element.addEventListener("keydown",listener,false);
  else if (isMoz)
    element.addEventListener("keypress",listener,false);
  else
    element.attachEvent("onkeydown",listener);
}

function addListener(element, type, listener)
{
  if(element.addEventListener)
  {
    element.addEventListener(type, listener, false);
  }
  else
  {
    element.attachEvent('on' + type, listener);
  }
}

function removeListener(element, type, listener)
{
  if(element.removeEventListener)
  {
    element.removeEventListener(type, listener, false);
  }
  else
  {
    element.detachEvent('on' + type, listener);
  }
}

/* XML Helper functions */
function flatten(node)
{
	if(node.nodeType == 1)
	{
		return '<' + node.nodeName + flattenAttributes(node) + '>' +
		flattenChildren(node.childNodes) + '</' + node.nodeName + '>';
	}
	else if(node.nodeType == 3)
	{
		return node.nodeValue;
	}
}

function flattenAttributes(node)
{
  var buffer = ''
  for(var i=0;i<node.attributes.length;i++)
  {
    var attribute = node.attributes[i];
    buffer += ' '+attribute.name+'="'+attribute.value+'"'
  }
  return buffer;
}

function flattenChildren(nodes)
{
	var buffer = '';
	if(nodes.length > 0)
	{
		for (var i=0;i<nodes.length;i++)
		{
			buffer += flatten(nodes[i]);
		}
	}
	return buffer;
}

function copyAttributes(source, destination)
{
  for(var i=0;i<source.attributes.length;i++)
  {
    var attribute = source.attributes[i];
    if(attribute.name=="colspan") {
    	destination.colSpan = attribute.value;
    } else {
	    destination.setAttribute(attribute.name, attribute.value);
	  }
  }
  destination.className = source.getAttribute('class');
}

/* [ML] Functions for other more boring stuff */
function getCaseInfo(id_case)
{
    function constructUri() 
	{
        return 'ajax.php?id_case=' + id_case;
    }

    var updater = liveUpdater(constructUri);
    var timeout = false;

	updater();
}

function getKeywordInfo(action, group_name, type_obj, id_obj, id_obj_sec, div)
{
    function constructUri() 
	{
        return 'ajax.php?action=' + action + '&group_name=' + group_name 
			+ '&type_obj=' + type_obj + '&id_obj=' + id_obj + '&id_obj_sec' + id_obj_sec
			+ '&div=' + div;
    }

    var updater = liveUpdater(constructUri);
    var timeout = false;

	updater();
}


// [ML] This was in ss_switcher.js, moved to here to combine with liveUpdater
// ----------------------------------------------
// StyleSwitcher functions written by Paul Sowden
// http://www.idontsmoke.co.uk/ss/
// - - - - - - - - - - - - - - - - - - - - - - -
// For the details, visit ALA:
// http://www.alistapart.com/stories/alternate/

function setActiveStyleSheet(title)
{
    var i, a, main;

    function constructUri() 
	{
        return 'ajax.php?author_ui_modified=1&action=changefont' + '&font_size=' + title;
    }

    var updater = liveUpdater(constructUri);
    var timeout = false;

    for(i=0; (a = document.getElementsByTagName("link")[i]); i++)
    {
	    if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title"))
	    {
        	    a.disabled = true;
        	    if(a.getAttribute("title") == title) { a.disabled = false; }
	    }
    }

	updater();
}

