<?php
$input = json_decode(file_get_contents('php://input'), TRUE);
$senderFirstName = $input['message']['from']['first_name'];

$resp['start'] = 'Hallo '.$senderFirstName.PHP_EOL.
  'Ich bin Manni und ich helfe dir gerne bei den Abfahrtszeiten von Bussen und Bahnen der DVB.'.
  PHP_EOL.
  'Bei /help werden dir alle Funktionen dieses Bots aufgelistet.';

$resp['help'] = 'Hallo '.$senderFirstName.PHP_EOL.
  'Wobei brauchen Sie Hilfe?'.PHP_EOL.PHP_EOL.
  '- Tippen Sie einfach den Stationsname oder einen Teil davon ein, um die Abfahrten angezeigt zu
  bekommen.'.PHP_EOL.
  '- Mit /addmystation können Sie eine Haltestelle zu Ihrer persönlichen Schnellauswahl hinzufügen.'
  .PHP_EOL.
  '- Mit /removemystation können Sie eine Haltestelle von Ihrer Schnellauswahl entfernen.'.PHP_EOL.
  '- Mit /plan bekommen Sie den Liniennetzplan als Bild geschickt.'.PHP_EOL.
  '- Mit /keys werden die Schnellauswahltasten aktualisiert eingeblendet.'.PHP_EOL.
  '- Über /contact können Sie mich direkt kontaktieren und Fragen stellen, Kritik oder Lob
  loswerden.'.PHP_EOL.PHP_EOL.
  'P.S.: Ich freue mich sehr, wennn Sie diesen Bot Freuden weiterempfehlen.'.PHP_EOL.
  'Dein Manni';

$resp['add_no_input'] = 'Bitte geben Sie eine Haltestelle  an.'.PHP_EOL.'z.B. /addmystation HBF';
$resp['add_already_in'] = 'Diese Haltestelle befindet sich bereits in Ihrer Auswahl.';
$resp['add_too_much'] = 'Es befinden sich zu viele Haltestellen in Ihrer Auswahl.';
$resp['add_succes'] = 'Erfolgreich hinzugefügt.';

$resp['contact_no_input'] = 'Schreibe deine Nachricht hinter /contact'.PHP_EOL.
'Zum Beispiel'.PHP_EOL.
'/contact Cooler Bot Manni ;)';
$resp['contact_thx'] = 'Danke '.$senderFirstName.' für deine Nachricht.';


$resp['keys_refreshed'] = 'Auswahl aktualisiert.';

$resp['rm_succes'] = 'Erfolgreich entfernt.';
$resp['rm_not_in'] = 'Dies Haltestelle befindet sich nicht in Ihrer Auswahl.';

$resp['not_manni'] = 'Dieses Feature ist Mannis vorbehalten.'.PHP_EOL.
  'Wenn du an diesem Bot mitentwickeln möchtest, dann schreibe mir per /contact';
$resp['no_station_match'] = 'Ich konnte keine passende Haltestelle finden.';
  'Ich werde mich schnellstmöglich um die Bearbeitung kümmern.'.PHP_EOL.'Dein Manni';
$resp['suggest'] = 'Meinten Sie?';
$resp['show_more'] = 'mehr anzeigen';

//Markdown
$resp['print_dep_for'] = 'Die Abfahrten für ';
$resp['print_no_info'] = 'Keine Abfahrtsinformationen verfügbar.'."\n".
  'Aber eine alte Manni-Weisheit besagt, dass es noch 42 Minuten dauert.';
?>
