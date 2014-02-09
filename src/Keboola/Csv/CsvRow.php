<?php

namespace Keboola\Csv;

class CsvRow implements \ArrayAccess, \Countable
{
    
    /**
     * Linear array containing all data
     * @var array
     */
    private $RowData;

    /**
     * Associative array containing all data
     * @var array
     */
    private $AssocRowData;
    
    /**
     * @var array
     */
    private $HeaderRow;
    
    /**
     * @var integer
     */
    private $Count;
    
    /**
     * @var string
     */
    private $Delimiter;
    
    /**
     * @var string
     */
    private $Enclosure;
    
    /**
     *
     * @var string
     */
    private $EscapedBy;
    
    /**
     * @var string
     */
    private $LineBreak;
    
    /**
     * @param array $HeaderRow
     * @param array $RowData
     * @param string $Delimiter Fields delimiter (Defaults to comma)
     * @param string $Enclosure Fields enclousure (Defaults to double quotes)
     * @param string $EscapedBy Fields escape caracter (Defaults to empty string)
     * @param string $LineBreak String representation of the line breaker (Defaults to PHP_EOL)
     */
    public function __construct(array $RowData, array $HeaderRow, $Delimiter = self::DEFAULT_DELIMITER, $Enclosure = self::DEFAULT_ENCLOSURE, $EscapedBy = "", $LineBreak = PHP_EOL) {
        $this->RowData = $RowData;
        $this->HeaderRow = $HeaderRow;
        $this->AssocRowData = array_combine($HeaderRow, $RowData);
        $this->Delimiter = $Delimiter;
        $this->Enclosure = $Enclosure;
        $this->EscapedBy = $EscapedBy;
        $this->LineBreak = $LineBreak;
    }
    
    public function __toString() {
        return $this->toString();
    }
    
    /**
     * @return string The string representation of the row
     */
    public function toString() {
        return implode($this->Delimiter, $this->RowData);
    }
    
    /**
     * @return string
     */
    public function getDelimiter () {
        return $this->Delimiter;
    }

    /**
     * @return string
     */
    public function getEnclosure () {
        return $this->Enclosure;
    }

    /**
     * @return string
     */
    public function getEscapedBy () {
        return $this->EscapedBy;
    }

    /**
     * @return string
     */
    public function getLineBreak () {
        return $this->LineBreak;
    }

    /**
     * @return integer 
     */
    public function count(){
        if ($this->Count === null) {
            $this->Count = count($this->RowData);
        }
        
        return $this->Count;
    }

    public function offsetExists ($offset) {
        return (array_key_exists($offset, $this->RowData) OR array_key_exists($offset, $this->HeaderRow));
    }

    public function offsetGet ($offset) {
        if (array_key_exists($offset, $this->RowData)) {
            return $this->RowData[$offset];
        } else {
            return $this->AssocRowData[$offset];
        }
    }

    public function offsetSet ($offset, $value) {
        if (array_key_exists($offset, $this->RowData)) {
            $this->RowData[$offset] = $value;
        } else {
            $this->AssocRowData[$offset] = $value;
        }
    }

    public function offsetUnset ($offset) {
        if (array_key_exists($offset, $this->RowData)) {
            unset($this->RowData[$offset]);
        } else {
            unset($this->AssocRowData[$offset]);
        }
    }

}