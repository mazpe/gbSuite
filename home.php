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
include_once($_SERVER['PHP_ROOT']."/gbSuite/apps/user.php");
include_once $_SERVER['PHP_ROOT'].'/gbSuite/demo_libs/server_url.php';
	
 if(isset($_GET['uid']) && $_GET['app'] == "login")
 {
 	$_SESSION['uid'] = $_GET['uid'];
	
 	header("location:/gbSuite/home.php");
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
 
 $params = "";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>gbSuite Application</title>
<link rel="stylesheet" type="text/css" href="/css/style.css" />
<link rel="stylesheet" type="text/css" href="/css/inbox_style.css" />
<link rel="stylesheet" type="text/css" href="/css/edit_setting_style.css" />
<link rel="stylesheet" type="text/css" href="/css/authorization_style.css" />
<link rel="stylesheet" type="text/css" href="/css/air.css" />
<link rel="stylesheet" type="text/css" href="/css/leads_control.css" />
<link rel="stylesheet" type="text/css" href="/css/notifications.css" />
<link rel="stylesheet" type="text/css" href="/css/team_builder.css" />
<link rel="stylesheet" href="/css/report.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="/css/reports_style.css" rel="stylesheet" type="text/css" />
<link rel="shortcut icon" href="/images/resources/layout.gif" />
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

<script type="text/javascript" src="<?=$YOUR_SERVER_URL;?>/js/mootools.js"></script>
<script type="text/javascript" src="<?=$YOUR_SERVER_URL;?>/js/dndApplication.js"></script>
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
</body>
</html>