<?php

namespace App\DataList;

use App\DataList\Filter\FieldDefinition;
use App\DataList\Filter\ScopeConstraint;

interface DataListConfigurationInterface
{
    public function getEntityClass(): string;

    public function getRootAlias(): string;

    public function getDefaultSortBy(): string;

    /** @return FieldDefinition[] */
    public function getFields(): array;

    /** @return ScopeConstraint[] */
    public function getScope(): array;
}
