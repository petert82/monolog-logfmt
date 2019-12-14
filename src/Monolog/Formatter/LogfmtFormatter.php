<?php
declare(strict_types=1);

namespace Petert82\Monolog\Formatter;

use DateTime;
use Monolog\Formatter\NormalizerFormatter;
use function is_bool;
use function is_scalar;
use function var_export;

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
    protected ?string $timeKey;
    protected ?string $lvlKey;
    protected ?string $chanKey;
    protected ?string $msgKey;

    protected bool $timeKeyValid = true;
    protected bool $lvlKeyValid = true;
    protected bool $chanKeyValid = true;
    protected bool $msgKeyValid = true;

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
     * @param string|null $dateTimeKey Key to use for the log timestamp.
     * @param string|null $levelKey Key to use for the log level.
     * @param string|null $channelKey Key to use for the log channel name.
     * @param string|null $messageKey Key to use for the log message.
     * @param string|null $dateFormat The format of the timestamp: should be a format supported by DateTime::format
     */
    public function __construct(
        ?string $dateTimeKey = 'ts',
        ?string $levelKey = 'lvl',
        ?string $channelKey = 'chan',
        ?string $messageKey = 'msg',
        ?string $dateFormat = DateTime::RFC3339
    ) {
        $this->timeKey = $dateTimeKey ? trim($dateTimeKey) : null;
        $this->lvlKey = $levelKey ? trim($levelKey) : null;
        $this->chanKey = $channelKey ? trim($channelKey) : null;
        $this->msgKey = $messageKey ? trim($messageKey) : null;
        $this->timeKeyValid = $this->isValidIdent($this->timeKey);
        $this->lvlKeyValid = $this->isValidIdent($this->lvlKey);
        $this->chanKeyValid = $this->isValidIdent($this->chanKey);
        $this->msgKeyValid = $this->isValidIdent($this->msgKey);

        parent::__construct($dateFormat);
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

    protected function stringifyVal($val): string
    {
        if ($this->isValidIdent($val)) {
            return (string) $val;
        }

        return $this->convertToString($val);
    }

    protected function isValidIdent($val): bool
    {
        if (is_string($val)) {
            // Control chars, DEL, ", =, space
            if (preg_match('/[\x00-\x1F\x7F\"\=\s]/u', $val)) {
                return false;
            }

            return $val !== '';
        }

        if (is_bool($val)) {
            return false;
        }

        return is_scalar($val);
    }

    protected function convertToString($data): string
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        return $this->toJson($data, true);
    }
}
