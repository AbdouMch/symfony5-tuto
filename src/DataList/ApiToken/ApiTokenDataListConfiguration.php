<?php

namespace App\DataList\ApiToken;

use App\DataList\DataListConfigurationInterface;
use App\DataList\Filter\FieldDefinition;
use App\DataList\Filter\FilterType;
use App\DataList\Filter\ScopeConstraint;
use App\Entity\ApiToken;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class ApiTokenDataListConfiguration implements DataListConfigurationInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

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
        $fields = [
            'identifier' => new FieldDefinition('api_token.identifier', FilterType::STRING),
            'createdAt' => new FieldDefinition('api_token.createdAt', FilterType::DATE),
            'lastUsedAt' => new FieldDefinition('api_token.lastUsedAt', FilterType::DATE),
        ];

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $fields['user'] = new FieldDefinition('api_token.user', FilterType::NUMBER);
        }

        return $fields;
    }

    public function getScope(): array
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return [];
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return [
            new ScopeConstraint(
                new FieldDefinition('api_token.user', FilterType::NUMBER),
                'eq',
                (string) $user->getId()
            ),
        ];
    }
}
