<?php

namespace App\Controller;

use App\Entity\Question;
use App\Form\QuestionFormType;
use App\Repository\QuestionRepository;
use App\Service\Markdown\MarkdownConverterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QuestionController extends BaseController
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
     * @Route("/question/{id}", name="app_question_show", requirements={"id"="\d+"})
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
     * @IsGranted("IS_VERIFIED")
     * @Route("/question/create", name="app_question_create")
     */
    public function create(Request $request, QuestionRepository $questionRepository): Response
    {
        $form = $this->createForm(QuestionFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Question $question */
            $question = $form->getData();
            $question->setOwner($this->getUser());
            $question->setAskedAt($question->getAskedAt() ?? new \DateTime());
            $questionRepository->add($question, true);

            $this->addFlash('success', 'Question submitted. Enjoy !');

            return $this->redirectToRoute('app_questions_list');
        }

        return $this->render('question/create.html.twig', [
            'questionForm' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("EDIT", subject="question")
     * @Route("/question/{id}/edit", name="app_question_edit")
     */
    public function edit(Question $question, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(QuestionFormType::class, $question, [
            'include_asked_at' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Question updated. Hooyaa !');

            return $this->redirectToRoute('app_question_edit', [
                'id' => $question->getId(),
            ]);
        }

        return $this->render('question/edit.html.twig', [
            'question' => $question,
            'questionForm' => $form->createView(),
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

    /**
     * @IsGranted("IS_VERIFIED")
     * @Route("/questions", options={"expose"=true}, name="app_questions_list")
     */
    public function list(QuestionRepository $questionRepository): Response
    {
        $questions = $questionRepository->findBy([], ['askedAt' => 'DESC']);

        return $this->render('question/list.html.twig', [
            'questions' => $questions,
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     *
     * @Route("/questions/partial-list", options={"expose"=true}, name="app_questions_partial_list")
     */
    public function partialList(QuestionRepository $questionRepository): Response
    {
        $questions = $questionRepository->findBy([], ['askedAt' => 'DESC']);

        return $this->render('question/_partial_list.html.twig', [
            'questions' => $questions,
        ]);
    }
}
