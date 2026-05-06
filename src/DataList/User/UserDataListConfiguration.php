<?php

namespace App\DataList\User;

use App\DataList\DataListConfigurationInterface;
use App\DataList\Filter\FieldDefinition;
use App\DataList\Filter\FilterType;
use App\Entity\User;

class UserDataListConfiguration implements DataListConfigurationInterface
{
    public function getEntityClass(): string
    {
        return User::class;
    }

    public function getRootAlias(): string
    {
        return 'user';
    }

    public function getDefaultSortBy(): string
    {
        return 'email';
    }

    public function getFields(): array
    {
        return [
            'id' => new FieldDefinition('user.id', FilterType::NUMBER),
            'email' => new FieldDefinition('user.email', FilterType::STRING, 'contains'),
        ];
    }

    public function getScope(): array
    {
        return [];
    }
}
