<?php

namespace App\DataList\User;

use App\DataList\AbstractDataList;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserDataList extends AbstractDataList
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, User::class);
    }

    protected function getRootAlias(): string
    {
        return 'user';
    }

    protected function getDataFieldsClasses(): array
    {
        return [
            'email' => EmailField::class,
        ];
    }
}
