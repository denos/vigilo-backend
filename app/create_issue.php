<?php
require_once('./common.php');
require_once('./functions.php');

# Generate Unique ID
$secretid=str_replace('.','',uniqid('', true));

if(isset($_GET['key'])) {
  $key = $_GET['key'];
}
else {
  $key = Null;
}

# Get Web form datas
$token = mysqli_real_escape_string($db,$_POST['token']);
$coordinates_lat = mysqli_real_escape_string($db,$_POST['coordinates_lat']);
$coordinates_lon = mysqli_real_escape_string($db,$_POST['coordinates_lon']);
$comment = mysqli_real_escape_string($db,$_POST['comment']);
$categorie = mysqli_real_escape_string($db,$_POST['categorie']);
$address = mysqli_real_escape_string($db,$_POST['address']);
$time = mysqli_real_escape_string($db,$_POST['time']);
$time = floor($time / 1000);
$status = 0;
if(isset($_POST['version'])) {
  $version = mysqli_real_escape_string($db,$_POST['version']);
}
else {
  $version = 0;
}


# Check if token exist
$query_token = mysqli_query($db,"SELECT * FROM obs_list WHERE obs_token='".$token."' LIMIT 1");

if(mysqli_num_rows($query_token) == 1 && getrole($key, $acls) == "admin") {
  $query_result = mysqli_fetch_array($query_token);
  $secretid = $query_result['obs_secretid'];
  $json = array('token' => $token, 'status' => 0,'secretid'=>$secretid,'group'=>$query_result['obs_group']);
  mysqli_query($db,'UPDATE obs_list SET obs_coordinates_lat="'.$coordinates_lat.'",
                                        obs_coordinates_lon="'.$coordinates_lon.'",
                                        obs_comment="'.$comment.'",
                                        obs_address_string="'.$address.'",
                                        obs_categorie="'.$categorie.'",
                                        obs_time="'.$time.'",
                                        obs_app_version="'.$version.'"
                    WHERE obs_token="'.$token.'" AND obs_secretid="'.$secretid.'"');
}
else {

  if(mysqli_num_rows($query_token) == 1 or empty($token)) {
    $token=strtoupper(substr(str_replace('.','',uniqid('', true)), 0, 8));
  }
  # Init Datas
  $json = array('token' => $token, 'status' => 0,'secretid'=>$secretid);
  # Insert user datas to MySQL Database
  if(!empty($coordinates_lat) and !empty($coordinates_lon) and !empty($categorie) and !empty($time) and !empty($address)) {
  
    $group_id = 0;
    $group_query = mysqli_query($db,"SELECT * FROM obs_groups ORDER BY group_id");
    while($group_result = mysqli_fetch_array($group_query)) {
        if($group_result['group_categorie'] == $categorie && str_replace(' ','',$group_result['group_address_string']) == str_replace(' ','',$address)) {
          $group_id = $group_result['group_id'];
          break;
        }
        elseif($group_result['group_categorie'] == $categorie && distance($group_result['group_coordinates_lat'], $group_result['group_coordinates_lon'], $coordinates_lat, $coordinates_lon, $unit = 'm') < 200) {
          $group_id = $group_result['group_id'];
          break;
        }
    }
    if($group_id == 0) {
      mysqli_query($db,'INSERT INTO obs_groups (`group_address_string`,`group_coordinates_lat`,`group_coordinates_lon`,`group_categorie`) VALUES
        ("'.$address.'","'.$coordinates_lat.'","'.$coordinates_lon.'","'.$categorie.'")') ;
      $group_id = mysqli_insert_id($db);
    }
  
    $json['group'] = $group_id;
    mysqli_query($db,'INSERT INTO obs_list (`obs_coordinates_lat`,`obs_coordinates_lon`,`obs_address_string`,`obs_comment`,`obs_categorie`,`obs_token`,`obs_time`,`obs_status`,`obs_app_version`,`obs_secretid`,`obs_group`) VALUES
  				  ("'.$coordinates_lat.'","'.$coordinates_lon.'","'.$address.'","'.$comment.'","'.$categorie.'","'.$token.'","'.$time.'",0,"'.$version.'","'.$secretid.'","'.$group_id.'")') ;
  
    if($mysqlerror = mysqli_error($db)) {
      $status = 1;
      error_log('CREATE_ISSUE : MySQL Error '.$mysqlerror);
    }
  }
  else {
    $status = 1;
    error_log('CREATE_ISSUE : Field not supported');
  }
}  
# If error force return 500 ERROR CODE
if($status != 0) {
  http_response_code(500);
}

# Return Token value
$json['status'] = $status;
echo json_encode($json);
?>
