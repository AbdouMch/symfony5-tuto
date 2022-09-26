<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use App\Service\Markdown\MarkdownConverterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage(QuestionRepository $questionRepository)
    {
        $questions = $questionRepository->findTopNewestQuestions(3);

        return $this->render('question/homepage.html.twig', [
            'questions' => $questions,
        ]);
    }

    /**
     * @Route("/questions/{id}", name="app_question_show")
     */
    public function show(Question $question, MarkdownConverterInterface $converter)
    {
        $answers = [
            'Make sure `your cat is sitting` purrrfectly still 🤣',
            'Honestly, I like furry shoes better than MY cat',
            'Maybe... try saying the spell backwards?',
        ];

        $questionText = $converter->convert($question->getQuestion());
        $question->setQuestion($questionText);

        return $this->render('question/show.html.twig', [
            'question' => $question,
            'question_text' => $questionText,
            'answers' => $answers,
        ]);
    }

    /**
     * @IsGranted("EDIT", subject="question")
     * @Route("/questions/{id}/edit", name="app_question_edit")
     */
    public function edit(Question $question)
    {
        return $this->render('question/edit.html.twig', [
            'question' => $question,
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
