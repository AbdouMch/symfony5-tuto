<?php

namespace App\DataList\Question;

use App\DataList\AbstractDataList;
use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;

class QuestionDataList extends AbstractDataList
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Question::class);
    }

    protected function getRootAlias(): string
    {
        return 'question';
    }

    protected function getDefaultSortBy(): string
    {
        return 'askedAt';
    }

    protected function getDataFieldsClasses(): array
    {
        return [
            'title' => TitleField::class,
        ];
    }
}
