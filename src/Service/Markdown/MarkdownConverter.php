<?php

namespace App\Service\Markdown;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Output\RenderedContentInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MarkdownConverter implements MarkdownConverterInterface
{
    private ConverterInterface $converter;
    private CacheInterface $cache;
    private int $cacheTTL;

    public function __construct(CacheInterface $cache, int $cacheTTL)
    {
        $this->converter = new CommonMarkConverter();
        $this->cache = $cache;
        $this->cacheTTL = $cacheTTL;
    }

    public function convert(string $input): RenderedContentInterface
    {
        $cacheKey = $this->getCacheKey($input);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($input) {
            $item->expiresAfter($this->cacheTTL);
            return $this->converter->convert($input);
        });
    }

    private function getCacheKey(string $input): string
    {
        return 'question'.md5($input);
    }
}
