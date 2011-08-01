<?php
/***********************************************************************************
 * Events Calendar Module for JomSocial
 * version:	1.3
 * author: Andrew Peeling
 * copyright (C) 2011 Andrew Peeling. All rights reserved.
 * license: GNU/GPL http://www.gnu.org/copyleft/gpl.html

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
 **********************************************************************************/

// no direct access
defined('_JEXEC') or die('Restricted access');

class modJScalendarHelper {
	function getCalendarEvents($params) {
	// GET PARAMETERS	
		$calendarJquery = $params->get('calendarJquery');
		$calendarUI = $params->get('calendarUI');
		$headerLeft = $params->get('headerLeft', 'prev,next today');
		$headerCenter = $params->get('headerCenter', 'title');
		$headerRight = $params->get('headerRight', 'month,agendaWeek,agendaDay');
		$textAllday = $params->get('textAllday', 'all-day');
		$textToday = $params->get('textToday', 'today');
		$textMonth = $params->get('textMonth', 'month');
		$textWeek = $params->get('textWeek', 'week');
		$textDay = $params->get('textDay', 'day');
		$monthNames = $params->get('monthNames', 'January,February,March,April,May,June,July,August,September,October,November,December');
		$monthNamesShort = $params->get('monthNamesShort', 'Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec');		
		$dayNames = $params->get('dayNames', 'Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday');		
		$dayNamesShort = $params->get('dayNamesShort', 'Sun,Mon,Tue,Wed,Thu,Fri,Sat');
		$timeFormat = $params->get('timeFormat', 'h:mm tt');
		$themeOption = $params->get('themeOption');
		$themeCSS = $params->get('themeCSS');
		$firstDay = $params->get('firstDay', 0);
		$useAjax = $params->get('useAjax', 0);
		$useCache = $params->get('useCache', 0);
		$userEvents = $params->get('userEvents', 0);
		$userEvents = 1;

	// Get Doc, Database and User Id 
		$db = &JFactory::getDBO();
		$doc = &JFactory::getDocument();
		if ($userEvents){
			require_once( JPATH_ROOT.DS.'components'.DS.'com_community'.DS.'libraries'.DS.'core.php');	
			$user = CFactory::getUser();
			$userid = $user->_userid;
		}
		
		$site = rtrim(JURI::base(), '/');
	// include CSS files
		$doc->addStyleSheet($site.'/modules/mod_jscalendar/tmpl/jquery.cluetip.css');
		$doc->addStyleSheet($site.'/modules/mod_jscalendar/tmpl/fullcalendar.css');	

	// include themeroller css
		if($themeOption){
			$doc->addStyleSheet($themeCSS);			
			$theme = 'true';
		}
		else{
			$theme = 'false';	
		}
	// include jQuery
		if($calendarJquery){
			$scripts = array_keys($doc->_scripts);
			$alreadyAdded = false;
			foreach($scripts as $script) {
				if (strpos($script, '://ajax.googleapis.com/ajax/libs/jquery/') !== false) {
						$alreadyAdded = true;
				}
			}
			if (!$alreadyAdded) {
				$doc->addScript( 'http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js' );
			}
		}
		// include jQuery UI
		if($calendarUI){
			$scripts = array_keys($doc->_scripts);
			$alreadyAdded = false;
			foreach($scripts as $script) {
				if (strpos($script, '://ajax.googleapis.com/ajax/libs/jqueryui/') !== false) {
						$alreadyAdded = true;
				}
			}
			if (!$alreadyAdded) {
				$doc->addScript( 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/jquery-ui.min.js' );
			}
		}
		// include javascript files
		$doc->addScript($site.'/modules/mod_jscalendar/tmpl/fullcalendar.min.js' );
		$doc->addScript($site.'/modules/mod_jscalendar/tmpl/jquery.cluetip.js' );
		$doc->addScript($site.'/modules/mod_jscalendar/tmpl/jquery.hoverIntent.minified.js' );
		// parse month, day names into an array						
		$monthFull  = explode(",", $monthNames);
		$monthShort  = explode(",", $monthNamesShort);	
		$dayFull  = explode(",", $dayNames);
		$dayShort  = explode(",", $dayNamesShort);	
		
		if($useAjax){
			if($userEvents && $userid != 0){
				$justData = ", data: {case: '".md5($user->_userid)."'}";
			}
			else{
				$justData = '';
			}
			if($useCache){
				$events = "{url: '".$site."/modules/mod_jscalendar/events.php', cache: true".$justData."}";
			}
			else{
				$events = "{url: '".$site."/modules/mod_jscalendar/events.php'".$justData."}";
			}
		}
		else{
			$events = '';
		//get rest of params
		$eventPublic = $params->get('eventPublic', 0);
		$eventCatids = $params->get( 'eventCatids');
		$pastEvents = $params->get( 'pastEvents', 0);		

			//limit to public events?
			if($eventPublic){
				$publicEvent = 'AND permission = 0';
			}
			else{
				$publicEvent = '';
			}
			//limit by categories
			if($eventCatids != NULL){
				$eventCatids = str_replace (' ', '', $eventCatids);
				$catids = explode(",", $eventCatids);
				foreach($catids as $key => $value) {
					if($value == "") {
					unset($catids[$key]);
					}
				}
				foreach ($catids as $catid){
					$catLimit = $catLimit.'catid = '.$catid.' OR ';
				}
				$catLimit = 'AND ('.$catLimit.')';
				$catLimit = str_replace (' OR )', ')', $catLimit);
				} else{
					$catLimit = '';
				}
			//ditch expired events?
			if($pastEvents){
				$expiredEvents = '';
			}
			else{
				$expiredEvents = 'AND enddate > CURDATE()';
			}
			//time to make the donuts
			$query = "SELECT id,catid,title,location,description,startdate,enddate FROM #__community_events WHERE published = 1 $catLimit $publicEvent $expiredEvents ORDER BY id DESC LIMIT 0, 999;";
			$db->setQuery($query);
			$eventList = $db->loadAssocList();
			//parse the events
			foreach ($eventList as $event){
				//truncate description text
				$text = $event["description"];
				$text = strip_tags($text);
				$text = $text." ";
				$text = htmlspecialchars($text);
				$text = str_replace("\n", " ", $text);
				$text = str_replace("\r", " ", $text);
				$text = substr($text,0, 135);
				$text = substr($text,0,strrpos($text,' '));
				$text = $text."...";
				//create event link
				$eventLink = CRoute::_('index.php?option=com_community&view=events&task=viewevent&eventid='.$event["id"]);
				//compile json feed
				$events = $events.'{"id":'.$event["id"].',"title":"'.addslashes($event["title"]).'","start":"'.$event["startdate"].'","end":"'.$event["enddate"].'","allDay":false,"url":"'.$eventLink.'","description":"'.$text.'","location":"'.$event["location"].'","className":"calendar'.$event["catid"].'"},';
			}
			$events = '['.$events.']';
			$events = str_replace (',]', ']', $events);
		}
		
		// create calendar javascript
		$doc->addScriptDeclaration("
jQuery.noConflict(); jQuery(document).ready(function() {
		jQuery('#calendar').fullCalendar({
			header: {
					left: '".$headerLeft."',
					center: '".$headerCenter."',
					right: '".$headerRight."'
			},
			allDayText: '".$textAllday."',
			firstDay: ".$firstDay.",
			buttonText: {
						today: '".$textToday."',
						month: '".$textMonth."',
						week: '".$textWeek."',
						day: '".$textDay."'
			},
			theme: ".$theme.",
			monthNames: ['".$monthFull[0]."', '".$monthFull[1]."', '".$monthFull[2]."', '".$monthFull[3]."', '".$monthFull[4]."', '".$monthFull[5]."', '".$monthFull[6]."', '".$monthFull[7]."', '".$monthFull[8]."', '".$monthFull[9]."', '".$monthFull[10]."', '".$monthFull[11]."'],
			monthNamesShort: ['".$monthShort[0]."', '".$monthShort[1]."', '".$monthShort[2]."', '".$monthShort[3]."', '".$monthShort[4]."', '".$monthShort[5]."', '".$monthShort[6]."', '".$monthShort[7]."', '".$monthShort[8]."', '".$monthShort[9]."', '".$monthShort[10]."', '".$monthShort[11]."'],
			dayNames: ['".$dayFull[0]."', '".$dayFull[1]."', '".$dayFull[2]."', '".$dayFull[3]."', '".$dayFull[4]."', '".$dayFull[5]."', '".$dayFull[6]."'],
			dayNamesShort: ['".$dayShort[0]."', '".$dayShort[1]."', '".$dayShort[2]."', '".$dayShort[3]."', '".$dayShort[4]."', '".$dayShort[5]."', '".$dayShort[6]."'],			
			timeFormat: '".$timeFormat."',
			events: ".$events.",
			allDayDefault : 'false',
			loading: function(bool) {
				if (bool) jQuery('#loading').show();
				else jQuery('#loading').hide();
			},
			eventRender: function(event, element) {
	        element.cluetip({hoverIntent: {sensitivity: 7, interval: 50, timeout: 0}, cluetipClass: 'rounded', tracking: true, splitTitle: '|', showTitle: false});}});
	});
");

echo "<div id='calendar'></div>";
echo "<div id='loading' style='position:absolute;display:none'>loading...</div>";
}
}