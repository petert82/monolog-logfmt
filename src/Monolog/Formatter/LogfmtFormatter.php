<?php

declare(strict_types=1);

namespace Petert82\Monolog\Formatter;

use DateTime;
use DateTimeInterface;
use JsonSerializable;
use Monolog\Formatter\NormalizerFormatter;
use Throwable;

use function is_bool;
use function is_scalar;
use function var_export;

/**
 * Formats records into a logfmt string.
 *
 * @see https://brandur.org/logfmt
 * @see https://godoc.org/github.com/kr/logfmt
 */
class LogfmtFormatter extends NormalizerFormatter
{
    protected ?string $timeKey;
    protected ?string $lvlKey;
    protected ?string $chanKey;
    protected ?string $msgKey;
    protected ?string $formattedRecordTerminator = "\n";
    protected bool $flattenStructures = false;

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
     * @param null|string $dateTimeKey Key to use for the log timestamp.
     * @param null|string $levelKey Key to use for the log level.
     * @param null|string $channelKey Key to use for the log channel name.
     * @param null|string $messageKey Key to use for the log message.
     * @param null|string $dateFormat The format of the timestamp: should be a format supported by DateTime::format
     * @param null|string $formattedRecordTerminator The suffix to append after the formatted record. Defaults to a newline. (useful to set to null for syslog)
     * @param null|bool $flattenStructures Whether to flatten nested arrays and objects into logfmt-compatible key-value pairs
     */
    public function __construct(
        ?string $dateTimeKey = 'ts',
        ?string $levelKey = 'lvl',
        ?string $channelKey = 'chan',
        ?string $messageKey = 'msg',
        ?string $dateFormat = DateTime::RFC3339,
        ?string $formattedRecordTerminator = "\n",
        ?bool $flattenStructures = false
    ) {
        $this->timeKey = $dateTimeKey ? trim($dateTimeKey) : null;
        $this->lvlKey = $levelKey ? trim($levelKey) : null;
        $this->chanKey = $channelKey ? trim($channelKey) : null;
        $this->msgKey = $messageKey ? trim($messageKey) : null;
        $this->timeKeyValid = $this->isValidIdent($this->timeKey);
        $this->lvlKeyValid = $this->isValidIdent($this->lvlKey);
        $this->chanKeyValid = $this->isValidIdent($this->chanKey);
        $this->msgKeyValid = $this->isValidIdent($this->msgKey);
        $this->formattedRecordTerminator = $formattedRecordTerminator;
        $this->flattenStructures = (bool) $flattenStructures;

        parent::__construct($dateFormat);
    }

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

            $this->addValue($pairs, $ctxKey, $ctxVal);
        }

        foreach ($vars['extra'] as $extraKey => $extraVal) {
            if (array_key_exists($extraKey, $pairs)) {
                continue;
            }
            if (! $this->isValidIdent($extraKey)) {
                continue;
            }

            $this->addValue($pairs, $extraKey, $extraVal);
        }

        return implode(' ', $pairs).$this->formattedRecordTerminator;
    }

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
            if (preg_match('/[\x00-\x1F\x7F\"\=\s]/', $val)) {
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

    /**
     * {@inheritdoc}
     *
     * Overridden to handle objects without including class names in flattened keys
     */
    protected function normalize($data, int $depth = 0)
    {
        // Handle regular objects specially - extract their properties without class name
        if (is_object($data)) {
            if (($data instanceof DateTimeInterface) || ($data instanceof Throwable) || ($data instanceof JsonSerializable) || method_exists($data, '__toString')) {
                return parent::normalize($data, $depth);
            }

            // Get the object's properties without the class name wrapper
            return json_decode($this->toJson($data, true), true);
        }

        return parent::normalize($data, $depth);
    }

    /**
     * Recursively flatten complex values (arrays and objects) into key-value pairs.
     *
     * @param mixed $value
     */
    protected function addValue(array &$pairs, string $keyPrefix, $value): void
    {
        if (!$this->flattenStructures) {
            $pairs[$keyPrefix] = $keyPrefix.'='.$this->stringifyVal($value);

            return;
        }

        // For arrays, iterate and add each entry with a prefixed key
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $newKey = $keyPrefix.'_'.$k;
                $this->addValue($pairs, $newKey, $v);
            }

            return;
        }

        // For objects, use the normalizer which handles special cases like DateTime
        if (is_object($value)) {
            try {
                $normalized = $this->normalize($value);

                if (is_array($normalized)) {
                    foreach ($normalized as $k => $v) {
                        $newKey = $keyPrefix.'_'.$k;
                        $this->addValue($pairs, $newKey, $v);
                    }
                } else {
                    // If normalization returned a scalar
                    $pairs[$keyPrefix] = $keyPrefix.'='.$this->stringifyVal($normalized);
                }
            } catch (Throwable $e) {
                // If normalization fails, use a placeholder
                $pairs[$keyPrefix] = $keyPrefix.'="[object conversion error]"';
            }

            return;
        }

        $pairs[$keyPrefix] = $keyPrefix.'='.$this->stringifyVal($value);
    }
}
