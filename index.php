<?php
  require_once('token.php');
  require_once('../../mysqli_connect.php');
  $website = 'https://api.telegram.org/bot'.$token;
  $input = json_decode(file_get_contents('php://input'), TRUE);
  $chatId = $input['message']['chat']['id'];
  $inputMsg = $input['message']['text'];


  switch (explode(' ', $inputMsg)[0]) {
    case "/station":
      if (isset(explode(' ', $inputMsg)[1])) {
        $inpStation = explode(' ', $inputMsg)[1];
        $possibleStations = getStations($inpStation);
        if (count($possibleStations) > 1) {
          $msg = 'Meinten Sie?'.PHP_EOL;
          for ($i=0; $i<count($possibleStations); $i++)
            $msg .= ' '.$possibleStations[$i][1].PHP_EOL;
        }//if
        else {
          $departures = json_decode(file_get_contents('http://widgets.vvo-online.de/abfahrtsmonitor/Abfahrten.do?ort=Dresden&hst='.$inpStation.'&vz=0&lim=10'), TRUE);
          $msg = 'Die Abfahrten für '.$possibleStations[0][1].PHP_EOL;
          for ($i=0; $i<10; $i++) $msg .= str_pad($departures[$i][0], 7, '_').' '.str_pad($departures[$i][1], 25, '_').' '.$departures[$i][2].PHP_EOL;
        }//else
        sendMsg($chatId, $msg);
      }//if
      else
        sendMsg($chatId, 'Von wo möchtest du die Abfahrten wissen?');
      break;
    default:
      //welcome message
      sendMsg($chatId, 'Hallo'.PHP_EOL.'Schreibe /station Hauptbahnhof um die Abfahrten vom Hauptbahnhof zu erhalten.');
  }//switch


  function sendMsg ($chatId, $message) {
    if (isset($chatId))
      file_get_contents($GLOBALS[website].'/sendmessage?chat_id='.$chatId.'&text='.urlencode($message));
  }//sendMsg


  function getStations($input) {
    global $dbc;
    $stations = [];
    $dbResult = @mysqli_query($dbc, 'SELECT * FROM `dvb_stations` WHERE `station` LIKE "%'.$input.'%"');
    while ($result = mysqli_fetch_array($dbResult)) {
      array_push($stations, array($result['short'], $result['station']));
    }//while
    return $stations;
  }//getStations

?>
