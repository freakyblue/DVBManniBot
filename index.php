<?php
  require_once('token.php');
  $website = 'https://api.telegram.org/bot'. $token;
  $input = json_decode(file_get_contents('php://input'), TRUE);
  $senderId = $input['message']['chat']['id'];

  //welcome message
  if (isset($senderId))
    file_get_contents($website . '/sendmessage?chat_id=' . $senderId . '&text=Hello');
?>
