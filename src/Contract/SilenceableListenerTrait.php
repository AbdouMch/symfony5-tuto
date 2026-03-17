<?php

namespace App\Contract;

trait SilenceableListenerTrait
{
    private bool $enabled = true;

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
