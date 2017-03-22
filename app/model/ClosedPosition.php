<?php
use rosasurfer\db\orm\PersistableObject;

use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\util\Date;


/**
 * ClosedPosition
 */
class ClosedPosition extends PersistableObject {


    /** @var int - primary key */
    protected $id;

    /** @var string - time of creation */
    protected $created;

    /** @var string - time of last modification */
    protected $version;

    protected /*int   */ $ticket;
    protected /*string*/ $type;
    protected /*float */ $lots;
    protected /*string*/ $symbol;
    protected /*string*/ $openTime;
    protected /*float */ $openPrice;
    protected /*string*/ $closeTime;
    protected /*float */ $closePrice;
    protected /*float */ $stopLoss;
    protected /*float */ $takeProfit;
    protected /*float */ $commission;
    protected /*float */ $swap;
    protected /*float */ $grossProfit;
    protected /*float */ $netProfit;
    protected /*int   */ $magicNumber;
    protected /*string*/ $comment;
    protected /*int   */ $signal_id;

    private   /*Signal*/ $signal;


    // Getter
    public function getId()          { return $this->id;          }
    public function getTicket()      { return $this->ticket;      }
    public function getType()        { return $this->type;        }
    public function getLots()        { return $this->lots;        }
    public function getSymbol()      { return $this->symbol;      }
    public function getOpenPrice()   { return $this->openPrice;   }
    public function getClosePrice()  { return $this->closePrice;  }
    public function getStopLoss()    { return $this->stopLoss;    }
    public function getTakeProfit()  { return $this->takeProfit;  }
    public function getMagicNumber() { return $this->magicNumber; }
    public function getComment()     { return $this->comment;     }
    public function getSignal_id()   { return $this->signal_id;   }


    /**
     * Ueberladene Methode.  Erzeugt eine neue geschlossene Position.
     *
     * @return self
     */
    public static function create() {
        if (func_num_args() != 2) throw new RuntimeException('Invalid number of function arguments: '.func_num_args());
        $arg1 = func_get_arg(0);
        $arg2 = func_get_arg(1);

        if ($arg1 instanceof OpenPosition)
            return self::create_1($arg1, $arg2);      // (OpenPosition $position, array $data)
        return self::create_2($arg1, $arg2);         // (Signal $signal, array $data)
    }


    /**
     * Erzeugt eine neue geschlossene Position anhand einer vormals offenen Position.
     *
     * @param  OpenPosition $openPosition - vormals offene Position
     * @param  array        $data         - Positionsdaten
     *
     * @return self
     */
    private static function create_1(OpenPosition $openPosition, array $data) {
        $position = new static();

        $position->ticket      =                $data['ticket'     ];
        $position->type        =                $data['type'       ];
        $position->lots        =                $data['lots'       ];
        $position->symbol      =                $data['symbol'     ];
        $position->openTime    = \MyFX::fxtDate($data['opentime'   ]);
        $position->openPrice   =                $data['openprice'  ];
        $position->closeTime   = \MyFX::fxtDate($data['closetime'  ]);
        $position->closePrice  =                $data['closeprice' ];
        $position->stopLoss    =          isSet($data['stoploss'   ]) ? $data['stoploss'   ] : $openPosition->getStopLoss();
        $position->takeProfit  =          isSet($data['takeprofit' ]) ? $data['takeprofit' ] : $openPosition->getTakeProfit();
        $position->commission  =          isSet($data['commission' ]) ? $data['commission' ] : $openPosition->getCommission();
        $position->swap        =          isSet($data['swap'       ]) ? $data['swap'       ] : $openPosition->getSwap();
        $position->grossProfit =          isSet($data['grossprofit']) ? $data['grossprofit'] : null;
        $position->netProfit   =                $data['netprofit'  ];
        $position->magicNumber =          isSet($data['magicnumber']) ? $data['magicnumber'] : $openPosition->getMagicNumber();
        $position->comment     =          isSet($data['comment'    ]) ? $data['comment'    ] : $openPosition->getComment();

        $position->signal_id = $openPosition->getSignal_id();

        return $position;
    }


    /**
     * Erzeugt eine neue geschlossene Position anhand der angegebenen Rohdaten.
     *
     * @param  Signal $signal - Signal der Position
     * @param  array  $data   - Positionsdaten
     *
     * @return self
     */
    private static function create_2(Signal $signal, array $data) {
        if (!$signal->isPersistent()) throw new InvalidArgumentException('Cannot process '.__CLASS__.' for non-persistent '.get_class($signal));

        $position = new static();

        $position->ticket      =                $data['ticket'     ];
        $position->type        =                $data['type'       ];
        $position->lots        =                $data['lots'       ];
        $position->symbol      =                $data['symbol'     ];
        $position->openTime    = \MyFX::fxtDate($data['opentime'   ]);
        $position->openPrice   =                $data['openprice'  ];
        $position->closeTime   = \MyFX::fxtDate($data['closetime'  ]);
        $position->closePrice  =                $data['closeprice' ];
        $position->stopLoss    =          isSet($data['stoploss'   ]) ? $data['stoploss'   ] : null;
        $position->takeProfit  =          isSet($data['takeprofit' ]) ? $data['takeprofit' ] : null;
        $position->commission  =          isSet($data['commission' ]) ? $data['commission' ] : null;
        $position->swap        =          isSet($data['swap'       ]) ? $data['swap'       ] : null;
        $position->grossProfit =          isSet($data['grossprofit']) ? $data['grossprofit'] : null;
        $position->netProfit   =                $data['netprofit'  ];
        $position->magicNumber =          isSet($data['magicnumber']) ? $data['magicnumber'] : null;
        $position->comment     =          isSet($data['comment'    ]) ? $data['comment'    ] : null;
        $position->signal_id   = $signal->getId();

        return $position;
    }


    /**
     * Return the creation time of the instance.
     *
     * @param  string $format - format as used by date($format, $timestamp)
     *
     * @return string - creation time
     */
    public function getCreated($format = 'Y-m-d H:i:s')  {
        if ($format == 'Y-m-d H:i:s')
            return $this->created;
        return Date::format($this->created, $format);
    }


    /**
     * Return the version string of the instance.
     *
     * @param  string $format - format as used by date($format, $timestamp)
     *
     * @return string - version (last modification time)
     */
    public function getVersion($format = 'Y-m-d H:i:s')  {
        if ($format == 'Y-m-d H:i:s')
            return $this->version;
        return Date::format($this->version, $format);
    }


    /**
     * Gibt die Beschreibung des OperationTypes dieser Position zurueck.
     *
     * @return string - Beschreibung
     */
    public function getTypeDescription() {
        return ucFirst($this->type);
    }


    /**
     * Gibt die OpenTime dieser Position zurueck.
     *
     * @param  string $format - Zeitformat (default: 'Y-m-d H:i:s')
     *
     * @return string - Zeitpunkt
     */
    public function getOpenTime($format='Y-m-d H:i:s')  {
        if ($format == 'Y-m-d H:i:s')
            return $this->openTime;
        return Date::format($this->openTime, $format);
    }


    /**
     * Gibt die CloseTime dieser Position zurueck.
     *
     * @param  string $format - Zeitformat (default: 'Y-m-d H:i:s')
     *
     * @return string - Zeitpunkt
     */
    public function getCloseTime($format='Y-m-d H:i:s')  {
        if ($format == 'Y-m-d H:i:s')
            return $this->closeTime;
        return Date::format($this->closeTime, $format);
    }


    /**
     * Gibt den Commission-Betrag dieser Position zurueck.
     *
     * @param  int    $decimals  - Anzahl der Nachkommastellen
     * @param  string $separator - Dezimaltrennzeichen
     *
     * @return float|string - Betrag oder NULL, wenn der Betrag nicht verfuegbar ist
     */
    public function getCommission($decimals=2, $separator='.') {
        if (is_null($this->commission) || !func_num_args())
            return $this->commission;
        return Number::format($this->commission, $decimals, $separator);
    }


    /**
     * Gibt den Swap-Betrag dieser Position zurueck.
     *
     * @param  int    $decimals  - Anzahl der Nachkommastellen
     * @param  string $separator - Dezimaltrennzeichen
     *
     * @return float|string - Betrag oder NULL, wenn der Betrag nicht verfuegbar ist
     */
    public function getSwap($decimals=2, $separator='.') {
        if (is_null($this->swap) || !func_num_args())
            return $this->swap;
        return Number::format($this->swap, $decimals, $separator);
    }


    /**
     * Gibt den Gross-Profit-Betrag dieser Position zurueck.
     *
     * @param  int    $decimals  - Anzahl der Nachkommastellen
     * @param  string $separator - Dezimaltrennzeichen
     *
     * @return float|string - Betrag oder NULL, wenn der Betrag nicht verfuegbar ist
     */
    public function getGrossProfit($decimals=2, $separator='.') {
        if (is_null($this->grossProfit) || !func_num_args())
            return $this->grossProfit;
        return Number::format($this->grossProfit, $decimals, $separator);
    }


    /**
     * Gibt den Net-Profit-Betrag dieser Position zurueck.
     *
     * @param  int    $decimals  - Anzahl der Nachkommastellen
     * @param  string $separator - Dezimaltrennzeichen
     *
     * @return float|string - Betrag
     */
    public function getNetProfit($decimals=2, $separator='.') {
        if (!func_num_args())
            return $this->netProfit;
        return Number::format($this->netProfit, $decimals, $separator);
    }


    /**
     * Gibt das Signal, zu dem diese Position gehoert, zurueck.
     *
     * @return Signal
     */
    public function getSignal() {
        if ($this->signal === null)
            $this->signal = Signal::dao()->getById($this->signal_id);
        return $this->signal;
    }


    /**
     * Fuegt diese Instanz in die Datenbank ein.
     *
     * @return self
     */
    protected function insert() {
        $db = self::dao();

        $created     = $dao->escapeLiteral($this->created);
        $version     = $dao->escapeLiteral($this->version);

        $ticket      =                     $this->ticket;
        $type        = $dao->escapeLiteral($this->type);
        $lots        =                     $this->lots;
        $symbol      = $dao->escapeLiteral($this->symbol);
        $opentime    = $dao->escapeLiteral($this->openTime);
        $openprice   =                     $this->openPrice;
        $closetime   = $dao->escapeLiteral($this->closeTime);
        $closeprice  =                     $this->closePrice;
        $stoploss    = $dao->escapeLiteral($this->stopLoss);
        $takeprofit  = $dao->escapeLiteral($this->takeProfit);
        $commission  = $dao->escapeLiteral($this->commission);
        $swap        = $dao->escapeLiteral($this->swap);
        $profit      = $dao->escapeLiteral($this->grossProfit);
        $netprofit   =                     $this->netProfit;
        $magicnumber = $dao->escapeLiteral($this->magicNumber);
        $comment     = $dao->escapeLiteral($this->comment);
        $signal_id   =                     $this->signal_id;

        // ClosedPosition einfuegen
        $sql = "insert into :ClosedPosition (created, version, ticket, type, lots, symbol, opentime, openprice, closetime, closeprice, stoploss, takeprofit, commission, swap, profit, netprofit, magicnumber, comment, signal_id) values
                      ($created, $version, $ticket, $type, $lots, $symbol, $opentime, $openprice, $closetime, $closeprice, $stoploss, $takeprofit, $commission, $swap, $profit, $netprofit, $magicnumber, $comment, $signal_id)";
        $this->id = $dao->execute($sql)
                             ->db()
                             ->lastInsertId();
        return $this;
    }
}
