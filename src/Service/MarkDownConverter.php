<?php

namespace App\Service;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Output\RenderedContentInterface;

class MarkDownConverter implements ConverterInterface
{
    private ConverterInterface $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter();
    }

    public function convert(string $input): RenderedContentInterface
    {
        return $this->converter->convert($input);
    }
}
