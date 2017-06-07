<?php
$debug = FALSE;

require_once('token.php');
if (!$debug) require_once('update.php');
require_once('../../mysqli_connect.php');

$website = 'https://api.telegram.org/bot'.$token;

function addMyStation ($chatId, $short) {
  global $dbc;
  $maxStationNo = 9;
  $allStations = explode(' ', mysqli_fetch_array(@mysqli_query(
    $dbc, 'SELECT `myStations` FROM `bot_DVBManniBot_user` WHERE `chat_id` ='.$chatId))[0]);
  if (in_array($short, $allStations))
    sendMsg ($chatId, 'Diese Haltestelle befindet sich bereits in Ihrer Auswahl.', '');
  elseif (count($allStations) >= $maxStationNo)
    sendMsg ($chatId, 'Es befinden sich zu viele Haltestellen in Ihrer Auswahl.', '');
  else {
    if (count($allStations) == 0) $spacer = '';
    else $spacer = ' ';
    @mysqli_query($dbc, 'UPDATE `bot_DVBManniBot_user` SET `myStations` = concat(`myStations` ,"'.$spacer.decBug($short)
      .'") WHERE `chat_id` ='.$chatId);
    userKeys ($chatId, 'Erfolgreich hinzugefügt.');
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
  $output = str_pad(encBug($input), $length, urlencode(' '));
  for ($i=0; $i<$count; $i++) $output .= ' ';
  return $output;
}//addSpace

function apiRequest ($methode) {
  return file_get_contents($GLOBALS[website].'/'.$methode);
}//apiRequest

//there is a akward bug in the telegram API, so you can't send  'H'
function decBug($x) {
  return str_replace('.H', 'H', $x);
}//decBug

function encBug($x) {
  return str_replace('H', '.H', $x);
}//encBug

function getStations ($input) {
  global $dbc;
  $stations = [];
  $dbResult = @mysqli_query($dbc, 'SELECT * FROM `bot_DVBManniBot_stations` WHERE `station` LIKE "%'.$input.'%" LIMIT 8');
  while ($result = mysqli_fetch_array($dbResult))
    array_push($stations, array($result['short'], $result['station']));
  return $stations;
}//getStations

function inlineKeys ($buttons, $chatId, $msg) {
  $keyboard = json_encode(array('inline_keyboard' => $buttons));
  apiRequest('sendmessage?parse_mode=Markdown&chat_id='.$chatId.'&text='.urlencode($msg).'&reply_markup='.$keyboard);
}//inlineKeys

function isStationShort ($input) {
  global $dbc;
  return mysqli_fetch_array(@mysqli_query($dbc, 'SELECT count(*) FROM `bot_DVBManniBot_stations` WHERE `short` = "'
    .$input.'"'))[0];
}//isStationShort

function keyboard ($keys, $text, $chatId) {
  apiRequest('sendmessage?parse_mode=Markdown&chat_id='.$chatId.'&text='.urlencode($text).
    '&reply_markup='.json_encode($keys));
}//keyboard

function printLongResult ($chatId, $short, $long) {
  $departures = json_decode(file_get_contents(
    'http://widgets.vvo-online.de/abfahrtsmonitor/Abfahrten.do?vz=0&lim=20&hst='.decBug($short)), TRUE);
  $msg = 'Die Abfahrten für '.encBug(decBug($long)).urlencode("\n"); //removes second point
  for ($i=0; $i<count($departures); $i++)
    $msg .= '`'.addSpace($departures[$i][0], 5).addSpace($departures[$i][1], 21).$departures[$i][2].'`'.urlencode("\n");
  sendMsg ($chatId, $msg, 'Markdown');
}//printLongResult

function printResult ($chatId, $short, $long) {
  global $dbc;
  $departures = json_decode(file_get_contents(
    'http://widgets.vvo-online.de/abfahrtsmonitor/Abfahrten.do?vz=0&lim=10&hst='.decBug($short)), TRUE);
  if ($long == '')
    $long = mysqli_fetch_array(@mysqli_query($dbc,
      'SELECT * FROM `bot_DVBManniBot_stations` WHERE `short`="'.decBug($short).'"'))['station'];
  $msg = 'Die Abfahrten für '.encBug(decBug($long)).urlencode("\n"); //removes second point
  for ($i=0; $i<count($departures); $i++)
    $msg .= '`'.addSpace($departures[$i][0], 5).addSpace($departures[$i][1], 21).$departures[$i][2].'`'.urlencode("\n");
  if($i == 0) $msg = 'Keine Abfahrtsinformationen verfügbar.'.urlencode("\n").
    'Aber eine alte Manni-Weisheit besagt, dass es noch 42 Minuten dauert.';
  if($i == 10) {
    $but[] = array(array('text' => 'mehr anzeigen',
      'callback_data' => '/printLongResult '.encBug($short).' '.encBug(decBug($long))));
    //inlineKeys($but, $chatId, $msg);
    $keyboard = json_encode(array('inline_keyboard' => $but));
    apiRequest('sendmessage?parse_mode=Markdown&chat_id='.$chatId.'&text='.encBug(decBug($msg)).
      '&reply_markup='.$keyboard);
  }//if
  else
    sendMsg ($chatId, $msg, 'Markdown');
}//printResult

function removeMyStation ($chatId, $short) {
  global $dbc;
  $allStations = mysqli_fetch_array(@mysqli_query(
    $dbc, 'SELECT `myStations` FROM `bot_DVBManniBot_user` WHERE `chat_id` ='.$chatId))[0];
  if (in_array($short, explode(' ', $allStations))) {
    $allStations = trim(str_replace($short, '', $allStations));
    $allStations = preg_replace('/\s\s+/', ' ', $allStations);
    @mysqli_query($dbc, 'UPDATE `bot_DVBManniBot_user` SET `myStations` = "'.$allStations.'" WHERE `chat_id` ='.$chatId);
    userKeys ($chatId, 'Erfolgreich entfernt.');
  }//if
  else sendMsg ($chatId, $short.' befindet sich nicht in Ihrer Auswahl.', '');
}//removeMyStation

function sendAll ($chatId, $msg) {
  global $dbc;
  $contactId = $GLOBALS[contactId];
  if ($chatId == $contactId) {
    $dbResult = @mysqli_query($dbc, 'SELECT `chat_id` FROM `bot_DVBManniBot_user` WHERE 1');
    while ($currChatId = mysqli_fetch_array($dbResult))
      sendMsg($currChatId['chat_id'], $msg, '');
  }//if
}//sendAll

function sendMsg ($chatId, $msg, $mode) {
  if ($mode == '') $msg = urlencode($msg);
  apiRequest('sendmessage?parse_mode='.$mode.'&chat_id='.$chatId.'&text='.$msg);
}//sendMsg

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
