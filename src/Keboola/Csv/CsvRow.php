<?php

namespace Keboola\Csv;

use ArrayAccess;
use Countable;
use Keboola\Csv\Exception\OutOfRangeException;

class CsvRow implements ArrayAccess, Countable
{

    /**
     * Linear array containing all data
     * @var array
     */
    protected $RowData;

    /**
     * Associative array containing all data
     * @var array
     */
    protected $AssocRowData;

    /**
     * @var integer
     */
    protected $Count;

    /**
     * @var string
     */
    protected $Delimiter;

    /**
     * @var string
     */
    protected $Enclosure;

    /**
     *
     * @var string
     */
    protected $EscapedBy;

    /**
     * @var string
     */
    protected $LineBreak;

    /**
     * @param array $RowData Key=>Value "equals to" Column=>Value
     * @param string $Delimiter Fields delimiter (Defaults to comma)
     * @param string $Enclosure Fields enclousure (Defaults to double quotes)
     * @param string $EscapedBy Fields escape caracter (Defaults to empty string)
     * @param string $LineBreak String representation of the line breaker (Defaults to PHP_EOL)
     */
    public function __construct(array $RowData = array(), $Delimiter = self::DEFAULT_DELIMITER, $Enclosure = self::DEFAULT_ENCLOSURE, $EscapedBy = "", $LineBreak = PHP_EOL)
    {
        $this->RowData = $RowData;
        $this->Delimiter = $Delimiter;
        $this->Enclosure = $Enclosure;
        $this->EscapedBy = $EscapedBy;
        $this->LineBreak = $LineBreak;
    }

    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string The string representation of the row
     */
    public function toString()
    {
        return implode($this->Delimiter, $this->RowData) . $this->getLineBreak();
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->Delimiter;
    }

    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->Enclosure;
    }

    /**
     * @return string
     */
    public function getEscapedBy()
    {
        return $this->EscapedBy;
    }

    /**
     * @return string
     */
    public function getLineBreak()
    {
        return $this->LineBreak;
    }

    /**
     * @return integer 
     */
    public function count()
    {
        if ($this->Count === null) {
            $this->Count = count($this->RowData);
        }

        return $this->Count;
    }

    /**
     * @param string|integer $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return (array_key_exists($offset, $this->RowData) OR array_key_exists($offset, array_values($this->RowData)));
    }

    /**
     * @param string|integer $offset
     * @return string
     * @throws OutOfRangeException
     */
    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->RowData)) {
            return $this->RowData[$offset];
        } elseif (array_key_exists($offset, $array_values = array_values($this->RowData))) {
            return $this->RowData[$array_values[$offset]];
        } else {
            throw new OutOfRangeException('Invalid offset (' . $offset . ').');
        }
    }

    /**
     * @param string|integer $offset
     * @param string|integer $value
     */
    public function offsetSet($offset, $value)
    {
        if (array_key_exists($offset, $this->RowData)) {
            $this->RowData[$offset] = $value;
        } elseif (array_key_exists($offset, $array_values = array_values($this->RowData))) {
            $this->RowData[$array_values[$offset]] = $value;
        } else {
            throw new OutOfRangeException('Invalid offset (' . $offset . ').');
        }
    }

    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->RowData)) {
            unset($this->RowData[$offset]);
        } elseif (array_key_exists($offset, $array_values = array_values($this->RowData))) {
            $array_values[$offset] = $value;
        } else {
            throw new OutOfRangeException('Invalid offset (' . $offset . ').');
        }
    }

    /**
     * @return array
     */
    public function getRowData()
    {
        return $this->RowData;
    }
    
    /**
     * Alias to CsvRow::getRowData()
     */
    public function toArray() {
        return $this->getRowData();
    }

}
