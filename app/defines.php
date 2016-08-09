<?php
/**
 * Timezones und Timezone-IDs
 *
 * @see  Definition in MT4Expander.dll::defines.h
 */
define('TIMEZONE_ALPARI'             , 'Alpari'          );    // bis 03/2012 "Europe/Berlin", ab 04/2012 "Europe/Kiev"
define('TIMEZONE_AMERICA_NEW_YORK'   , 'America/New_York');
define('TIMEZONE_EUROPE_BERLIN'      , 'Europe/Berlin'   );
define('TIMEZONE_EUROPE_KIEV'        , 'Europe/Kiev'     );
define('TIMEZONE_EUROPE_LONDON'      , 'Europe/London'   );
define('TIMEZONE_EUROPE_MINSK'       , 'Europe/Minsk'    );
define('TIMEZONE_FXT'                , 'FXT'             );    // "Europe/Kiev"   mit DST-Wechseln von "America/New_York"
define('TIMEZONE_FXT_MINUS_0200'     , 'FXT-0200'        );    // "Europe/London" mit DST-Wechseln von "America/New_York"
define('TIMEZONE_GLOBALPRIME'        , 'GlobalPrime'     );    // bis 24.10.2015 "FXT", dann durch Fehler "Europe/Kiev" (einmalig?)
define('TIMEZONE_GMT'                , 'GMT'             );

define('TIMEZONE_ID_ALPARI'          ,                 1 );
define('TIMEZONE_ID_AMERICA_NEW_YORK',                 2 );
define('TIMEZONE_ID_EUROPE_BERLIN'   ,                 3 );
define('TIMEZONE_ID_EUROPE_KIEV'     ,                 4 );
define('TIMEZONE_ID_EUROPE_LONDON'   ,                 5 );
define('TIMEZONE_ID_EUROPE_MINSK'    ,                 6 );
define('TIMEZONE_ID_FXT'             ,                 7 );
define('TIMEZONE_ID_FXT_MINUS_0200'  ,                 8 );
define('TIMEZONE_ID_GLOBALPRIME'     ,                 9 );
define('TIMEZONE_ID_GMT'             ,                10 );


// Timeframe-Identifier
define('PERIOD_M1' ,      1);    // 1 minute
define('PERIOD_M5' ,      5);    // 5 minutes
define('PERIOD_M15',     15);    // 15 minutes
define('PERIOD_M30',     30);    // 30 minutes
define('PERIOD_H1' ,     60);    // 1 hour
define('PERIOD_H4' ,    240);    // 4 hours
define('PERIOD_D1' ,   1440);    // daily
define('PERIOD_W1' ,  10080);    // weekly
define('PERIOD_MN1',  43200);    // monthly
define('PERIOD_Q1' , 129600);    // a quarter (3 months)


// Operation-Types
define('OP_BUY'      ,   0);     //    MT4: long position
define('OP_SELL'     ,   1);     //         short position
define('OP_BUYLIMIT' ,   2);     //         buy limit order
define('OP_SELLLIMIT',   3);     //         sell limit order
define('OP_BUYSTOP'  ,   4);     //         stop buy order
define('OP_SELLSTOP' ,   5);     //         stop sell order
define('OP_BALANCE'  ,   6);     //         account credit or withdrawal transaction
define('OP_CREDIT'   ,   7);     //         credit facility, no transaction
define('OP_TRANSFER' ,   8);     // custom: Balance-Änderung durch Kunden (Deposit/Withdrawal)
define('OP_VENDOR'   ,   9);     //         Balance-Änderung durch Criminal (Dividende, Swap, Ausgleich etc.)


// Spalten der internen History-Daten in UploadAccountHistoryForm
define('AH_TICKET'     ,  0);
define('AH_OPENTIME'   ,  1);
define('AH_TYPE'       ,  2);
define('AH_UNITS'      ,  3);
define('AH_SYMBOL'     ,  4);
define('AH_OPENPRICE'  ,  5);
define('AH_CLOSETIME'  ,  6);
define('AH_CLOSEPRICE' ,  7);
define('AH_COMMISSION' ,  8);
define('AH_SWAP'       ,  9);
define('AH_PROFIT'     , 10);
define('AH_MAGICNUMBER', 11);
define('AH_COMMENT'    , 12);


// Struct-Sizes
define('DUKASCOPY_BAR_SIZE' , 24);
define('DUKASCOPY_TICK_SIZE', 20);
