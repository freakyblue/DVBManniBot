<?php
$debug = TRUE;

require_once('token.php');
if (!$debug) require_once('update.php');
require_once('../../mysqli_connect.php');

$website = 'https://api.telegram.org/bot'.$token;

function printResult ($chatId, $short, $long) {
  global $dbc;
  $departures = json_decode(file_get_contents(
    'http://widgets.vvo-online.de/abfahrtsmonitor/Abfahrten.do?vz=0&lim=10&hst='.decBug($short)), TRUE);
  if ($long == '')
    $long = mysqli_fetch_array(@mysqli_query($dbc,
      'SELECT * FROM `dvb_stations` WHERE `short`="'.decBug($short).'"'))['station'];
  $msg = 'Die Abfahrten für '.encBug(decBug($long)).urlencode("\n"); //removes second point
  for ($i=0; $i<count($departures); $i++)
    $msg .= '`'.addSpace($departures[$i][0], 5).addSpace($departures[$i][1], 21).$departures[$i][2].'`'.urlencode("\n");
  if($i == 0) $msg = 'No information available'.PHP_EOL.
    'Aber eine alte Manni-Weisheit besagt, dass es noch 42 Minauten dauert.';
  sendMsg ($chatId, $msg, 'Markdown');
}

function sendMsg ($chatId, $msg, $mode) {
  if ($mode == '') $msg = urlencode($msg);
  apiRequest('sendmessage?parse_mode='.$mode.'&chat_id='.$chatId.'&text='.$msg);
}//sendMsg

function apiRequest ($methode) {
  return file_get_contents($GLOBALS[website].'/'.$methode);
}//apiRequest

function inlineKeys ($buttons, $chatId, $msg) {
  $keyboard = json_encode(array('inline_keyboard' => $buttons));
  apiRequest('sendmessage?parse_mode=Markdown&chat_id='.$chatId.'&text='.urlencode($msg).'&reply_markup='.$keyboard);
}//inlineKeys

function getStations ($input) {
  global $dbc;
  $stations = [];
  $dbResult = @mysqli_query($dbc, 'SELECT * FROM `dvb_stations` WHERE `station` LIKE "%'.$input.'%" LIMIT 8');
  while ($result = mysqli_fetch_array($dbResult))
    array_push($stations, array($result['short'], $result['station']));
  return $stations;
}//getStations

function isStationShort ($input) {
  global $dbc;
  return mysqli_fetch_array(@mysqli_query($dbc, 'SELECT count(*) FROM `dvb_stations` WHERE `short` = "'.$input.'"'))[0];
}

//there is a akward bug in the telegram API, so you can't send  'H'
function encBug($x) {
  return str_replace('H', '.H', $x);
}//encBug

function decBug($x) {
  return str_replace('.H', 'H', $x);
}//decBug

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
}
?>
