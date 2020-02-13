<?php


namespace MortenScheel\LaravelBlitz\Transformers;


use MortenScheel\LaravelBlitz\Concerns\ReportsErrors;

class AddClassImportTransformer implements Transformer
{
    use ReportsErrors;

    /**
     * @var string
     */
    private $original;
    /**
     * @var string
     */
    private $basename;
    /**
     * @var string
     */
    private $import;

    /**
     * AddClassImportTransformer constructor.
     * @param string $original
     * @param string $basename
     * @param string $import
     */
    public function __construct(string $original, string $basename, string $import)
    {
        $this->original = $original;
        $this->basename = $basename;
        $this->import = $import;
    }

    public function transform(): ?string
    {
        $pattern = \sprintf("~(\nclass %s)~mu", $this->basename);
        if (\preg_match($pattern, $this->original, $match, \PREG_OFFSET_CAPTURE)) {
            $offset = $match[1][1];
            $before = \mb_substr($this->original, 0, $offset);
            $after = \mb_substr($this->original, $offset);
            return \sprintf("%suse %s;\n%s", $before, $this->import, $after);
        }
        $this->error = "Couldn't add class import";
        return null;
    }

    public function isTransformationRequired(): bool
    {
        return \mb_stripos($this->original, "use {$this->import}") === false;
    }
}