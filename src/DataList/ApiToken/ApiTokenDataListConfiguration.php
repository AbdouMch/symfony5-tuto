<?php

namespace App\DataList\ApiToken;

use App\DataList\DataListConfigurationInterface;
use App\DataList\Filter\FieldDefinition;
use App\DataList\Filter\FilterType;
use App\Entity\ApiToken;

class ApiTokenDataListConfiguration implements DataListConfigurationInterface
{
    public function getEntityClass(): string
    {
        return ApiToken::class;
    }

    public function getRootAlias(): string
    {
        return 'api_token';
    }

    public function getDefaultSortBy(): string
    {
        return 'createdAt';
    }

    public function getFields(): array
    {
        return [
            'identifier' => new FieldDefinition('api_token.identifier', FilterType::STRING),
            'createdAt' => new FieldDefinition('api_token.createdAt', FilterType::DATE),
            'lastUsedAt' => new FieldDefinition('api_token.lastUsedAt', FilterType::DATE),
            'user' => new FieldDefinition('api_token.user', FilterType::NUMBER),
        ];
    }
}
