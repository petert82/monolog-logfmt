<?php
namespace Petert82\Monolog\Formatter;

use Monolog\Formatter\NormalizerFormatter;

/**
 * Formats records into a logfmt string.
 * 
 * @see https://brandur.org/logfmt
 * @see https://godoc.org/github.com/kr/logfmt
 *
 * @author Peter Thompson <peter.thompson@dunelm.org.uk>
 */
class LogfmtFormatter extends NormalizerFormatter
{
    protected $lvlKey = 'lvl';
    protected $chanKey = 'chan';
    protected $msgKey = 'msg';
    protected $timeKey = 'ts';
    
    public function __construct()
    {
        $this->dateFormat = \DateTime::RFC3339;
    }
    
    public function format(array $record)
    {
        $vars = parent::format($record);
        
        $pairs = [
            $this->timeKey.'='.$vars['datetime'],
            $this->lvlKey.'='.$vars['level_name'],
            $this->chanKey.'='.$this->stringifyVal($vars['channel']),
            $this->msgKey.'='.$this->stringifyVal($vars['message']),
        ];

        foreach ($vars['context'] as $ctxKey => $ctxVal) {
            if (! $this->isValidIdent($ctxKey)) {
                continue;
            }
            $pairs[] = $this->stringifyVal($ctxKey).'='.$this->stringifyVal($ctxVal);
        }

        foreach ($vars['extra'] as $extraKey => $extraVal) {
            if (array_key_exists($extraKey, $vars['context'])) {
                continue;
            }
            if (! $this->isValidIdent($extraKey)) {
                continue;
            }
            $pairs[] = $this->stringifyVal($extraKey).'='.$this->stringifyVal($extraVal);
        }
        
        $line = implode(' ', $pairs)."\n";
        
        return $line;
    }
    
    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    protected function stringifyVal($val)
    {
        if ($this->isValidIdent($val)) {
            return (string) $val;
        }

        return $this->convertToString($val);
    }

    protected function isValidIdent($val)
    {
        if (is_string($val)) {
            // Control chars, DEL, ", =, space
            if (preg_match('/[\x00-\x1F\x7F\"\=\s]/u', $val)) {
                return false;
            }

            return true;
        }

        if (is_bool($val)) {
            return false;
        }

        if (is_scalar($val)) {
            return true;
        }

        return false;
    }

    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            $string = (string) $data;
        }

        $string = $this->toJson($data, true);

        return $string;
    }
}