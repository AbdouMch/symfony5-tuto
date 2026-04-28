<?php

namespace App\DataList\ApiToken;

use App\DataList\AbstractDataList;
use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;

class ApiTokenDataList extends AbstractDataList
{

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, ApiToken::class);
    }

    protected function getRootAlias(): string
    {
        return 'api_token';
    }

    protected function getDataFieldsClasses(): array
    {
        return [
            'identifier' => IdentifierField::class,
            'createdAt'  => CreatedAtField::class,
            'lastUsedAt' => LastUsedAtField::class,
        ];
    }
}