<?php
  require_once('token.php');
  $website = 'https://api.telegram.org/bot'.$token;
  $input = json_decode(file_get_contents('php://input'), TRUE);
  $chatId = $input['message']['chat']['id'];
  $inputMsg = $input['message']['text'];

  switch (explode(' ', $inputMsg)[0]) {
    case "/station":
      if (isset(explode(' ', $inputMsg)[1])) {
        sendMsg($chatId, 'Du möchtest die Abfahrten von '.explode(' ', $inputMsg)[1].'.');
        $departures = json_decode(file_get_contents('http://widgets.vvo-online.de/abfahrtsmonitor/Abfahrten.do?ort=Dresden&hst='.explode(' ', $inputMsg)[1].'&vz=0&lim=6'), TRUE);
        for ($i=0; $i<5; $i++) $msg .= $departures[$i][0].' - '.$departures[$i][1].' - '.$departures[$i][2].PHP_EOL;
        sendMsg($chatId, $msg);
      }
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
?>
