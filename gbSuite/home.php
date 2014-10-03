<?
/*
 * Created on 22/07/2008
 * 
 * Created by Joel.
 *
 * This is the main file of gbSuite, 
 * This script loads the data of the user logged in.
 * 
 */
 session_start();
include_once($_SERVER['PHP_ROOT']."/gbSuite/util/connection.php");
include_once($_SERVER['PHP_ROOT']."/gbSuite/util/online_user_util.php");
include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
	
	 
 /*Aqui es para colocar la cookie del remember login*/
 if($_GET['remember'] == "true"){
 	 	
 	$con=new Connection();
 	$sql_query_user="select email, password from info where uid='".$_SESSION['uid']."'";
 	
 	$resultado=$con->get_row($sql_query_user); 	
 	 	
 	@setcookie("cookname", $resultado[0], time()+60*60*24*2, "/");
    @setcookie(md5("cookpass"),md5($resultado[1]) , time()+60*60*24*2, "/");
    
}
 
if($_GET['app']=='profile'&& isset($_GET['uid']) && $_SESSION['uid']!=$_GET['uid']){		
	
	$user =new User( null, $_SESSION['uid'],new Connection());
		
	if($user->getTitle()=='Salesperson')
	{
		$user =new User( null, $_GET['uid'],new Connection());
			if($user->getTitle()!="Salesperson"){			
			header("location:/gbSuite/home.php");
			
		}
		
	}
}
	
	
 if(isset($_GET['uid']) && $_GET['app'] == "login")
 {
 	$_SESSION['uid'] = $_GET['uid'];

 	$con=new Connection();
 	$sql_query_user="select email, password from info where uid='".$_SESSION['uid']."'";
 	
 	$resultado=$con->get_row($sql_query_user); 	
 
 	//save_login($_SESSION['uid'],$resultado[0],$resultado[1]);
 	 		
	if($_GET['remember'] == "true")
	 {
	    $remember = "?remember=true";
	 }
 	header("location:/gbSuite/home.php$remember");
 	exit(); 
 }
 else
 {
 	if(!isset($_SESSION['uid']))
 	{
 		header("location:/gbSuite/login.php"); 
 		exit();
 	}
 }
 
 /*
 Here we do the online user update
 */
 if(isset($_SESSION['uid'])){
 	setCurrentUserOnline(new Connection()); 		 	 	
 }
 //print_r(countUserOnline(new Connection()));
 $params = "";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>gbSuite Application</title>

<link rel="stylesheet" type="text/css" href="/css/style.css" />
<!--link rel="stylesheet" type="text/css" href="/css/inbox_style.css" /-->
<link rel="stylesheet" type="text/css" href="/css/inbox.css" />
<link rel="stylesheet" type="text/css" href="/css/edit_setting_style.css" />
<link rel="stylesheet" type="text/css" href="/css/authorization_style.css" />
<link rel="stylesheet" type="text/css" href="/css/air.css" />
<link rel="stylesheet" type="text/css" href="/css/leads_control.css" />
<link rel="stylesheet" type="text/css" href="/css/notifications.css" />
<link rel="stylesheet" type="text/css" href="/css/team_builder.css" />
<link rel="stylesheet" type="text/css" href="/css/desk_log.css" />
<link rel="stylesheet" type="text/css" href="/css/desktop_icon.css" />
<link rel="stylesheet" type="text/css" href="/css/date-picker.css" />
<link rel="stylesheet" href="/css/report.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="/css/reports_style.css" rel="stylesheet" type="text/css" />
<!--link rel="stylesheet" href="/css/coach_report.css" type="text/css" media="screen, print" charset="utf-8" /-->
<link rel="shortcut icon" href="/images/resources/layout.gif" />
<link rel="stylesheet" href="/css/about_app.css" type="text/css" media="screen" charset="utf-8" />
<!--[if lt IE 7]>
  <link rel="stylesheet" href="/css/ie.css" type="text/css" media="screen" charset="utf-8" />
<![endif]-->

<style>
	.visibleElement
	{
		display:block;
	}
	
	.visible
	{
		display:none;
	}
	
	.application, .application-title
	{
		position:relative;
	}
	
	.dragenter
	{
		border:dotted;
	}
	
	.dragleave
	{
		border:none;
	}
	
	/*.sortablelist
	{
		padding: 0 0 0 0;
		margin: 0 0 0 0;
	}*/
	
	li.sortelement
	{			
		padding: 0 0 0 0;
		margin: 0 0 0 0;
		/*cursor: pointer;*/
		list-style: none;
		position:relative;
		/*background-color: #222;*/
	}	
	
	ul.draglist 
	{ 
	    /*position: relative;	    
	    /*background: #f7f7f7;*/
	    /*border: 1px solid gray;*/
	    /*list-style: none;
	    margin:0;
	    padding:0;*/
	}
	
	ul.draglist li 
	{
	    /*margin: 1px;*/
	    /*cursor: move;*/ 
	}
	
	ul.draglist_alt 
	{ 
	    /*position: relative;*/	     
	    list-style: none;
	    margin:0;
	    padding:0;
	    /*
	       The bottom padding provides the cushion that makes the empty 
	       list targetable.  Alternatively, we could leave the padding 
	       off by default, adding it when we detect that the list is empty.
	    */
	}
		
	ul.draglist_alt li 
	{
	    /*margin: 1px;*/
	    cursor: hand; 
	}
	
	
	li.list1
	{
	    background-color: #D1E6EC;
	    border:1px solid #7EA6B2;
	}

	ul.draglist_alt 
	{ 
	    margin:0;
	    padding:0;
	    /*
	       The bottom padding provides the cushion that makes the empty 
	       list targetable.  Alternatively, we could leave the padding 
	       off by default, adding it when we detect that the list is empty.
	    */
	    padding-bottom:20px;
	}
	
	ul.draglist_alt li 
	{
	    margin: 1px;
	    cursor: hand; 
	}


	li.associates-list-item1 
	{
	    background-color: #D1E6EC;
	    border:1px solid #7EA6B2;
		cursor:hand;
	}

	li.associates-list-item2 
	{
	    background-color: #D8D4E2;
	    border:1px solid #6B4C86;
		cursor:hand;
	}	
</style>
</head>
<body >
<?php

 
 foreach($_GET as $key => $value)
 {
 	$params .= ($params != "" ? "&" : "")."$key=$value";
 }	
 
if((isset($_GET['ui']) && $_GET['ui'] == "true") || !isset($_GET['ui']))
{
	//If the uid comes by GET that means it wants to see
	$url = "app.php";
	
	$params .= ( isset($_GET['app']) ? ($params != ""? "&" : "?")."app=".$_GET['app'] : "");
	$params .= ( isset($_GET['uid']) ? ($params != ""? "&" : "?")."uid=".$_GET['uid'] : "");
	$params .= ( isset($_GET['action']) ? ($params != ""? "&" : "?")."action=".$_GET['action'] : "");
	
	 $_GET['fb_app_name'] = "gbSuite";
	 $_GET['fb_user_id'] = $_SESSION['uid'];	  
	 
	 /**The application to view in the main, just for now.**/ 
	 $_GET['fb_url_suffix'] = $url."?".$params;
 
 	include_once($_SERVER['PHP_ROOT']."/canvas.php");	
}
else
	if($_GET['ui'] == "false") /**If the process doesn't require a load of all the interface**/
	{
		$applicationName = "";
		$applicationsDirectory = "apps";
		
		if(isset($_GET['app']) && $_GET['app']  != "")
			$applicationName = $_GET['app'];
			
		$connection = new Connection();
	
		/**Read the information of the current user**/
		$query = "SELECT * FROM info WHERE uid = '".$_SESSION['uid']."' ";
		
		$row = $connection->get_row($query);
		
		$user = new User($row);
		
		$user->setConnection($connection);
		
		//This means the user information to display is from a friend of the current user.
		if(isset($_GET['uid']) && isset($_GET['fuid']))
		{
			$user->setFriendUID($_GET['fuid']);
			$user->setCurrentProfileUID($_GET['uid']);
		}			
		else				
			if(isset($_GET['uid']))
				$user->setFriendUID($_GET['uid']);
	
		/**Read the information of the application**/				
		$query = "SELECT * FROM apps WHERE name = '".$applicationName."'";
			
		$row = $connection->get_row($query);
		
		include_once($_SERVER['PHP_ROOT']."/gbSuite/".$applicationsDirectory."/".$row['file_name']."/".$row['file_name'].".php");
			
		$applicationClass = $row['class_name'];	
		$params = 'nothing';
		$application = new $applicationClass();
			
		$application->setAttributes($row);
			
		$application->setConnection($connection);
			
		$application->setUser($user);
	
		//The action must come in the url
		$function = $_GET['action'];
			
		if(isset($function))
			call_user_func(array(&$application, $function), $params);
	}
?>
<script type="text/javascript" src="<?=$YOUR_SERVER_URL;?>/js/yahoo/yahoo-dom-event.js"></script>
<script type="text/javascript" src="<?=$YOUR_SERVER_URL;?>/js/yahoo/animation-min.js"></script>
<script type="text/javascript" src="<?=$YOUR_SERVER_URL;?>/js/yahoo/dragdrop-min.js"></script><!--begin custom header content for this example-->

<script type="text/javascript" src="<?=$YOUR_SERVER_URL;?>/js/date-picker.js"></script>

<script type="text/javascript" src="<?=$YOUR_SERVER_URL;?>/js/mootools.js"></script>
<script type="text/javascript" src="<?=$YOUR_SERVER_URL;?>/js/dndApplication.js"></script>
<script>
	function a1234567_popUp(url) 
	{
		day = new Date();
		id = day.getTime();
		
		dialog = window.open(url ,id , 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=1,resizable=1,width=500,height=500,left = 390,top = 150');
		dialog.print();
	}
</script>
<script>
	//This script is for applications reorder
	$$('.application-list').each(function(list)
	{
		new Sortables(list,
		{ 
			initialize: function()
			{
				var step = 0;
				
				this.elements.each(function(element, i)
				{
					var color = [step, 82, 87].hsbToRgb();
					
					//element.setStyle('background-color', color);
					
					step = step + 35;
					
					//element.setStyle('height', $random(40, 100));						
				});
			},
			onComplete :function()			 
			{
				var position, appId, ordering;
				
				appId = "";
				
				position = list.id;
				
				position = position.replace('app1234567_application_list_', '');
				position = position.replace('-', '_');
				position = position.toUpperCase();
												
				appId = this.serialize(function(el) 
				{
					return el.id.replace('app1234567_application_list_item_', '');
				});
					
				a1234567_arrange_application_list(Ajax.JSON, '', appId, position);
			}
		});	
	});
	
//FOR ASSOCIATES PERMISSIONS
(function() 
{
var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;
var DDM = YAHOO.util.DragDropMgr;

YAHOO.example.DDApp = 
{
    init: function() 
	{
		new YAHOO.util.DDTarget("app1234567_team-builder-available");
		new YAHOO.util.DDTarget("app1234567_team-builder-member");
		
		var ul1 = Dom.get("app1234567_team-builder-available");
		var ul2 = Dom.get("app1234567_team-builder-member");
		
		var items = ul2.getElementsByTagName("li");
		
		for (var i = 0; i < items.length; i++) 
            new YAHOO.example.DDList(items[i]);
		
		items = ul1.getElementsByTagName("li");
		
		for (var i = 0; i < items.length; i++) 
            new YAHOO.example.DDList(items[i]);
    },

    showOrder: function() 
	{
        var parseList = function(ul, title) 
		{
            var items = ul.getElementsByTagName("li");
			
			var uids = "";
			
            for (i = 0; i < items.length; i= i + 1) 
			{
				if(items[i].id.contains('_mylistitem'))
				{
					uids +=  items[i].id.replace("_mylistitem", "").replace("app1234567_", "");
					
					if(i + 1 < items.length)
						uids += ",";
				}
            }
			
			if(uids.charAt(uids.length - 1) == ',')
				uids = uids.substring(0, uids.length - 2);

			return uids;
        };

		/*var ul2 = Dom.get("app1234567_associates-list2"); 
		var appId = document.getElementById('app1234567_application-id').getValue();
		
		var uids = parseList(ul2, "");
 		
		var type = document.getElementById('app1234567_visibility-list').getValue();
		if(type == 'Some associates')
			type = 'allowed';
		else
			type = 'denied';
			 		
		if(uids != "")				
			a1234567_saveApplicationAssociatePermission('allowed', appId, uids);*/
    },

    switchStyles: function() 
	{
        //Dom.get("app1234567_team-builder-available").className = "draglist_alt";
        //Dom.get("app1234567_team-builder-member").className = "draglist_alt";
    }
};

//////////////////////////////////////////////////////////////////////////////
// custom drag and drop implementation
//////////////////////////////////////////////////////////////////////////////

YAHOO.example.DDList = function(id, sGroup, config) 
{
    YAHOO.example.DDList.superclass.constructor.call(this, id, sGroup, config);

    //this.logger = this.logger || YAHOO;
    var el = this.getDragEl();
    //Dom.setStyle(el, "opacity", 0.67); // The proxy is slightly transparent

    this.goingUp = false;
    this.lastY = 0;
};

YAHOO.extend(YAHOO.example.DDList, YAHOO.util.DDProxy, 
{
    startDrag: function(x, y) 
	{
        //this.logger.log(this.id + " startDrag");

        // make the proxy look like the source element
        var dragEl = this.getDragEl();
        var clickEl = this.getEl();
		
        Dom.setStyle(clickEl, "visibility", "hidden");

        dragEl.innerHTML = clickEl.innerHTML;

        Dom.setStyle(dragEl, "color", Dom.getStyle(clickEl, "color"));
        Dom.setStyle(dragEl, "backgroundColor", Dom.getStyle(clickEl, "backgroundColor"));
        Dom.setStyle(dragEl, "font-size", "11px");
		Dom.setStyle(dragEl, "font-family", "Arial");
    },

    endDrag: function(e) 
	{
        var srcEl = this.getEl();
        var proxy = this.getDragEl();

        // Show the proxy element and animate it to the src element's location
        Dom.setStyle(proxy, "visibility", "");
		
        var a = new YAHOO.util.Motion( 
            proxy, { 
                points: { 
                    to: Dom.getXY(srcEl)
                }
            }, 
            0.2, 
            YAHOO.util.Easing.easeOut 
        )
        var proxyid = proxy.id;
        var thisid = this.id;

        // Hide the proxy and show the source element when finished with the animation
        a.onComplete.subscribe(function() 
		{
                Dom.setStyle(proxyid, "visibility", "hidden");
                Dom.setStyle(thisid, "visibility", "");
        });
        a.animate();
    },

    onDragDrop: function(e, id) 
	{
		// If there is one drop interaction, the li was dropped either on the list,
        // or it was dropped on the current location of the source element.
        if (DDM.interactionInfo.drop.length === 1) 
		{
            // The position of the cursor at the time of the drop (YAHOO.util.Point)
            var pt = DDM.interactionInfo.point; 

            // The region occupied by the source element at the time of the drop
            var region = DDM.interactionInfo.sourceRegion; 

            // Check to see if we are over the source element's location.  We will
            // append to the bottom of the list once we are sure it was a drop in
            // the negative space (the area of the list without any list items)
            if (!region.intersect(pt)) 
			{
                var destEl = Dom.get(id);
				
                var destDD = DDM.getDDById(id);
				
                destEl.appendChild(this.getEl());
				
                destDD.isEmpty = false;
				
                DDM.refreshCache();
            }
			
        }
				
		//YAHOO.example.DDApp.showOrder();
    },

    onDrag: function(e) 
	{

        // Keep track of the direction of the drag for use during onDragOver
        var y = Event.getPageY(e);

        if (y < this.lastY) {
            this.goingUp = true;
        } else if (y > this.lastY) {
            this.goingUp = false;
        }

        this.lastY = y;
    },

    onDragOver: function(e, id) {
    
        var srcEl = this.getEl();
        var destEl = Dom.get(id);

        // We are only concerned with list items, we ignore the dragover
        // notifications for the list.
        if (destEl.nodeName.toLowerCase() == "li") {
            var orig_p = srcEl.parentNode;
            var p = destEl.parentNode;

            if (this.goingUp) {
                p.insertBefore(srcEl, destEl); // insert above
            } else {
                p.insertBefore(srcEl, destEl.nextSibling); // insert below
            }

            DDM.refreshCache();
        }
    }
});

Event.onDOMReady(YAHOO.example.DDApp.init, YAHOO.example.DDApp, true);

})();
	
</script>
<script>
/*
 * Date Format 1.2.2
 * (c) 2007-2008 Steven Levithan <stevenlevithan.com>
 * MIT license
 * Includes enhancements by Scott Trenda <scott.trenda.net> and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */
var dateFormat = function () {
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) val = "0" + val;
			return val;
		};

	// Regexes and supporting functions are cached through closure
	return function (date, mask, utc) {
		var dF = dateFormat;

		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && (typeof date == "string" || date instanceof String) && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date();
		if (isNaN(date)) throw new SyntaxError("invalid date");

		mask = String(dF.masks[mask] || mask || dF.masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var	_ = utc ? "getUTC" : "get",
			d = date[_ + "Date"](),
			D = date[_ + "Day"](),
			m = date[_ + "Month"](),
			y = date[_ + "FullYear"](),
			H = date[_ + "Hours"](),
			M = date[_ + "Minutes"](),
			s = date[_ + "Seconds"](),
			L = date[_ + "Milliseconds"](),
			o = utc ? 0 : date.getTimezoneOffset(),
			flags = {
				d:    d,
				dd:   pad(d),
				ddd:  dF.i18n.dayNames[D],
				dddd: dF.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dF.i18n.monthNames[m],
				mmmm: dF.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
}();

// Some common format strings
dateFormat.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
	dayNames: [
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	monthNames: [
		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]
};

// For convenience...
Date.prototype.format = function (mask, utc) {
	return dateFormat(this, mask, utc);
}
	
var timeout = 2000;

showDate();
		
function showDate()
{
	var dateLabel = document.getElementById('app1234567_date-label');
	
	dateLabel.innerHTML = '<strong>' + dateFormat(new Date(), "dddd, mmmm d, yyyy, h:MM TT Z") + '</strong>';
	
	setTimeout('showDate()', timeout);
}

function checkUserStateOnline()
{	
	a1234567_checkUserState();
	setTimeout('checkUserStateOnline()',60000);
}

checkUserStateOnline();	


</script>	

<script type="text/javascript">
	var Tips1 = new Tips($$('.Tips1'));
	
</script>

<fb:js>
<script>
var elements_count=0;
function a1234567_setUserOnline(message, names)
{
	var menu_height=0, menu_top=0;
	
	position  = $('app1234567_associated-online').getPosition();
	
	var scroll_y=window.getScrollTop();
	var window_width=window.getSize();
	
	var element= $('online-associates');
	
	if(element.style.display=='none')
		element.style.display = "";
	else
		element.style.display='none';
	
	var html = "<table width='300px' class='online-user' cellpadding=0 cellspacing=0 >";
	html += "<tr><td class='online-title' colspan=2 >Associates</td></tr>";
	
	for(var i=0; i<message.length; i++)
	{
		if(i % 2 == 0)
		{
			html += "<tr class='online-user-row'><td class='online-state' align='center'><img src='/images/resources/online.gif' /></td>";
			html += "<td class='online-user-name'><a href='home.php?app=profile&uid="+message[i]+"' >"+names[i/2]+"</a></td></tr>";
		}
	}
	html += "</table>";
	element.innerHTML = html;
	elements_count = message.length;
	
	menu_height = (message.length / 2) * 21;	
	menu_top = scroll_y + position.y - menu_height;
	var element_width=element.style.width.replace("px","");
	
	if(this.ie)
	{
		add = 0;
	}
	else
	{
		add = 5;
	}
	
	//element.style.top=    (menu_top - (11 + add) )+'px';	
	element.style.left = window_width.size.x-element_width+ "px";		
}

window.addEvent("scroll",function ()
{
    var element= $('online-associates');

	if(element.style.display!='none')
	{
	var menu_height=0, menu_top=0;
	var position  = $('app1234567_associated-online').getPosition();
	var scroll_y=window.getScrollTop();
	var window_width=window.getSize();	
	
	menu_height=(elements_count)*21;	
	menu_top=scroll_y+position.y-menu_height;
	var element_width=element.style.width.replace("px","");
		
	
	if(this.ie)
	{
		add = 0;
	}
	else
	{
		add = 5;
	}
	
	//element.style.top= (menu_top + (10 - add )) +'px';
	//element.style.left = window_width.size.x-element_width+ "px";	
	}	

});
window.addEvent("resize",function ()
{
	 var element= $('online-associates');
	if(element.style.display!='none')
	{
	var menu_height=0, menu_top=0;
	var position  = $('app1234567_associated-online').getPosition();
	var scroll_y=window.getScrollTop();
	var window_width=window.getSize();	
	
	menu_height=(elements_count)*21;	
	menu_top=scroll_y+position.y-menu_height;
	var element_width=element.style.width.replace("px","");
		
	
	if(this.ie)
	{
		add = 0;
	}
	else
	{
		add = 5;
	}
	
	//element.style.top= (menu_top + (10 - add )) +'px';
	//element.style.left = window_width.size.x-element_width+ "px";	
	}
});


function a1234567_checkUncheckAll(){
	var mailform=document.getElementById("app1234567_mail-form");
	var master_checkbox=document.getElementById("app1234567_master-check");
	var urlId="";
	for(i=0; i<mailform.elements.length; i++){
		checkbox=mailform.elements[i];
		if(checkbox.type=='checkbox' ){
			checkbox.checked=!master_checkbox.checked;
		}
	}
	master_checkbox.checked=!master_checkbox.checked;
}



/*This function are use on Inbox Very importants*/
function a1234567_checkSelectedMail(){

var mailform=document.getElementById("app1234567_mail-form");
var urlId="";
for(i = 0 ; i < mailform.elements.length; i++){

		checkbox= mailform.elements[i];
								
			if( checkbox.tagName == "INPUT" && checkbox.type == 'checkbox' && checkbox.checked){
					
					urlId+=checkbox.value+",";
			}
	}
	if(urlId!=""){
		document.location="/gbSuite/home.php?app=inbox&action=delete&confirm=true&id="+urlId.substring(0,urlId.length-1);
	}
}
</script>
</fb:js>

<div id="app1234567_main-setting-div" style="		
			background-color:white;
			position:absolute;		
			align:center;
			overflow:visible;
			position:absolute;
			left:35%;
			top:20%;		
			z-index:100;
			display:none;">
	<div id="app1234567_application-setting-div"> 
		
	</div>
	<input type="button" value="Close" onclick="a1234567_closeDialog()" />
	<input type="button" value="Save" onclick="a1234567_saveApplicationSettings()" />
</div>
<script>
	function reload()
	{
		//document.location.reload();
	}
	
	//setTimeout('reload()', 60000);
</script>
</body>
</html>