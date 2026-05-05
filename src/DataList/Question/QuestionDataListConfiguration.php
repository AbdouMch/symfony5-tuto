<?php

namespace App\DataList\Question;

use App\DataList\DataListConfigurationInterface;
use App\DataList\Filter\FieldDefinition;
use App\DataList\Filter\FilterType;
use App\Entity\Question;

class QuestionDataListConfiguration implements DataListConfigurationInterface
{
    public function getEntityClass(): string
    {
        return Question::class;
    }

    public function getRootAlias(): string
    {
        return 'question';
    }

    public function getDefaultSortBy(): string
    {
        return 'askedAt';
    }

    public function getFields(): array
    {
        return [
            'title' => new FieldDefinition('question.name', FilterType::STRING, 'contains'),
        ];
    }
}
