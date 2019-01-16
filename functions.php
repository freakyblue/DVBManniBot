<?php
require_once('token.php');
require_once('response-messages.php');
require_once('../../mysqli_connect.php');

$website = 'https://api.telegram.org/bot'.$token;

function addMyStation ($chatId, $short) {
  global $dbc, $resp;
  $short = strtoupper($short);
  $maxStationNo = 9;
  $allStations = explode(' ', mysqli_fetch_array(@mysqli_query(
    $dbc, 'SELECT `myStations` FROM `bot_DVBManniBot_user` WHERE `chat_id` ='.$chatId))[0]);
  if (in_array($short, $allStations))
    sendMsg ($chatId, $resp['add_already_in'], '');
  elseif (count($allStations) >= $maxStationNo)
    sendMsg ($chatId, $resp['add_too_much'], '');
  else {
    if (count($allStations) == 0) $spacer = '';
    else $spacer = ' ';
    @mysqli_query($dbc, 'UPDATE `bot_DVBManniBot_user` SET `myStations` = concat(`myStations` ,"'.
      $spacer.$short.'") WHERE `chat_id` ='.$chatId);
    userKeys ($chatId, $resp['add_succes']);
  }//else
}//addMyStation

function addSpace ($input, $length) {
  $input = substr($input, 0, $length);
  $count = substr_count($input, 'Ä');
  $count += substr_count($input, 'Ö');
  $count += substr_count($input, 'Ü');
  $count += substr_count($input, 'ä');
  $count += substr_count($input, 'ö');
  $count += substr_count($input, 'ü');
  $count += substr_count($input, 'ß');
  $output = str_pad($input, $length, ' ');
  for ($i=0; $i<$count; $i++) $output .= ' ';
  return $output;
}//addSpace

function apiRequest ($methode) {
  return file_get_contents($GLOBALS[website].'/'.$methode);
}//apiRequest

function convertSpecialSigns ($inputTxt) {
  $resultTxt = str_replace('ß', 'ss', $inputTxt);
  return $resultTxt;
}//convertSpecialSigns

function getStations ($input) {
  global $dbc;
  $stations = [];
  //$input = convertSpecialSigns($input);
  $dbResult = @mysqli_query(
    $dbc, 'SELECT * FROM `bot_DVBManniBot_stations` WHERE `station` LIKE "%'.$input.'%" LIMIT 8'
  );
  while ($result = mysqli_fetch_array($dbResult))
    array_push($stations, array($result['short'], $result['station']));
  return $stations;
}//getStations

function inlineKeys ($buttons, $chatId, $msg) {
  $keyboard = json_encode(array('inline_keyboard' => $buttons));
  apiRequest('sendmessage?parse_mode=Markdown&chat_id='.$chatId.'&text='.urlencode($msg).
    '&reply_markup='.$keyboard);
}//inlineKeys

function isStationShort ($input) {
  global $dbc;
  return mysqli_fetch_array(@mysqli_query($dbc,
    'SELECT count(*) FROM `bot_DVBManniBot_stations` WHERE `short` = "'.$input.'"'
  ))[0];
}//isStationShort

function keyboard ($keys, $text, $chatId) {
  apiRequest('sendmessage?parse_mode=Markdown&chat_id='.$chatId.'&text='.urlencode($text).
    '&reply_markup='.json_encode($keys));
}//keyboard

function locationResult ($chatId, $latitude, $longitude) {
  global $dbc;
  $query = @mysqli_query(
    $dbc,
    'SELECT short FROM `bot_DVBManniBot_stations`
    ORDER BY ABS(`latitude` - '.$latitude.') + ABS(`longitude` - '.$longitude.') LIMIT 3'
  );
  while ($station = mysqli_fetch_array($query)) {
    printResult($chatId, $station['short'], '', 8);
  }//while
}//locationResult

function printResult ($chatId, $short, $long, $max) {
  global $dbc, $resp;
  $departures = json_decode(file_get_contents(
    'http://widgets.vvo-online.de/abfahrtsmonitor/Abfahrten.do?vz=0&lim='.$max.
    '&hst='.urlencode($short)
  ), TRUE);
  if ($long == '')
    $long = mysqli_fetch_array(@mysqli_query($dbc,
      'SELECT * FROM `bot_DVBManniBot_stations` WHERE `short`="'.$short.'"'))['station'];
  foreach ($departures as $departure) {
    $lenNo = max(strlen($departure[0]), $lenNo);
    $lenDest = max(strlen($departure[1]), $lenDest);
  }//foreach
  $lenNo = min(4, $lenNo);
  $lenDest = min(17, $lenDest);
  foreach ($departures as $departure)
    $msg .= '`'.addSpace($departure[0], ($lenNo + 1)).
      addSpace($departure[1], ($lenDest + 1)).$departure[2].'`'."\n";
  if(count($departures) == 0)
    $msg = $resp['print_no_info'];
  if(count($departures) == 10) {
    $but[] = array(array('text' => $resp['show_more'],
      'callback_data' => urlencode('/printLongResult_'.$short.'_'.$long)));
    inlineKeys($but, $chatId, $resp['print_dep_for']."*".$long."* _(".$short.")_\n".$msg);
  }//if
  else
    sendMsg ($chatId, $resp['print_dep_for']."*".$long."* _(".$short.")_\n".$msg, 'Markdown');
}//printResult

function removeMyStation ($chatId, $short) {
  global $dbc, $resp;
  $short = strtoupper($short);
  $allStations = mysqli_fetch_array(@mysqli_query(
    $dbc, 'SELECT `myStations` FROM `bot_DVBManniBot_user` WHERE `chat_id` ='.$chatId))[0];
  if (in_array($short, explode(' ', $allStations))) {
    $allStations = trim(str_replace($short, '', $allStations));
    $allStations = preg_replace('/\s\s+/', ' ', $allStations);
    @mysqli_query($dbc,
      'UPDATE `bot_DVBManniBot_user` SET `myStations` = "'.$allStations.'" WHERE `chat_id`='.$chatId
    );
    userKeys ($chatId, $resp['rm_succes']);
  }//if
  else sendMsg ($chatId, $resp['rm_not_in'], '');
}//removeMyStation

function sendAll ($chatId, $msg) {
  global $dbc, $contactId;
  if ($chatId == $contactId) {
    $dbResult = @mysqli_query($dbc, 'SELECT `chat_id` FROM `bot_DVBManniBot_testuser` WHERE 1');
    while ($currChatId = mysqli_fetch_array($dbResult)) {
      sendMsg($currChatId['chat_id'], $msg, '');
    }//while
  }//if
}//sendAll

function sendMsg ($chatId, $msg, $mode) {
  apiRequest('sendmessage?parse_mode='.$mode.'&chat_id='.$chatId.'&text='.urlencode($msg));
}//sendMsg

function sendPlan ($chatId) {
  global $website;
  $photo = new CURLFile('plan.jpg', 'image/jpg', 'plan.jpg');
  $ch = curl_init($website.'/sendDocument');
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, array('chat_id' => $chatId, 'document' => $photo));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
  curl_exec($ch);
  curl_close($ch);
}//sendPlan

function userKeys ($chatId, $msg) {
  global $dbc;
  $db = explode(' ', mysqli_fetch_array(@mysqli_query(
    $dbc, 'SELECT `myStations` FROM `bot_DVBManniBot_user` WHERE `chat_id` ='.$chatId))[0]);
  $keyboard = [];
  $buttonsPerRow = 3;
  for ($i=0; $i<count($db); $i++) {
    if ($i % $buttonsPerRow == 0) array_push($keyboard, [$db[$i]]);
    else array_push($keyboard[floor($i/$buttonsPerRow)], $db[$i]);
  }//for
  $keys = array('resize_keyboard' => true, 'keyboard'=> $keyboard);
  keyboard($keys, $msg, $chatId);
}//userKeys

?>
