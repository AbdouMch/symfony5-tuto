<?php

namespace App\DataList\Filter;

final class ScopeConstraint
{
    public FieldDefinition $definition;
    public string $operator;
    public string $value;

    public function __construct(FieldDefinition $definition, string $operator, string $value)
    {
        $this->definition = $definition;
        $this->operator   = $operator;
        $this->value      = $value;
    }
}
