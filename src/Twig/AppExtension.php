<?php

namespace App\Twig;

use App\Service\FlashMessageService\FlashMessageService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private FlashMessageService $flashMessageService;

    public function __construct(FlashMessageService $flashMessageService)
    {
        $this->flashMessageService = $flashMessageService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_flashes', [$this->flashMessageService, 'getAll']),
        ];
    }
}
