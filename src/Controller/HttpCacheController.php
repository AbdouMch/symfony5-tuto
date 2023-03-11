<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HttpCacheController extends BaseController
{
    /**
     * @Route("/time", name="app_time")
     */
    public function time(Request $request): Response
    {
        $response = $this->render('time.html.twig');

        $response
        ->setPublic()
            ->setEtag("time")
            ->setMaxAge(10)
        ;


        return $response;
    }

    /**
     * @Route("/cache/comments/{id}/vote/{direction<up|down>}", options={"expose"=true}, methods="GET", name="app_vote_cache")
     *
     */
    public function commentVote(Request $request, $id, $direction, LoggerInterface $logger, LoggerInterface $votingLogger)
    {
        $votingLogger->info(
            '{user} is voting on the ansswer id: {answer_id}',
            [
                'user' => '$this->getUser()->getEmail()',
                'answer_id' => $id,
            ]
        );

        // todo - use id to query the database

        // use real logic here to save this to the database
        if ('up' === $direction) {
            $logger->info('Voting up!');
            $currentVoteCount = random_int(7, 100);
        } else {
            $logger->info('Voting down!');
            $currentVoteCount = random_int(0, 5);
        }

        $response = $this->json(['votes' => $currentVoteCount]);

        $response->setPublic()
            ->setMaxAge(30);

        return $response;
    }
}