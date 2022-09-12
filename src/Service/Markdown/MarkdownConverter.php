<?php

namespace App\Service\Markdown;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Output\RenderedContentInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MarkdownConverter implements MarkdownConverterInterface
{
    private ConverterInterface $converter;
    private CacheInterface $cache;
    private int $cacheTTL;
    private LoggerInterface $markdownLogger;

    public function __construct(CacheInterface $cache, LoggerInterface $markdownLogger, int $cacheTTL)
    {
        $this->converter = new CommonMarkConverter();
        $this->cache = $cache;
        $this->cacheTTL = $cacheTTL;
        $this->markdownLogger = $markdownLogger;
    }

    public function convert(string $input): RenderedContentInterface
    {
        $cacheKey = $this->getCacheKey($input);
        throw new \Exception('bad things happened!!');
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($input) {
            $item->expiresAfter($this->cacheTTL);
            $converted = $this->converter->convert($input);
            $this->markdownLogger->info("converting", [$input, $converted->getContent()]);
            return $converted;
        });
    }

    private function getCacheKey(string $input): string
    {
        return 'question'.md5($input);
    }
}
