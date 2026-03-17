<?php

namespace App\Contract;

interface SilenceableListenerInterface
{
    public function disable(): void;

    public function isEnabled(): bool;
}
