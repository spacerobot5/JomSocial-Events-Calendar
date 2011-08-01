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

/* Include Joomla configuration */
	require_once('../../configuration.php');
	$jconfig = new JConfig();
	//db establish
	$db_error = "Sorry, we are unable to connect to the database at this time. Please try again later.";
	$db_config = mysql_connect( $jconfig->host, $jconfig->user, $jconfig->password ) or die( $db_error );
	mysql_select_db( $jconfig->db, $db_config ) or die( $db_error );
	mysql_set_charset('utf8'); 
	$start = date('Y-m-d H:i:s', mysql_real_escape_string($_GET['start']));
	$end = date('Y-m-d H:i:s', mysql_real_escape_string($_GET['end']));
	$events ='';
	//db get params
	$query = "SELECT params FROM ".$jconfig->dbprefix."modules WHERE module = 'mod_jscalendar'";
	$newresult = mysql_query($query);
	//parse params
	$row = mysql_fetch_array($newresult);
	parse_str(str_replace("\n", "&", $row[0]), $params);
	//limit to public events?
	if($params[eventPublic]){
		$publicEvent = 'AND permission = 0';
	}
	else{
		$publicEvent = '';
	}
	//limit by category id?
	if($params[eventCatids] != NULL){
		$params[eventCatids] = str_replace (' ', '', $params[eventCatids]);
		$catids = explode(",", $params[eventCatids]);
		foreach($catids as $key => $value) {
			if($value == "") {
				unset($catids[$key]);
			}
		}
		foreach ($catids as $catid){
			$catLimit = $catLimit.'catid = '.$catid.' OR ';
		}
		$catLimit = 'AND ('.$catLimit.')';
		$catLimit = str_replace (' OR )', ') AND', $catLimit);
	} else{
		$catLimit = '';
	}
	//show past events?
	if($params[pastEvents]){
		$expiredEvents = '';
	}
	else{
		$expiredEvents = 'AND enddate > CURDATE()';
	}
	
	//db get events
	$query = "SELECT id,catid,title,location,description,startdate,enddate FROM ".$jconfig->dbprefix."community_events WHERE published = 1 $catLimit $publicEvent AND ((startdate > '$start' AND startdate < '$end') OR (enddate > '$start' AND enddate < '$end') OR (startdate < '$start' AND enddate > '$end')) $expiredEvents";
	$result = mysql_query($query);
	while(($resultArray[] = mysql_fetch_assoc($result)) || array_pop($resultArray));
	//close db for security reason
	mysql_close($db_config);
	//begin parsing into json feed
	foreach ($resultArray as $event){
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
		//perform lame SEF formatting... or not
		if($params[sefOption]){
			$eventLink = str_replace ('[id]', $event["id"], $params["sefFormat"]);
		} else{
			$eventLink = '/index.php?option=com_community&view=events&task=viewevent&eventid='.$event["id"];
		}
		//compile json feed
		$events = $events.'{"id":'.$event["id"].',"title":"'.addslashes($event["title"]).'","start":"'.$event["startdate"].'","end":"'.$event["enddate"].'","allDay":false,"url":"'.$eventLink.'","description":"'.$text.'","location":"'.$event["location"].'","className":"calendar'.$event["catid"].'"},';
	}
	$events = '['.$events.']';
	$events = str_replace (',]', ']', $events);
	print_r($events);
?>
