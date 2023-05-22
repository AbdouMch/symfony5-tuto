<?php

namespace App\Model\Mapper;

use App\Entity\Question;
use App\Model\Question as QuestionModel;
use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;

class QuestionMapper implements AutoMapperConfiguratorInterface
{
    public function configure(AutoMapperConfigInterface $config): void
    {
        $config->registerMapping(Question::class, QuestionModel::class)
            ->forMember('ownerName', function (Question $question) {
                return $question->getOwner()->getFirstName();
            });
    }
}
