<?php

namespace MortenScheel\LaravelBlitz\Transformers;

use MortenScheel\LaravelBlitz\Concerns\ReportsErrors;

class AppendPhpArrayTransformer implements Transformer
{
    use ReportsErrors;
    /**
     * @var string
     */
    private $original;
    /**
     * @var string
     */
    private $variable;
    /**
     * @var string
     */
    private $value;

    /**
     * AppendPhpArrayTransformer constructor.
     * @param string $original
     * @param string $variable
     * @param string $value
     */
    public function __construct(string $original, string $variable, string $value)
    {
        $this->original = $original;
        // Ensure dollar sign is escaped
        $this->variable = \preg_replace("/(?<!\\\\)\\\$/", "\\\\\$$1", \trim($variable));
        $this->value = $value;
    }

    public function transform(): ?string
    {
        if ($variable_capture_match = $this->captureVariableBody()) {
            [$array_content, $array_offset] = $variable_capture_match[1];
            // Capture final line and indentation
            if (\preg_match('~\n( *)(\S+)\s+$~u', $array_content, $final_line_match, \PREG_OFFSET_CAPTURE)) {
                $indent = $final_line_match[1][0];
                [$final_line, $final_line_offset] = $final_line_match[2];
                $offset = $array_offset + $final_line_offset + \mb_strlen($final_line);
                $before = \mb_substr($this->original, 0, $offset);
                if (!\preg_match('~,\s*$~', $before)) {
                    $before .= ',';
                }
                $after = \mb_substr($this->original, $offset);
                return \sprintf("%s\n%s%s,%s", $before, $indent, $this->value, $after);
            }
        }
        $this->error = "{$this->variable} not found";
        return false;
    }

    /**
     * @return array|null
     */
    private function captureVariableBody()
    {
        $variable_capture_pattern = \sprintf('~%s\s*=\s*\[([^\]]+)]~mu', $this->variable);
        \preg_match($variable_capture_pattern, $this->original, $match, \PREG_OFFSET_CAPTURE);
        return $match;
    }

    public function isTransformationRequired(): bool
    {
        if ($variable_capture_match = $this->captureVariableBody()) {
            return \mb_stripos($variable_capture_match[1][0], $this->value) !== false;
        }
        return true;
    }
}