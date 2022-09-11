<?php

namespace App\Service\Markdown;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Output\RenderedContentInterface;
use Symfony\Contracts\Cache\CacheInterface;

class MarkdownConverter implements MarkdownConverterInterface
{
    private ConverterInterface $converter;
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->converter = new CommonMarkConverter();
        $this->cache = $cache;
    }

    public function convert(string $input): RenderedContentInterface
    {
        $cacheKey = $this->getCacheKey($input);

        return $this->cache->get($cacheKey, function () use ($input) {
            return $this->converter->convert($input);
        });
    }

    private function getCacheKey(string $input): string
    {
        return 'question'.md5($input);
    }
}
