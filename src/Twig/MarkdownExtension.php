<?php

namespace App\Twig;

use App\Service\Markdown\MarkdownConverterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    private MarkdownConverterInterface $markdownConverter;

    public function __construct(MarkdownConverterInterface $markdownConverter)
    {
        $this->markdownConverter = $markdownConverter;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    public function parseMarkdown(string $value): string
    {
        return $this->markdownConverter->convert($value)->getContent();
    }
}
