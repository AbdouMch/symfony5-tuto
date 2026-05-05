<?php

namespace App\DataList;

use App\DataList\Filter\FieldDefinition;

interface DataListConfigurationInterface
{
    public function getEntityClass(): string;

    public function getRootAlias(): string;

    public function getDefaultSortBy(): string;

    /** @return FieldDefinition[] */
    public function getFields(): array;
}
