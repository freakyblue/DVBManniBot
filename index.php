<?php
$debug = FALSE;

require_once('token.php');    //bot identifier
require_once('../../mysqli_connect.php');   //db-connection
require_once('functions.php');
if (!$debug) require_once('update.php');    //statistics

$website = 'https://api.telegram.org/bot'.$token;
$input = json_decode(file_get_contents('php://input'), TRUE);
$chatId = $input['message']['chat']['id'];
$inputMsg = $input['message']['text'];
$command = explode(' ', $inputMsg)[0];
$senderFirstName = $input['message']['from']['first_name'];
$senderLastName = $input['message']['from']['last_name'];
$senderUsername = $input['message']['from']['username'];
$callbackId = $input['callback_query']['from']['id'];
$callbackData = $input['callback_query']['data'];

if ($chatId) {  //to hide warnings from website
  if (!$debug) update($chatId, $senderUsername, $senderFirstName, $senderLastName);

  switch ($command) {
    case '/start':
      sendMsg($chatId, 'Hallo '.$senderFirstName.PHP_EOL.
        'Ich bin Manni und ich helfe dir gerne bei den Abfahrtszeiten von Bussen und Bahnen der DVB.', '');
      break;
    case '/help':
      sendMsg($chatId, 'Hallo '.$senderFirstName.PHP_EOL.
        'Wobei brauchen Sie Hilfe?'.PHP_EOL.PHP_EOL.
        '- Tippen Sie einfach den Stationsname oder einen Teil ein um die Abfahrten angezeigt zu bekommen.'.PHP_EOL.
        '- Mit /addmystation können Sie eine Haltestelle zu Ihrer persönlichen Schnellauswahl hinzufügen.'.PHP_EOL.
        '- Mit /removemystation können Sie eine Haltestelle von Ihrer Schnellauswahl entfernen.'.PHP_EOL.
        '- Mit /keys werden die Schnellauswahltasten aktualisiert eingeblendet.'.PHP_EOL.
        '- Über /contact können Sie mich direkt kontaktieren und Fragen stellen, Kritik oder Lob loswerden.'.PHP_EOL.
        PHP_EOL.'P.S.: Ich freue mich sehr, wennn Sie diesen Bot Freuden weiterempfehlen.'.PHP_EOL.
        'Dein Manni', '');
      break;
    case '/addmystation':
      $arg1 = explode(' ', $inputMsg)[1];
      if (!isset($arg1)) {
        sendMsg($chatId, 'Bitte geben Sie eine Haltestelle  an.'.PHP_EOL.'z.B. /addmystation HBF', '');
        break;
      }
      if (isStationShort($arg1)) addMyStation($chatId, $arg1);
      else {
        $possibleStations = getStations($arg1);
        if (count($possibleStations) > 1) {
          for ($i=0; $i<count($possibleStations); $i++)
            $but[] = array(array('text' => $possibleStations[$i][1], 'callback_data' => '/addshort '.
              encBug($possibleStations[$i][0]).' '.encBug($possibleStations[$i][1])));
          inlineKeys($but, $chatId, 'Meinten Sie?');
        }//if
        elseif (count($possibleStations) == 1) addMyStation($chatId, $possibleStations[0][0]);
        else sendMsg($chatId, 'Ich konnte keine passende Haltestelle finden.', '');
      }//else
      break;
    case '/removemystation':
      removeMyStation($chatId, explode(' ', $inputMsg)[1]);
      break;
    case '/keys':
      userKeys($chatId, 'Auswahl aktualisiert.');
      break;
    //to answer as bot (for admmin only)
    case '/answer':
      if ($chatId == $contactId) {
        $contacterChatId = explode(' ', $inputMsg)[1];
        $msg = str_replace('/answer ', '', $inputMsg);
        $msg = str_replace($contacterChatId, '', $msg);
        sendMsg($contacterChatId, $msg, '');
      }//if
      else sendMsg($chatId, $chatId.'Dieses Feature ist Mannis vorbehalten.'.PHP_EOL.
        'Wenn du an diesem Bot mitentwickeln möchtest, dann schreibe mir per /contact','');
      break;
    //to answer as bot (for admmin only)
    case '/sendAll':
      if ($chatId == $contactId) {
        $msg = str_replace('/sendAll ', '', $inputMsg);
        sendAll($chatId, $msg);
      }//if
      else sendMsg($chatId, $chatId.'Dieses Feature ist Mannis vorbehalten.'.PHP_EOL.
        'Wenn du an diesem Bot mitentwickeln möchtest, dann schreibe mir per /contact','');
      break;
    //to contact the bot admin
    case '/contact':
      if (count(explode(' ', $inputMsg)) == 1)
        sendMsg($chatId, 'Schreibe deine Nachricht hinter /contact'.PHP_EOL.'Zum Beispiel'.PHP_EOL.
          '/contact Cooler Bot Manni ;)','');
      else {
        sendMsg($contactId, 'from @'.$senderUsername.' ('.$senderFirstName.' '.$senderLastName.') '.$chatId.PHP_EOL.
          $inputMsg,'');
        sendMsg($chatId, 'Danke '.$senderFirstName.' für deine Nachricht.'.PHP_EOL.
          'Ich werde mich schnellstmöglich um die Bearbeitung kümmern.'.PHP_EOL.'Dein Manni','');
      }//else
      break;
    default:
      //if station-short is entered
      if (isStationShort($inputMsg)) printResult($chatId, $inputMsg, '');
      else {
        $possibleStations = getStations($inputMsg);
        if (count($possibleStations) > 1) {
          for ($i=0; $i<count($possibleStations); $i++)
            $but[] = array(array('text' => $possibleStations[$i][1], 'callback_data' => '/short '.
              encBug($possibleStations[$i][0]).' '.encBug($possibleStations[$i][1])));
          inlineKeys($but, $chatId, 'Meinten Sie?');
        }//if
        else printResult($chatId, $possibleStations[0][0], $possibleStations[0][1]);
      }//else
      break;
  }//switch

}//if

if($input['callback_query']) {
  $command = explode(' ', $callbackData)[0];
  $arg1 = explode(' ', $callbackData)[1];
  $arg2 = explode(' ', $callbackData)[2].' '.explode(' ', $callbackData)[3].' '.explode(' ', $callbackData)[4];
  switch($command) {
    case '/short':
      printResult($callbackId, $arg1, $arg2);
      break;
    case '/addshort':
      addMyStation($callbackId, $arg1);
      break;
    case '/printLongResult':
      printLongResult($callbackId, $arg1, $arg2);
      break;
    default:
      break;
    }//switch
}//if

?>
