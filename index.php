<?php
$debug = FALSE;

require_once('token.php');    //bot identifier
require_once('functions.php');
require_once('response-messages.php');
if (!$debug) require_once('update.php');    //statistics

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
      sendMsg($chatId, $resp['start'], '');
      break;
    case '/help':
      sendMsg($chatId, $resp['help'], '');
      break;
    //before /addmystation
    case '/add':
      $arg1 = explode(' ', $inputMsg)[1];
      if (!isset($arg1)) {
        sendMsg($chatId, $resp['add_no_input'], '');
        break;
      }//if
      if (isStationShort($arg1)) addMyStation($chatId, $arg1);
      else {
        $possibleStations = getStations($arg1);
        if (count($possibleStations) > 1) {
          for ($i=0; $i<count($possibleStations); $i++)
            $but[] = array(array(
              'text' => $possibleStations[$i][1],
              'callback_data' => urlencode(
                '/addshort_'.$possibleStations[$i][0].'_'.$possibleStations[$i][1]
              )
            ));
          inlineKeys($but, $chatId, $resp['suggest']);
        }//if
        elseif (count($possibleStations) == 1) addMyStation($chatId, $possibleStations[0][0]);
        else sendMsg($chatId, $resp['no_station_match'], '');
      }//else
      break;
    //before /removemystation
    case '/remove':
      removeMyStation($chatId, explode(' ', $inputMsg)[1]);
      break;
    case '/keys':
      userKeys($chatId, $resp['keys_refreshed']);
      break;
    //to answer as bot (for admmin only)
    case '/answer':
      if ($chatId == $contactId) {
        $contacterChatId = explode(' ', $inputMsg)[1];
        $msg = str_replace('/answer ', '', $inputMsg);
        $msg = str_replace($contacterChatId, '', $msg);
        sendMsg($contacterChatId, $msg, '');
      }//if
      else sendMsg($chatId, $resp['not_manni'],'');
      break;
    //to answer as bot (for admmin only)
    case '/sendAll':
      if ($chatId == $contactId) {
        $msg = str_replace('/sendAll ', '', $inputMsg);
        sendAll($chatId, $msg);
      }//if
      else sendMsg($chatId, $resp['not_manni'],'');
      break;
    //send line map as img
    case '/plan':
      sendPlanBig($chatId);
      break;
    //to get statistics
    case '/stats':
      if ($chatId == $contactId) {
        statistics($chatId);
      }//if
      else sendMsg($chatId, $resp['not_manni'],'');
      break;
    //to contact the bot admin
    case '/contact':
      if (count(explode(' ', $inputMsg)) == 1)
        sendMsg($chatId, $resp['contact_no_input'],'');
      else {
        sendMsg($contactId, 'from @'.$senderUsername.
        ' ('.$senderFirstName.' '.$senderLastName.') '.
        '`'.$chatId.'`'.PHP_EOL.$inputMsg, 'Markdown'
        );
        sendMsg($chatId, $resp['contact_thx'],'');
      }//else
      break;
    default:
      //if station-short is entered
      if (isStationShort($inputMsg)) printResult($chatId, $inputMsg, '', 10);
      else {
        $possibleStations = getStations($inputMsg);
        if (count($possibleStations) > 1) {
          for ($i=0; $i<count($possibleStations); $i++)
            $but[] = array(array(
              'text' => $possibleStations[$i][1],
              'callback_data' => urlencode(
                '/short_'.$possibleStations[$i][0].'_'.$possibleStations[$i][1]
              )
            ));
          inlineKeys($but, $chatId, $resp['suggest']);
        }//if
        else printResult($chatId, $possibleStations[0][0], $possibleStations[0][1], 10);
      }//else
      break;
  }//switch

}//if

if($input['callback_query']) {
  $command = explode('_', $callbackData)[0];
  $arg1 = explode('_', $callbackData)[1];
  $arg2 = explode('_', $callbackData)[2];
  switch($command) {
    case '/short':
      printResult($callbackId, $arg1, $arg2, 10);
      break;
    case '/addshort':
      addMyStation($callbackId, $arg1);
      break;
    case '/printLongResult':
      printResult($callbackId, $arg1, $arg2, 20);
      break;
    default:
      break;
    }//switch
}//if

?>
