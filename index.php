<?php
  $debug = FALSE;
  require_once('token.php');
  if (!$debug) require_once('update.php');
  require_once('../../mysqli_connect.php');
  $website = 'https://api.telegram.org/bot'.$token;
  $input = json_decode(file_get_contents('php://input'), TRUE);
  $chatId = $input['message']['chat']['id'];
  $inputMsg = $input['message']['text'];
  $senderFirstName = $input['message']['from']['first_name'];
  $senderUsername = $input['message']['from']['username'];
  $callbackId = $input['callback_query']['from']['id'];
  $callbackData = $input['callback_query']['data'];

if (isset($chatId)) {
  if ($debug) sendMsg($chatId, '$chatId is set');
  if (!$debug) update($chatId);
  switch (explode(' ', $inputMsg)[0]) {
    case '/station':
      sendMsg($chatId, '/station wird nicht länger gebraucht, tippe einfach den Haltestellennamen ohne Zusatz ein.');
      break;
    case '/start':
      sendMsg($chatId, 'Hallo '.$senderFirstName.PHP_EOL.'Ich bin Manni und ich helfe dir gerne bei den Abfahrtszeiten von Bussen und Bahnen der DVB.');
      break;
    case '/contact':
      if (count(explode(' ', $inputMsg)) == 1)
        sendMsg($chatId, 'Schreibe deine Nachricht hinter /contact'.PHP_EOL.'Zum Beispiel'.PHP_EOL.'/contact Cooler Bot Manni ;)');
      else {
        sendMsg($contactChatId, 'from @'.$senderUsername.PHP_EOL.$inputMsg);
        sendMsg($chatId, 'Danke '.$senderFirstName.' für deine Nachricht.'.PHP_EOL.'Ich werde mich schnellstmöglich um die Bearbeitung kümmern.'.PHP_EOL.'Dein Manni');
      }//else
      break;
    default:
      $possibleStations = getStations($inputMsg);
      if ($debug) sendMsg($chatId, 'count($possibleStations) '.count($possibleStations));
      if (count($possibleStations) > 1) {
        for ($i=0; $i<count($possibleStations); $i++) {
          if ($debug) sendMsg($chatId, 'text '.$possibleStations[$i][1].PHP_EOL.'callback_data /short '.encBug($possibleStations[$i][0]).' '.encBug($possibleStations[$i][1]));
          //$but[] = array(array('text' => $possibleStations[$i][1], 'callback_data' => '/short HBF Hauptbahnhof'));
          $but[] = array(array('text' => $possibleStations[$i][1], 'callback_data' => '/short '.encBug($possibleStations[$i][0]).' '.encBug($possibleStations[$i][1])));
        }
        inlineKeys($but, $chatId, 'Meinten Sie?');
      }//if
      else {
        if ($debug) sendMsg($chatId, '$chatId '.$chatId.PHP_EOL.'$possibleStations[0][0] '.$possibleStations[0][0].PHP_EOL.'$possibleStations[0][1] '.$possibleStations[0][1]);
        printResult($chatId, $possibleStations[0][0], $possibleStations[0][1]);
      }//else
      break;
  }//switch
}//if

if($input['callback_query']) {
  $command = explode(' ', $callbackData)[0];
  $arg1 = explode(' ', $callbackData)[1];
  $arg2 = explode(' ', $callbackData)[2].' '.explode(' ', $callbackData)[3].' '.explode(' ', $callbackData)[4];
  if ($debug) sendMsg($callbackId, $callbackData.PHP_EOL.'command '.$command.PHP_EOL.'arg1 '.$arg1.PHP_EOL.'arg2 '.$arg2);
  if($command == '/short') printResult($callbackId, $arg1, $arg2);
}//if

function printResult ($chatId, $short, $long) {
  if ($GLOBALS[debug]) sendMsg($chatId, '$chatId '.$chatId.PHP_EOL.'$short '.$short.PHP_EOL.'$long '.$long);
  global $dbc;
  $departures = json_decode(file_get_contents('http://widgets.vvo-online.de/abfahrtsmonitor/Abfahrten.do?vz=0&lim=10&hst='.$short), TRUE);
  if ($long == '')
    $long = mysqli_fetch_array(@mysqli_query($dbc, 'SELECT * FROM `dvb_stations` WHERE `short`="'.$short.'"'))['station'];
  $msg = 'Die Abfahrten für '.encBug($long).urlencode("\n");
  if ($GLOBALS[debug]) sendMsg($chatId, '$msg '.$msg);
  if ($GLOBALS[debug]) sendMsg($chatId, 'count($departures) '.count($departures));
  for ($i=0; $i<count($departures); $i++)
    $msg .= "` ".addSpace($departures[$i][0], 5).addSpace($departures[$i][1], 28).$departures[$i][2]."` ".urlencode("\n");
  if($i == 0) $msg = 'No information available';
  if ($GLOBALS[debug]) sendMsg($chatId, '$msg '.$msg);
  apiRequest('sendmessage?parse_mode=Markdown&chat_id='.$chatId.'&text='.$msg);
}

function sendMsg ($chatId, $msg) {
  apiRequest('sendmessage?chat_id='.$chatId.'&text='.urlencode($msg));
}//sendMsg

function apiRequest ($methode) {
  return file_get_contents($GLOBALS[website].'/'.$methode);
}//apiRequest

function inlineKeys ($buttons, $chatId, $msg) {
  $keyboard = json_encode(array('inline_keyboard' => $buttons));
  apiRequest('sendmessage?parse_mode=Markdown&chat_id='.$chatId.'&text='.urlencode($msg).'&reply_markup='.$keyboard);
}//inlineKeys

function getStations($input) {
  global $dbc;
  $stations = [];
  $dbResult = @mysqli_query($dbc, 'SELECT * FROM `dvb_stations` WHERE `station` LIKE "%'.$input.'%" LIMIT 8');
  while ($result = mysqli_fetch_array($dbResult)) {
    array_push($stations, array($result['short'], $result['station']));
  }//while
  return $stations;
}//getStations

//there is a akward bug in the telegram API, so you can't send " H"
function encBug($x) {
  if ($GLOBALS[debug]) sendMsg($GLOBALS[chatId], $x.' to '.str_replace("H", ".H", $x));
  return str_replace("H", ".H", $x);
}//encBug

function decBug($x) {
  return str_replace(".H", "H", $x);
}//decBug

function addSpace ($input, $length) {
  $count = substr_count($input, "Ä");
  $count += substr_count($input, "Ö");
  $count += substr_count($input, "Ü");
  $count += substr_count($input, "ä");
  $count += substr_count($input, "ö");
  $count += substr_count($input, "ü");
  $count += substr_count($input, "ß");
  $output = str_pad(encBug($input), $length, urlencode(" "));
  for ($i=0; $i<$count; $i++) $output .= " ";
  return $output;
}
?>
