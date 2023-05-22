<?php

namespace App\Model;

class Spell
{
    public string $name;
    public string $constantCode;
    public string $ownerName;
    /**
     * @var array<Question>
     */
    public array $questions;
}
