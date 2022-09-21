<?php

namespace App\Controller;

use App\Service\Markdown\MarkdownConverterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class QuestionController extends AbstractController
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage(Environment $twigEnvironment)
    {
        return $this->render('question/homepage.html.twig');
    }

    /**
     * @Route("/questions/{slug}", name="app_question_show")
     */
    public function show($slug, MarkdownConverterInterface $converter)
    {
        $answers = [
            'Make sure `your cat is sitting` purrrfectly still 🤣',
            'Honestly, I like furry shoes better than MY cat',
            'Maybe... try saying the spell backwards?',
        ];

        $questionText = "I've been turned into a `cat`, any thoughts on how to turn back? While I'm **adorable**, I don't really care for cat food.";

        $questionText = $converter->convert($questionText);

        return $this->render('question/show.html.twig', [
            'question' => ucwords(str_replace('-', ' ', $slug)),
            'question_text' => $questionText,
            'answers' => $answers,
        ]);
    }

    /**
     * @IsGranted("ROLE_QUESTION_DELETE")
     * @Route("/question/{id}/delete")
     */
    public function delete(int $id): Response
    {
        return new Response("question with $id has been deleted successfully");
    }
}
