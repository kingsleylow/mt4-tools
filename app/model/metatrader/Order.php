<?php
namespace rosasurfer\xtrade\model\metatrader;

use rosasurfer\db\orm\PersistableObject;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use rosasurfer\xtrade\Tools;
use rosasurfer\xtrade\metatrader\MT4;


/**
 * Represents a MetaTrader order ticket.
 */
class Order extends PersistableObject {


    /** @var int - primary key */
    protected $id;

    /** @var string - time of creation */
    protected $created;

    /** @var string - time of last modification */
    protected $modified;

     /** @var int - ticket number */
    protected $ticket;

    /** @var string - order type: Buy|Sell */
    protected $type;

    /** @var float - lot size */
    protected $lots;

    /** @var string - symbol */
    protected $symbol;

    /** @var float - open price */
    protected $openPrice;

    /** @var string - open time (server timezone) */
    protected $openTime;

    /** @var float - stoploss price */
    protected $stopLoss;

    /** @var float - takeprofit price */
    protected $takeProfit;

    /** @var float - close price */
    protected $closePrice;

    /** @var string - close time (server timezone) */
    protected $closeTime;

    /** @var float - commission */
    protected $commission;

    /** @var float - swap */
    protected $swap;

    /** @var float - gross profit */
    protected $profit;

    /** @var int - magic number */
    protected $magicNumber;

    /** @var string - order comment */
    protected $comment;

    /** @var int - the Test's id */
    protected $test_id;

    /** @var Test - the test the order belongs to */
    protected $test;


    /**
     * Create a new Order.
     *
     * @param  Test  $test       - the test the order belongs to
     * @param  array $properties - order properties as parsed from the test's log file
     *
     * @return self
     */
    public static function create(Test $test, array $properties) {
        $order       = new self();
        $order->test = $test;

        $id = $properties['id'];
        if (!is_int($id))                                     throw new IllegalTypeException('Illegal type of property order.id: '.getType($id));
        if ($id)                                              throw new InvalidArgumentException('Invalid property order.id: '.$id.' (not zero)');
        $order->id = null;

        $ticket = $properties['ticket'];
        if (!is_int($ticket))                                 throw new IllegalTypeException('Illegal type of property order.ticket: '.getType($ticket));
        if ($ticket <= 0)                                     throw new InvalidArgumentException('Invalid property order.ticket: '.$ticket.' (not positive)');
        $order->ticket = $ticket;

        $type = $properties['type'];
        if (!is_int($type))                                   throw new IllegalTypeException('Illegal type of property order['.$ticket.'].type: '.getType($type));
        if (!Tools::isOrderType($type))                       throw new InvalidArgumentException('Invalid property order['.$ticket.'].type: '.$type.' (not an order type)');
        $order->type = Tools::orderTypeDescription($type);

        $lots = $properties['lots'];
        if (!is_float($lots))                                 throw new IllegalTypeException('Illegal type of property order['.$ticket.'].lots: '.getType($lots));
        if ($lots <= 0)                                       throw new InvalidArgumentException('Invalid property order['.$ticket.'].lots: '.$lots.' (not positive)');
        if ($lots != round($lots, 2))                         throw new InvalidArgumentException('Invalid property order['.$ticket.'].lots: '.$lots.' (lot step violation)');
        $order->lots = $lots;

        $symbol = $properties['symbol'];
        if (!is_string($symbol))                              throw new IllegalTypeException('Illegal type of property order['.$ticket.'].symbol: '.getType($symbol));
        if ($symbol != trim($symbol))                         throw new InvalidArgumentException('Invalid property order['.$ticket.'].symbol: "'.$symbol.'" (format violation)');
        if (!strLen($symbol))                                 throw new InvalidArgumentException('Invalid property order['.$ticket.'].symbol: "'.$symbol.'" (length violation)');
        if (strLen($symbol) > MT4::MAX_SYMBOL_LENGTH)         throw new InvalidArgumentException('Invalid property order['.$ticket.'].symbol: "'.$symbol.'" (length violation)');
        $order->symbol = $symbol;

        $openPrice = $properties['openPrice'];
        if (!is_float($openPrice))                            throw new IllegalTypeException('Illegal type of property order['.$ticket.'].openPrice: '.getType($openPrice));
        $openPrice = round($openPrice, 5);
        if ($openPrice <= 0)                                  throw new InvalidArgumentException('Invalid property order['.$ticket.'].openPrice: '.$openPrice.' (not positive)');
        $order->openPrice = $openPrice;

        $openTime = $properties['openTime'];                  // FXT timestamp
        if (!is_int($openTime))                               throw new IllegalTypeException('Illegal type of property order['.$ticket.'].openTime: '.getType($openTime));
        if ($openTime <= 0)                                   throw new InvalidArgumentException('Invalid property order['.$ticket.'].openTime: '.$openTime.' (not positive)');
        if (isForexWeekend($openTime, 'FXT'))                 throw new InvalidArgumentException('Invalid property order['.$ticket.'].openTime: '.$openTime.' (not a weekday)');
        $order->openTime = gmDate('Y-m-d H:i:s', $openTime);

        $stopLoss = $properties['stopLoss'];
        if (!is_float($stopLoss))                             throw new IllegalTypeException('Illegal type of property order['.$ticket.'].stopLoss: '.getType($stopLoss));
        $stopLoss = round($stopLoss, 5);
        if ($stopLoss < 0)                                    throw new InvalidArgumentException('Invalid property order['.$ticket.'].stopLoss: '.$stopLoss.' (not non-negative)');
        $order->stopLoss = !$stopLoss ? null : $stopLoss;

        $takeProfit = $properties['takeProfit'];
        if (!is_float($takeProfit))                           throw new IllegalTypeException('Illegal type of property order['.$ticket.'].takeProfit: '.getType($takeProfit));
        $takeProfit = round($takeProfit, 5);
        if ($takeProfit < 0)                                  throw new InvalidArgumentException('Invalid property order['.$ticket.'].takeProfit: '.$takeProfit.' (not non-negative)');
        $order->takeProfit = !$takeProfit ? null : $takeProfit;

        if ($stopLoss && $takeProfit) {
            if (Tools::isLongOrderType(Tools::strToOrderType($order->type))) {
                if ($stopLoss >= $takeProfit)                 throw new InvalidArgumentException('Invalid properties order['.$ticket.'].stopLoss|takeProfit for LONG order: '.$stopLoss.'|'.$takeProfit.' (mis-match)');
            }
            else if ($stopLoss <= $takeProfit)                throw new InvalidArgumentException('Invalid properties order['.$ticket.'].stopLoss|takeProfit for SHORT order: '.$stopLoss.'|'.$takeProfit.' (mis-match)');
        }

        $closePrice = $properties['closePrice'];
        if (!is_float($closePrice))                           throw new IllegalTypeException('Illegal type of property order['.$ticket.'].closePrice: '.getType($closePrice));
        $closePrice = round($closePrice, 5);
        if ($closePrice < 0)                                  throw new InvalidArgumentException('Invalid property order['.$ticket.'].closePrice: '.$closePrice.' (not non-negative)');
        $order->closePrice = !$closePrice ? null : $closePrice;

        $closeTime = $properties['closeTime'];                // FXT timestamp
        if (!is_int($closeTime))                              throw new IllegalTypeException('Illegal type of property order['.$ticket.'].closeTime: '.getType($closeTime));
        if ($closeTime < 0)                                   throw new InvalidArgumentException('Invalid property order['.$ticket.'].closeTime: '.$closeTime.' (not positive)');
        if      ($closeTime && !$closePrice)                  throw new InvalidArgumentException('Invalid properties order['.$ticket.'].closePrice|closeTime: '.$closePrice.'|'.$closeTime.' (mis-match)');
        else if (!$closeTime && $closePrice)                  throw new InvalidArgumentException('Invalid properties order['.$ticket.'].closePrice|closeTime: '.$closePrice.'|'.$closeTime.' (mis-match)');
        if ($closeTime) {
            if (isForexWeekend($closeTime, 'FXT'))            throw new InvalidArgumentException('Invalid property order['.$ticket.'].closeTime: '.$closeTime.' (not a weekday)');
            if ($closeTime < $openTime)                       throw new InvalidArgumentException('Invalid properties order['.$ticket.'].openTime|closeTime: '.$openTime.'|'.$closeTime.' (mis-match)');
        }
        $order->closeTime = !$closeTime ? null : gmDate('Y-m-d H:i:s', $closeTime);

        $commission = $properties['commission'];
        if (!is_float($commission))                           throw new IllegalTypeException('Illegal type of property order['.$ticket.'].commission: '.getType($commission));
        $order->commission = round($commission, 2);

        $swap = $properties['swap'];
        if (!is_float($swap))                                 throw new IllegalTypeException('Illegal type of property order['.$ticket.'].swap: '.getType($swap));
        $order->swap = round($swap, 2);

        $profit = $properties['profit'];
        if (!is_float($profit))                               throw new IllegalTypeException('Illegal type of property order['.$ticket.'].profit: '.getType($profit));
        $order->profit = round($profit, 2);

        $magicNumber = $properties['magicNumber'];
        if (!is_int($magicNumber))                            throw new IllegalTypeException('Illegal type of property order['.$ticket.'].magicNumber: '.getType($magicNumber));
        if ($magicNumber < 0)                                 throw new InvalidArgumentException('Invalid property order['.$ticket.'].magicNumber: '.$magicNumber.' (not non-negative)');
        $order->magicNumber = !$magicNumber ? null : $magicNumber;

        $comment = $properties['comment'];
        if (!is_string($comment))                             throw new IllegalTypeException('Illegal type of property order['.$ticket.'].comment: '.getType($comment));
        $comment = trim($comment);
        if (strLen($comment) > MT4::MAX_ORDER_COMMENT_LENGTH) throw new InvalidArgumentException('Invalid property order['.$ticket.'].comment: "'.$comment.'" (length violation)');
        $order->comment = strLen($comment) ? $comment : null;

        return $order;
    }


    /** @return int - primary key */
    public function getId() { return $this->id; }

    /** @return int - ticket number */
    public function getTicket() { return $this->ticket; }

    /** @return string - order type: Buy|Sell */
    public function getType() { return $this->type; }

    /** @return float - lot size */
    public function getLots() { return $this->lots; }

    /** @return string - symbol */
    public function getSymbol() { return $this->symbol; }

    /** @return float - open price */
    public function getOpenPrice() { return $this->openPrice; }

    /** @return string - open time (server timezone) */
    public function getOpenTime() { return $this->openTime; }

    /** @return float - stoploss price */
    public function getStopLoss() { return $this->stopLoss; }

    /** @return float - takeprofit price */
    public function getTakeProfit() { return $this->takeProfit; }

    /** @return float - close price */
    public function getClosePrice() { return $this->closePrice; }

    /** @return string - close time (server timezone) */
    public function getCloseTime() { return $this->closeTime; }

    /** @return float - commission */
    public function getCommission() { return $this->commission; }

    /** @return float - swap */
    public function getSwap() { return $this->swap; }

    /** @return float - gross profit */
    public function getProfit() { return $this->profit; }

    /** @return int - magic number */
    public function getMagicNumber() { return $this->magicNumber; }

    /** @return string - order comment */
    public function getComment() { return $this->comment; }


    /**
     * Whether or not this order ticket represents a position and not a pending order.
     *
     * @return bool
     */
    public function isPosition() {
        return ((strCompareI($this->type, 'Buy') || strCompareI($this->type, 'Sell')) && $this->openTime > 0);
    }


    /**
     * Whether or not this ticket is closed.
     *
     * @return bool
     */
    public function isClosed() {
        return ($this->closeTime > 0);
    }


    /**
     * Whether or not this ticket represents a closed position.
     *
     * @return bool
     */
    public function isClosedPosition() {
        return ($this->isPosition() && $this->isClosed());
    }


    /**
     * Insert pre-processing hook. Assigns a {@link Test} id as long as this is not yet done automatically by the ORM.
     *
     * @return $this
     */
    protected function beforeInsert() {
        if (!$this->test_id)
            $this->test_id = $this->test->getId();
        return $this;
    }
}
