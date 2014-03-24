<?php

/**
 *
 * User: Martin Halamíček
 * Date: 13.4.12
 * Time: 15:31
 *
 */

namespace Keboola\Csv;

use Iterator;
use Keboola\Csv\Exception;
use Keboola\Csv\InvalidArgumentException;
use SplFileObject;

class CsvFile extends SplFileObject implements Iterator
{

    const DEFAULT_DELIMITER = ',';
    const DEFAULT_ENCLOSURE = '"';
    const DEFAULT_LINE_BREAK = PHP_EOL;
    
    protected $allowedLineBreaks = array(
        "\r\n", // win
        "\r", // mac
        "\n", // unix
    );

    protected $_delimiter;
    protected $_enclosure;
    protected $_escapedBy;
    protected $_filePointer;
    protected $_rowCounter = 0;
    protected $_currentRow;
    protected $_lineBreak;
    protected $headerRow;

    /**
     * @param string $fileName The filename
     * @param string $delimiter Fields delimiter (Defaults to comma)
     * @param string $enclosure Fields enclousure (Defaults to double quotes)
     * @param string $escapedBy Fields escape caracter (Defaults to empty string)
     * @param string $lineDelimiter EOL delimiter (Defaults to "\r\n")
     */
    public function __construct($fileName, $delimiter = self::DEFAULT_DELIMITER, $enclosure = self::DEFAULT_ENCLOSURE, $escapedBy = "")
    {
        parent::__construct($fileName);

        $this->_escapedBy = $escapedBy;
        $this->_setDelimiter($delimiter);
        $this->_setEnclosure($enclosure);
        $this->_setHeader();
        $this->_lineBreak = $this->_detectLineBreak();
    }

    /**
     * @param string $delimiter The FIELD delimiter
     * @return CsvFile
     */
    protected function _setDelimiter($delimiter)
    {
        $this->_validateDelimiter($delimiter);
        $this->_delimiter = $delimiter;
        return $this;
    }

    protected function _validateDelimiter($delimiter)
    {
        if (strlen($delimiter) > 1) {
            throw new InvalidArgumentException(
                "Delimiter must be a single character. \"$delimiter\" received", 
                Exception::INVALID_PARAM, 
                NULL, 
                'invalidParam'
            );
        }

        if (strlen($delimiter) == 0) {
            throw new InvalidArgumentException(
                "Delimiter cannot be empty.",
                Exception::INVALID_PARAM,
                NULL,
                'invalidParam'
            );
        }
    }

    public function getDelimiter()
    {
        return $this->_delimiter;
    }

    public function getEnclosure()
    {
        return $this->_enclosure;
    }

    public function getEscapedBy()
    {
        return $this->_escapedBy;
    }

    /**
     * @param $enclosure
     * @return CsvFile
     */
    protected  function _setEnclosure($enclosure)
    {
        $this->_validateEnclosure($enclosure);
        $this->_enclosure = $enclosure;
        return $this;
    }

    protected function _validateEnclosure($enclosure)
    {
        if (strlen($enclosure) > 1) {
            throw new InvalidArgumentException(
                "Enclosure must be a single character. \"$enclosure\" received", 
                Exception::INVALID_PARAM,
                NULL, 
                'invalidParam'
            );
        }
    }

    public function getColumnsCount()
    {
        return count($this->getHeader());
    }

    /**
     * @return CsvRow
     */
    public function getHeader()
    {
        return $this->headerRow;
    }

    protected function _setHeader()
    {
        if ($this->isFile() OR $this->isLink()) {
            $curent_line = $this->key();

            $this->rewind();
            $header = $this->current();

            $this->headerRow = $header;

            if ($curent_line != 0) {
                $this->seek($curent_line);
            }
        } else {
            $this->headerRow = new CsvRow();
        }
    }

    public function writeRow($row)
    {
        $str = $this->rowToStr($row);
        $ret = $this->fwrite($str);

        /* According to http://php.net/fwrite the fwrite() function
         should return false on error. However not writing the full 
         string (which may occur e.g. when disk is full) is not considered 
         as an error. Therefore both conditions are necessary. */
        if (($ret === false) || (($ret === 0) && (strlen($str) > 0)))  {
            throw new Exception(
                "Cannot open file $this",
                Exception::WRITE_ERROR, 
                NULL, 
                'writeError'
            );
        }
    }

    /**
     * @param array|CsvRow $row
     * @return type
     */
    public function rowToStr($row)
    {
        if (!$row instanceof CsvRow) {
            $return = array();
            foreach ($row as $column) {
                $return[] = $this->getEnclosure() . 
                    str_replace(
                        $this->getEnclosure(), 
                        str_repeat(
                            $this->getEnclosure(),
                            2
                        ), 
                        $column
                    ) . $this->getEnclosure();
            }

            return implode($this->getDelimiter(), $return) . $this->getLineDelimiter();
        } else {
            return $row->toString();
        }
    }

    public function getLineBreak()
    {
        if (!$this->_lineBreak) {
            $this->_lineBreak = $this->_detectLineBreak();
        }
        return $this->_lineBreak;
    }

    public function getLineBreakAsText()
    {
        return trim(json_encode($this->getLineBreak()), '"');
    }

    public function validateLineBreak()
    {
        $lineBreak = $this->getLineBreak();
        if (in_array($lineBreak, array("\r\n", "\n"))) {
            return $lineBreak;
        }

        throw new InvalidArgumentException("Invalid line break. Please use unix \\n or win \\r\\n line breaks.", Exception::INVALID_PARAM, NULL, 'invalidParam');
    }

    protected function _detectLineBreak()
    {
        if ($this->isFile() OR $this->isLink()) {
            return in_array($this->getHeader()->getLineBreak(), $this->allowedLineBreaks);
        } else {
            return CsvFile::DEFAULT_LINE_BREAK;
        }
    }

    protected function _closeFile()
    {
        if (is_resource($this->_filePointer)) {
            fclose($this->_filePointer);
        }
    }

    public function __destruct()
    {
        $this->_closeFile();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return CsvRow Can return any type.
     */
    public function current()
    {
        return $this->_currentRow;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->_currentRow = $this->_readLine();
        $this->_rowCounter++;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return scalar scalar on success, integer
     * 0 on failure.
     */
    public function key()
    {
        return $this->_rowCounter;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->_currentRow !== false;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        parent::rewind();
        $this->_currentRow = $this->_readLine();
        $this->_rowCounter = 0;
    }

    /**
     * @return CsvRow
     */
    protected function _readLine()
    {
        $this->validateLineBreak();

        // allow empty enclosure hack
        $enclosure = !$this->getEnclosure() ? chr(0) : $this->getEnclosure();
        $escapedBy = !$this->_escapedBy ? chr(0) : $this->_escapedBy;
        
        $RowData = $this->fgetcsv($this->getDelimiter(), $enclosure, $escapedBy);
        $Columns = $this->getHeader()->toArray();
        
        return new CsvRow(
            array_combine($Columns, $RowData),
            $this->getDelimiter(),
            $this->getEnclosure(),
            $this->getEscapedBy(),
            $this->getLineBreak()
        );
    }

}
