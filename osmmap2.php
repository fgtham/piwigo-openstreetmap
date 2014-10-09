<?php
/***********************************************
* File      :   osmmap2.php
* Project   :   piwigo-openstreetmap
* Descr     :   Display a world map
*
* Created   :   28.05.2013
*
* Copyright 2013-2014 <xbgmsharp@gmail.com>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
************************************************/

if ( !defined('PHPWG_ROOT_PATH') )
  define('PHPWG_ROOT_PATH','../../');

include_once( PHPWG_ROOT_PATH.'include/common.inc.php' );
include_once( PHPWG_ROOT_PATH.'admin/include/functions.php' );
include_once( dirname(__FILE__) .'/include/functions.php');
include_once( dirname(__FILE__) .'/include/functions_map.php');

check_status(ACCESS_GUEST);

osm_load_language();
load_language('plugin.lang', OSM_PATH);

$section = '';
if ( $conf['question_mark_in_urls']==false and isset($_SERVER["PATH_INFO"]) and !empty($_SERVER["PATH_INFO"]) )
{
	$section = $_SERVER["PATH_INFO"];
	$section = str_replace('//', '/', $section);
	$path_count = count( explode('/', $section) );
	$page['root_path'] = PHPWG_ROOT_PATH.str_repeat('../', $path_count-1);
	if ( strncmp($page['root_path'], './', 2) == 0 )
	{
		$page['root_path'] = substr($page['root_path'], 2);
	}
}
else
{
	foreach ($_GET as $key=>$value)
	{
		if (!strlen($value)) $section=$key;
		break;
	}
}

// deleting first "/" if displayed
$tokens = explode('/', preg_replace('#^/#', '', $section));
$next_token = 0;
$result = osm_parse_map_data_url($tokens, $next_token);
$page = array_merge( $page, $result );


if (isset($page['category']))
	check_restrictions($page['category']['id']);

$local_conf = array();
$local_conf['pinid'] = isset($conf['osm_conf']['pin']['pin']) ? $conf['osm_conf']['pin']['pin'] : 1;
$local_conf['pinpath'] = isset($conf['osm_conf']['pin']['pinpath']) ? $conf['osm_conf']['pin']['pinpath'] : '';
$local_conf['pinsize'] = isset($conf['osm_conf']['pin']['pinsize']) ? $conf['osm_conf']['pin']['pinsize'] : '';
$local_conf['pinshadowpath'] = isset($conf['osm_conf']['pin']['pinshadowpath']) ? $conf['osm_conf']['pin']['pinshadowpath'] : '';
$local_conf['pinshadowsize'] = isset($conf['osm_conf']['pin']['pinshadowsize']) ? $conf['osm_conf']['pin']['pinshadowsize'] : '';
$local_conf['pinoffset'] = isset($conf['osm_conf']['pin']['pinoffset']) ? $conf['osm_conf']['pin']['pinoffset'] : '';
$local_conf['pinpopupoffset'] = isset($conf['osm_conf']['pin']['pinpopupoffset']) ? $conf['osm_conf']['pin']['pinpopupoffset'] : '';

/* If the config include parameters get them */
$zoom = isset($conf['osm_conf']['left_menu']['zoom']) ? $conf['osm_conf']['left_menu']['zoom'] : 2;
$center = isset($conf['osm_conf']['left_menu']['center']) ? $conf['osm_conf']['left_menu']['center'] : '0,0';
$center_lat = isset($center) ? explode(',', $center)[0] : 0;
$center_lng = isset($center) ? explode(',', $center)[1] : 0;

/* If we have zoom and center coordonate, set it otherwise fallback default */
$local_conf['zoom'] = isset($_GET['zoom']) ? $_GET['zoom'] : '2';
$local_conf['center_lat'] = isset($_GET['center_lat']) ? $_GET['center_lat'] : '0';
$local_conf['center_lng'] = isset($_GET['center_lng']) ? $_GET['center_lng'] : '0';

$zoom = isset($_GET['zoom']) ? $_GET['zoom'] : $zoom;
$center_lat = isset($_GET['center_lat']) ? $_GET['center_lat'] : $center_lat;
$center_lng = isset($_GET['center_lng']) ? $_GET['center_lng'] : $center_lng;

// Load baselayerURL
// Key1 BC9A493B41014CAABB98F0471D759707
if     ($baselayer == 'mapnik')		$baselayerurl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
else if($baselayer == 'mapquest')	$baselayerurl = 'http://otile1.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png';
else if($baselayer == 'cloudmade')	$baselayerurl = 'http://{s}.tile.cloudmade.com/7807cc60c1354628aab5156cfc1d4b3b/997/256/{z}/{x}/{y}.png';
else if($baselayer == 'mapnikde')	$baselayerurl = 'http://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png';
else if($baselayer == 'mapnikfr')	$baselayerurl = 'http://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png';
else if($baselayer == 'blackandwhite')	$baselayerurl = 'http://{s}.www.toolserver.org/tiles/bw-mapnik/{z}/{x}/{y}.png';
else if($baselayer == 'mapnikhot')	$baselayerurl = 'http://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png';
else if($baselayer == 'mapquestaerial')	$baselayerurl = 'http://oatile{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.jpg';
else if($baselayer == 'custom')	$baselayerurl = $custombaselayerurl;

$attribution = osmcopyright($attrleaflet, $attrimagery, $attrmodule, $baselayer, $custombaselayer);

// Generate Javascript
// ----------------------------------------
// no worldWarp (no world copies, restrict the view to one world)
if($noworldwarp)
{
	$nowarp = " true ";
	$worldcopyjump = "worldCopyJump: false, maxBounds: [ [82, -180], [-82, 180] ]";
}
else
{
	$nowarp = " false ";
	$worldcopyjump = "worldCopyJump: true";
}

//$js = "\nvar addressPoints = ". json_encode($js_data, JSON_UNESCAPED_SLASHES) .";\n";
$js = "\nvar addressPoints = ". str_replace("\/","/",json_encode($js_data)) .";\n";

$available_pin = array(
	'0' => '',
	'1' => '',
	'2' => 'PlgIconGreen',
	'3' => 'PlgIconRed',
	'4' => 'LeafIconGreen',
	'5' => 'LeafIconOrange',
	'6' => 'LeafIconRed',
	'7' => 'MapIconBlue',
	'8' => 'MapIconGreen',
	'9' => 'CustomIcon',
	'10' => 'ImgIcon'
);
$local_conf['control'] = true;
$local_conf['img_popup'] = true;
$local_conf['paths'] = osm_get_gps($page);

$js_data = osm_get_items($page);
$js = osm_get_js($conf, $local_conf, $js_data);
osm_gen_template($conf, $js, $js_data, 'osm-map2.tpl', $template)
?>
