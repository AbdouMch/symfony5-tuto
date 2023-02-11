<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends BaseController
{
    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     *
     * @Route("/comments/{id}/vote/{direction<up|down>}", methods="POST")
     */
    public function commentVote($id, $direction, LoggerInterface $logger, LoggerInterface $votingLogger)
    {
        $votingLogger->info(
            '{user} is voting on the ansswer id: {answer_id}',
            [
                'user' => $this->getUser()->getEmail(),
                'answer_id' => $id,
            ]
        );

        // todo - use id to query the database

        // use real logic here to save this to the database
        if ('up' === $direction) {
            $logger->info('Voting up!');
            $currentVoteCount = rand(7, 100);
        } else {
            $logger->info('Voting down!');
            $currentVoteCount = rand(0, 5);
        }

        return $this->json(['votes' => $currentVoteCount]);
    }
}
