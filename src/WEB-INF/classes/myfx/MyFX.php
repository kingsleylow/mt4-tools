<?php
/**
 * MyFX related functionality
 */
class MyFX extends StaticClass {


   // Start der M1-History der FX-Indizes
   public static $fxIndizesHistoryStart_M1 = null;                // @see static initializer at end of file


   /**
    * Gibt den absoluten Pfad der unter dem angegebenen Schlüssel konfigurierten Pfadeinstellung zurück.
    * Ist ein relativer Pfad konfiguriert, wird der Pfad als relativ zu APPLICATION_ROOT interpretiert.
    *
    * @param  string $key - Schlüssel
    *
    * @return string - absoluter Pfad mit Forward-Slashes (auch unter Windows)
    *
    * @throws plRuntimeException - wenn unter dem angegebenen Schlüssel keine Pfadeinstellung existiert
    */
   public static function getConfigPath($key) {
      if (!is_string($key)) throw new IllegalTypeException('Illegal type of parameter $key: '.getType($key));

      $directory = str_replace('\\', '/', Config ::get($key));    // Backslashes in Konfiguration ersetzen

      if (WINDOWS) {
         if (!preg_match('/^[a-z]:/i', $directory))               // Pfad ist relativ, wenn er nicht mit einem Lw.-Bezeichner beginnt
            $directory = APPLICATION_ROOT.($directory{0}=='/'?'':'/').$directory;
      }
      else if ($directory{0} != '/') {                            // Pfad ist relativ, wenn er nicht mit einem Slash beginnt
         $directory = APPLICATION_ROOT.'/'.$directory;
      }

      return str_replace('\\', '/', $directory);                  // Backslashes in APPLICATION_ROOT ersetzen
   }


   /**
    * Gibt den FXT-Timestamp der angegebenen Zeit zurück. Ohne Argument wird der FXT-Timestamp der aktuellen Zeit
    * zurückgegeben. Analog zu time(), der zurückgegebene Wert sind jedoch die Sekunden seit dem 01.01.1970 FXT.
    *
    * @param  int $gmtTime - GMT-Timestamp (default: aktuelle Zeit)
    *
    * @return int - FXT-Timestamp
    */
   public static function fxtTime($gmtTime=null) {
      if (is_null($gmtTime)) $gmtTime = time();
      else if (!is_int($gmtTime)) throw new IllegalTypeException('Illegal type of parameter $gmtTime: '.getType($gmtTime));

      $oldTimezone = date_default_timezone_get();
      try {
         date_default_timezone_set('America/New_York');

         $estOffset = iDate('Z', $gmtTime);
         $fxtTime   = $gmtTime + $estOffset + 7*HOURS;

         date_default_timezone_set($oldTimezone);
         return $fxtTime;
      }
      catch(Exception $ex) { date_default_timezone_set($oldTimezone); throw $ex; }
   }


   /**
    * Parst eine FXT-Zeit in einen GMT-Timestamp.
    *
    * @param  string $time - FXT-Zeit in einem der Funktion strToTime() verständlichen Format
    *
    * @return int - Timestamp
    */
   public static function fxtStrToTime($time) {
      if (!is_string($time)) throw new IllegalTypeException('Illegal type of parameter $time: '.getType($time));

      $oldTimezone = date_default_timezone_get();
      try {
         date_default_timezone_set('America/New_York');

         $timestamp = strToTime($time);
         if ($timestamp === false) throw new plInvalidArgumentException('Invalid argument $time: "'.$time.'"');
         $timestamp -= 7*HOURS;

         date_default_timezone_set($oldTimezone);
         return $timestamp;
      }
      catch(Exception $ex) { date_default_timezone_set($oldTimezone); throw $ex; }
   }


   /**
    * Formatiert einen GMT-Timestamp als FXT-Zeit.
    *
    * @param  int    $timestamp - Zeitpunkt (GMT, default: aktuelle Zeit)
    * @param  string $format    - Formatstring (default: 'Y-m-d H:i:s')
    *
    * @return string - FXT-String
    *
    * Analogous to the date() function except that the time returned is Forex Time (FXT).
    */
   public static function fxtDate($timestamp=null, $format='Y-m-d H:i:s') {
      if (is_null($timestamp)) $timestamp = time();
      else if (!is_int($timestamp)) throw new IllegalTypeException('Illegal type of parameter $timestamp: '.getType($timestamp));
      if (!is_string($format))      throw new IllegalTypeException('Illegal type of parameter $format: '.getType($format));

      // FXT = America/New_York +0700     (von 17:00 bis 24:00 = 7h)
      // $timestamp += 7*HOURS reicht nicht aus, da dann keine FXT-Repräsentation von Zeiten, die in
      // New York in eine Zeitumstellung fallen, möglich ist. Dies ist nur mit einer Zone ohne DST möglich.
      // Der GMT-Timestamp muß in einen FXT-Timestamp konvertiert und dieser als GMT-Timestamp formatiert werden.

      return gmDate($format, self::fxtTime($timestamp));
   }


   /**
    * Gibt den Offset der angegebenen Zeit zu FXT (Forex Time) zurück.
    *
    * @param  int   $timestamp      - Zeitpunkt relativ zu GMT (default: aktuelle Zeit)
    * @param  array $prevTransition - Wenn angegeben, enthält die Variable nach Rückkehr ein Array
    *                                 ['time'=>{timestamp}, 'offset'=>{offset}] mit dem Zeitpunkt des vorherigen Zeitwechsels
    *                                 und dem Offset vor diesem Zeitpunkt.
    * @param  array $nextTransition - Wenn angegeben, enthält die Variable nach Rückkehr ein Array
    *                                 ['time'=>{timestamp}, 'offset'=>{offset}] mit dem Zeitpunkt des nächsten Zeitwechsels
    *                                 und dem Offset nach diesem Zeitpunkt.
    *
    * @return int - Offset in Sekunden (es gilt: FXT + Offset = GMT) oder NULL, wenn der Zeitpunkt außerhalb der bekannten
    *               Transitionsdaten liegt
    */
   public static function getGmtToFxtTimeOffset($timestamp=null, &$prevTransition=array(), &$nextTransition=array()) {
      if (!is_int($timestamp) && !is_null($timestamp)) throw new IllegalTypeException('Illegal type of parameter $timestamp: '.getType($timestamp));
      is_null($timestamp) && $timestamp=time();

      static $transitions = null;
      if (!$transitions) {
         $timezone    = new DateTimeZone('America/New_York');
         $transitions = $timezone->getTransitions();
      }

      $i = -2;
      foreach ($transitions as $i => $transition) {
         if ($transition['ts'] > $timestamp) {
            $i--;                                                    // $i zeigt auf die aktuelle Periode
            break;
         }
      }

      $size = sizeOf($transitions);
      $args = func_num_args();

      // $prevTransition definieren
      if ($args > 1) {
         $prevTransition = array();

         if ($i < 0) {                                               // $transitions ist leer oder $timestamp
            $prevTransition['time'  ] = null;                        // liegt vor der ersten Periode
            $prevTransition['offset'] = null;
         }
         else if ($i == 0) {                                         // $timestamp liegt in erster Periode
            $prevTransition['time'  ] = $transitions[0]['ts'];
            $prevTransition['offset'] = null;                        // vorheriger Offset unbekannt
         }
         else {
            $prevTransition['time'  ] =   $transitions[$i  ]['ts'];
            $prevTransition['offset'] = -($transitions[$i-1]['offset'] + 7*HOURS);
         }
      }

      // $nextTransition definieren
      if ($args > 2) {
         $nextTransition = array();

         if ($i==-2 || $i >= $size-1) {                              // $transitions ist leer oder
            $nextTransition['time'  ] = null;                        // $timestamp liegt in letzter Periode
            $nextTransition['offset'] = null;
         }
         else {
            $nextTransition['time'  ] =   $transitions[$i+1]['ts'];
            $nextTransition['offset'] = -($transitions[$i+1]['offset'] + 7*HOURS);
         }
      }

      // Rückgabewert definieren
      $offset = null;
      if ($i >= 0)                                                   // $transitions ist nicht leer und
         $offset = -($transitions[$i]['offset'] + 7*HOURS);          // $timestamp liegt nicht vor der ersten Periode
      return $offset;
   }


   /**
    * Gibt die Mailadressen aller konfigurierten Signalempfänger per E-Mail zurück.
    *
    * @return string[] - Array mit E-Mailadressen
    */
   public static function getMailSignalReceivers() {
      static $addresses = null;

      if (is_null($addresses)) {
         $values = Config ::get('mail.signalreceivers');
         foreach (explode(',', $values) as $address) {
            if ($address=trim($address))
               $addresses[] = $address;
         }
         if (!$addresses)
            $addresses = array();
      }
      return $addresses;
   }


   /**
    * Gibt die Rufnummern aller konfigurierten Signalempfänger per SMS zurück.
    *
    * @return string[] - Array mit Rufnummern
    */
   public static function getSmsSignalReceivers() {
      static $numbers = null;

      if (is_null($numbers)) {
         $values = Config ::get('sms.signalreceivers', null);
         foreach (explode(',', $values) as $number) {
            if ($number=trim($number))
               $numbers[] = $number;
         }
         if (!$numbers)
            $numbers = array();
      }
      return $numbers;
   }


   /**
    * Verschickt eine SMS.
    *
    * @param  string $receiver - Empfänger (internationales Format)
    * @param  string $message  - Nachricht
    */
   public static function sendSMS($receiver, $message) {
      if (!is_string($receiver))   throw new IllegalTypeException('Illegal type of parameter $receiver: '.getType($receiver));
      $receiver = trim($receiver);
      if (strStartsWith($receiver, '+' )) $receiver = subStr($receiver, 1);
      if (strStartsWith($receiver, '00')) $receiver = subStr($receiver, 2);
      if (!ctype_digit($receiver)) throw new plInvalidArgumentException('Invalid argument $receiver: "'.$receiver.'"');

      if (!is_string($message))    throw new IllegalTypeException('Illegal type of parameter $message: '.getType($message));
      $message = trim($message);
      if ($message == '')          throw new plInvalidArgumentException('Invalid argument $message: "'.$message.'"');


      $config   = Config ::get('sms.clickatell');
      $username = $config['username'];
      $password = $config['password'];
      $api_id   = $config['api_id'  ];
      $message  = urlEncode($message);
      $url      = 'https://api.clickatell.com/http/sendmsg?user='.$username.'&password='.$password.'&api_id='.$api_id.'&to='.$receiver.'&text='.$message;

      // HTTP-Request erzeugen und ausführen
      $request  = HttpRequest ::create()->setUrl($url);
      $options[CURLOPT_SSL_VERIFYPEER] = false;                // das SSL-Zertifikat kann nicht prüfbar oder ungültig sein
      $response = CurlHttpClient ::create($options)->send($request);
      $status   = $response->getStatus();
      $content  = $response->getContent();
      if ($status != 200) throw new plRuntimeException('Unexpected HTTP status code from api.clickatell.com: '.$status.' ('.HttpResponse ::$sc[$status].')');
   }


   /**
    * Gibt die Beschreibung eines Operation-Types zurück.
    *
    * @param  int $type - Operation-Type
    *
    * @return string - Beschreibung
    */
   public static function operationTypeDescription($type) {
      if (!is_int($type)) throw new IllegalTypeException('Illegal type of parameter $type: '.getType($type));

      static $operationTypes = array(OP_BUY       => 'Buy'       ,
                                     OP_SELL      => 'Sell'      ,
                                     OP_BUYLIMIT  => 'Buy Limit' ,
                                     OP_SELLLIMIT => 'Sell Limit',
                                     OP_BUYSTOP   => 'Stop Buy'  ,
                                     OP_SELLSTOP  => 'Stop Sell' ,
                                     OP_BALANCE   => 'Balance'   ,
                                     OP_CREDIT    => 'Credit'    ,
                                    );
      if (isSet($operationTypes[$type]))
         return $operationTypes[$type];

      throw new plInvalidArgumentException('Invalid parameter $type: '.$type.' (not an operation type)');
   }


   /**
    * Ob ein FXT-Zeitpunkt auf einen regulären Handelstag fällt.
    *
    * @param  int $fxtTime - FXT-Timestamp
    *
    * @return bool
    */
   public static function isTradingDay($fxtTime) {
      if (!is_int($fxtTime)) throw new IllegalTypeException('Illegal type of parameter $fxtTime: '.getType($fxtTime));

      return (!self::isWeekend($fxtTime) && !self::isHoliday($fxtTime));
   }


   /**
    * Ob der Wochentag eines FXT-Zeitpunkts ein Sonnabend oder Sonntag ist.
    *
    * @param  int $fxtTime - FXT-Timestamp
    *
    * @return bool
    */
   public static function isWeekend($fxtTime) {
      if (!is_int($fxtTime)) throw new IllegalTypeException('Illegal type of parameter $fxtTime: '.getType($fxtTime));

      $dow = iDate('w', $fxtTime);
      return ($dow==SATURDAY || $dow==SUNDAY);
   }


   /**
    * Ob ein FXT-Zeitpunkt auf einen Forex-Feiertag fällt.
    *
    * @param  int $fxtTime - FXT-Timestamp
    *
    * @return bool
    */
   public static function isHoliday($fxtTime) {
      if (!is_int($fxtTime)) throw new IllegalTypeException('Illegal type of parameter $fxtTime: '.getType($fxtTime));

      $dom = iDate('d', $fxtTime);
      $m   = iDate('m', $fxtTime);

      if ($dom==1 && $m==1)            // 1. Januar
         return true;
      if ($dom==25 && $m==12)          // 25. Dezember
         return true;
      return false;
   }


   /**
    * Interpretiert die MyFX-Bardaten eines Strings und liest sie in ein Array ein.
    *
    * @param  string $data - String mit MyFX-Bardaten
    *
    * @return MYFX_BAR[] - Array mit Bardaten
    */
   public static function readBarData($data) {
      if (!is_string($data)) throw new IllegalTypeException('Illegal type of parameter $data: '.getType($data));

      $size   = strLen($data); if ($size % MYFX_BAR_SIZE) throw new plRuntimeException('Odd length of passed $data: '.$size.' (not an even MYFX_BAR_SIZE)');
      $offset = 0;
      $i      = 0;
      $bars   = array();

      while ($offset < $size) {
         $bars[] = unpack("@$offset/Vtime/Vopen/Vhigh/Vlow/Vclose/Vticks", $data);
         $offset += MYFX_BAR_SIZE;
         $i++;
      }
      return $bars;
   }


   /**
    * Interpretiert die Bardaten einer MyFX-Datei und liest sie in ein Array ein.
    *
    * @param  string $fileName - Name der Datei mit MyFX-Bardaten
    *
    * @return MYFX_BAR[] - Array mit Bardaten
    */
   public static function readBarFile($fileName) {
      if (!is_string($fileName)) throw new IllegalTypeException('Illegal type of parameter $fileName: '.getType($fileName));
      return self::readBarData(file_get_contents($fileName));
   }


   /**
    * Interpretiert die Bardaten einer komprimierten MyFX-Datei und liest sie in ein Array ein.
    *
    * @param  string $fileName - Name der Datei mit MyFX-Bardaten
    *
    * @return MYFX_BAR[] - Array mit Bardaten
    */
   public static function readCompressedBarFile($fileName) {
      throw new UnimplementedFeatureException(__METHOD__);
   }


   /**
    * Gibt die lesbare Konstante eines Timeframe-Codes zurück.
    *
    * @param  int period - Timeframe-Code bzw. Anzahl der Minuten je Bar
    *
    * @return string
    */
   public static function periodToStr($period) {
      if (!is_int($period)) throw new IllegalTypeException('Illegal type of parameter $period: '.getType($period));

      switch ($period) {
         case PERIOD_M1 : return "PERIOD_M1";       // 1 minute
         case PERIOD_M5 : return "PERIOD_M5";       // 5 minutes
         case PERIOD_M15: return "PERIOD_M15";      // 15 minutes
         case PERIOD_M30: return "PERIOD_M30";      // 30 minutes
         case PERIOD_H1 : return "PERIOD_H1";       // 1 hour
         case PERIOD_H4 : return "PERIOD_H4";       // 4 hour
         case PERIOD_D1 : return "PERIOD_D1";       // 1 day
         case PERIOD_W1 : return "PERIOD_W1";       // 1 week
         case PERIOD_MN1: return "PERIOD_MN1";      // 1 month
         case PERIOD_Q1 : return "PERIOD_Q1";       // 1 quarter
      }
      return "$period";
   }


   /**
    * Alias für periodToStr()
    *
    * @param  int timeframe
    *
    * @return string
    */
   public static function timeframeToStr($timeframe) {
      return self::periodToStr($timeframe);
   }


   /**
    * Gibt die Beschreibung eines Timeframe-Codes zurück.
    *
    * @param  int period - Timeframe-Code bzw. Anzahl der Minuten je Bar
    *
    * @return string
    */
   public static function periodDescription($period) {
      if (!is_int($period)) throw new IllegalTypeException('Illegal type of parameter $period: '.getType($period));

      switch ($period) {
         case PERIOD_M1 : return "M1";      //      1  1 minute
         case PERIOD_M5 : return "M5";      //      5  5 minutes
         case PERIOD_M15: return "M15";     //     15  15 minutes
         case PERIOD_M30: return "M30";     //     30  30 minutes
         case PERIOD_H1 : return "H1";      //     60  1 hour
         case PERIOD_H4 : return "H4";      //    240  4 hour
         case PERIOD_D1 : return "D1";      //   1440  daily
         case PERIOD_W1 : return "W1";      //  10080  weekly
         case PERIOD_MN1: return "MN1";     //  43200  monthly
         case PERIOD_Q1 : return "Q1";      // 129600  3 months (a quarter)
      }
      return "$period";
   }


   /**
    * Alias für periodDescription()
    *
    * @param  int timeframe
    *
    * @return string
    */
   public static function timeframeDescription($timeframe) {
      return self::periodDescription($timeframe);
   }
}


/**
 * Workaround für in PHP nicht existierende Static Initializer
 */

// Start der M1-History der FX-Indizes
MyFX::$fxIndizesHistoryStart_M1 = array('AUDLFX' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'CADLFX' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'CHFLFX' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'EURLFX' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'GBPLFX' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'JPYLFX' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'NZDLFX' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'USDLFX' => strToTime('2003-08-03 00:00:00 GMT'),

                                        'AUDFX6' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'CADFX6' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'CHFFX6' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'EURFX6' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'GBPFX6' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'JPYFX6' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'USDFX6' => strToTime('2003-08-03 00:00:00 GMT'),

                                        'AUDFX7' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'CADFX7' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'CHFFX7' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'EURFX7' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'GBPFX7' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'JPYFX7' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'NOKFX7' => strToTime('2003-08-05 00:00:00 GMT'),
                                        'NZDFX7' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'SEKFX7' => strToTime('2003-08-05 00:00:00 GMT'),
                                        'SGDFX7' => strToTime('2004-11-16 00:00:00 GMT'),
                                        'USDFX7' => strToTime('2003-08-03 00:00:00 GMT'),
                                        'ZARFX7' => strToTime('2003-08-03 00:00:00 GMT'),

                                        'EURX'   => strToTime('2003-08-04 00:00:00 GMT'),
                                        'USDX'   => strToTime('2003-08-04 00:00:00 GMT'),
);
