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
    protected $timeKey;
    protected $lvlKey;
    protected $chanKey;
    protected $msgKey;

    protected $timeKeyValid = true;
    protected $lvlKeyValid = true;
    protected $chanKeyValid = true;
    protected $msgKeyValid = true;
    
    /**
     * Constructor params can be used to customise the keys that are used in the formatted output
     * for the standard (non-context/extra) log record fields.
     * 
     * Set any of these params to an empty string or null to not include that field in the output.
     * 
     * Note that these standard log fields will take precedence over fields with the same name(s)
     * in the context or extra arrays when formatting log records. i.e. with the default names, a
     * context field with the name "msg" would not be included in the output from `format`.
     * 
     * @param string $dateTimeKey Key to use for the log timestamp.
     * @param string $levelKey    Key to use for the log level.
     * @param string $channelKey  Key to use for the log channel name.
     * @param string $messageKey  Key to use for the log message.
     */
    public function __construct(
        $dateTimeKey = 'ts',
        $levelKey = 'lvl',
        $channelKey = 'chan',
        $messageKey = 'msg'
    ) {
        $this->timeKey = trim($dateTimeKey);
        $this->lvlKey = trim($levelKey);
        $this->chanKey = trim($channelKey);
        $this->msgKey = trim($messageKey);
        $this->timeKeyValid = $this->isValidIdent($this->timeKey);
        $this->lvlKeyValid = $this->isValidIdent($this->lvlKey);
        $this->chanKeyValid = $this->isValidIdent($this->chanKey);
        $this->msgKeyValid = $this->isValidIdent($this->msgKey);
        $this->dateFormat = \DateTime::RFC3339;
    }
    
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $vars = parent::format($record);
        
        $pairs = [];
        if ($this->timeKeyValid) {
            $pairs[$this->timeKey] = $this->timeKey.'='.$vars['datetime'];
        }
        if ($this->lvlKeyValid) {
            $pairs[$this->lvlKey] = $this->lvlKey.'='.$vars['level_name'];
        }
        if ($this->chanKeyValid) {
            $pairs[$this->chanKey] = $this->chanKey.'='.$this->stringifyVal($vars['channel']);
        }
        if ($this->msgKeyValid) {
            $pairs[$this->msgKey] = $this->msgKey.'='.$this->stringifyVal($vars['message']);
        }

        foreach ($vars['context'] as $ctxKey => $ctxVal) {
            if (array_key_exists($ctxKey, $pairs)) {
                continue;
            }
            if (! $this->isValidIdent($ctxKey)) {
                continue;
            }
            $pairs[$ctxKey] = $ctxKey.'='.$this->stringifyVal($ctxVal);
        }

        foreach ($vars['extra'] as $extraKey => $extraVal) {
            if (array_key_exists($extraKey, $pairs)) {
                continue;
            }
            if (! $this->isValidIdent($extraKey)) {
                continue;
            }
            $pairs[$extraKey] = $extraKey.'='.$this->stringifyVal($extraVal);
        }
        
        return implode(' ', $pairs)."\n";
    }
    
    /**
     * {@inheritdoc}
     */
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

            if (0 === strlen($val)) {
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