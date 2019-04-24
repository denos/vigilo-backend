<?php
/*
Copyright (C) 2019 Velocité Montpellier

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
 any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
$cwd = dirname(__FILE__);

require_once("${cwd}/includes/common.php");
require_once("${cwd}/includes/functions.php");

header('BACKEND_VERSION: '.BACKEND_VERSION);
header('Content-Type: application/json');

$status = 0;
$json = array();
$scope = (isset($_GET['scope']) ? mysqli_real_escape_string($db,$_GET['scope']) : 0);

if ($scope != 0) {
	$query = mysqli_query($db, "SELECT * FROM obs_scopes WHERE scope_name='".$scope."' LIMIT 1");  
  if(mysqli_num_rows($query) == 0) {
    $status = 1;
    error_log('Scope: '.$scope . ' do not exist !');
  }
  else {
    $result = mysqli_fetch_array($query);
    $json = array(
            'display_name' => $result['scope_display_name'],
            'coordinate_lat_min' => $result['scope_coordinate_lat_min'],
            'coordinate_lat_max' => $result['scope_coordinate_lat_max'],
            'coordinate_lon_min' => $result['scope_coordinate_lon_min'],
            'coordinate_lon_max' => $result['scope_coordinate_lon_max'],
            'map_center_string' => $result['scope_map_center_string'],
            'map_zoom' => $result['scope_map_zoom'],
            'contact_email' => $result['scope_contact_email'],
            'sharing_content_text' => $result['scope_sharing_content_text'],
            'umap_url' => $result['scope_umap_url'],
            'backend_version' => BACKEND_VERSION);
  }
}
else {
  $status = 1;
  error_log('Scope is not defined');
}

if($status != 0) {
  http_response_code(500);
}

echo json_encode($json,JSON_PRETTY_PRINT);
