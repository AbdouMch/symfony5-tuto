<?php

namespace App\Controller;

use App\Entity\Question;
use App\Exporter\Question\QuestionExporter;
use App\Form\QuestionFormType;
use App\Repository\QuestionRepository;
use App\Service\DateTimeService;
use App\Service\FlashMessageService\FlashMessageService;
use App\Service\Markdown\MarkdownConverterInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuestionController extends BaseController
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage(QuestionRepository $questionRepository): Response
    {
        $questions = $questionRepository->findTopNewestQuestions(3);

        return $this->render('question/homepage.html.twig', [
            'questions' => $questions,
        ]);
    }

    /**
     * @Route("/question/{id}", name="app_question_show", requirements={"id"="\d+"})
     */
    public function show(Question $question, MarkdownConverterInterface $converter): Response
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
     *
     * @Route("/question/create", name="app_question_create")
     */
    public function create(
        Request $request,
        QuestionRepository $questionRepository,
        DateTimeService $dateTimeService
    ): Response {
        $form = $this->createForm(QuestionFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Question $question */
            $question = $form->getData();
            $question->setOwner($this->getUser());
            $question->setAskedAt($question->getAskedAt() ?? new \DateTime());
            $questionRepository->add($question, true);
            $now = $dateTimeService->getUserFriendlyDatetime(new \DateTime());
            $message = sprintf('Question submitted at %s. Enjoy!', $now->format('Y-m-d H:i'));
            $this->addFlash('success', $message);

            return $this->redirectToRoute('app_questions_list');
        }

        return $this->render('question/create.html.twig', [
            'questionForm' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("EDIT", subject="question")
     *
     * @Route("/question/{id}/edit", name="app_question_edit")
     */
    public function edit(
        Question $question,
        Request $request,
        EntityManagerInterface $em,
        DateTimeService $dateTimeService,
        FlashMessageService $flashMessage
    ): Response {
        $newUpdates = null;

        if ($request->isMethod('POST')) {
            $uow = $em->getUnitOfWork();
            $uow->computeChangeSet($em->getClassMetadata(get_class($question)), $question);
            $newUpdates = $uow->getOriginalEntityData($question);
        }

        $form = $this->createForm(QuestionFormType::class, $question, [
            'include_asked_at' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->lock($question, LockMode::OPTIMISTIC, $question->getVersion());

                $em->flush();
                $now = $dateTimeService->getUserFriendlyDatetime(new \DateTime());
                $message = sprintf('Question updated at %s. Hooyaa!', $now->format('Y-m-d H:i'));
                $flashMessage->add(FlashMessageService::SUCCESS, null, $message);

                return $this->redirectToRoute('app_question_edit', [
                    'id' => $question->getId(),
                ]);
            } catch (OptimisticLockException $exception) {
                $flashMessage->add(FlashMessageService::ERROR, null, 'Sorry, but someone else has already changed this question. Please apply the changes again!');
            }
        }

        return $this->render('question/edit.html.twig', [
            'question' => $question,
            'questionForm' => $form->createView(),
            'new_updates' => $newUpdates,
        ]);
    }

    /**
     * @IsGranted("ROLE_QUESTION_DELETE")
     *
     * @Route("/question/{id}/delete")
     */
    public function delete(int $id): Response
    {
        return new Response("question with $id has been deleted successfully");
    }

    /**
     * @IsGranted("IS_VERIFIED")
     *
     * @Route("/questions", name="app_questions_list")
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

    /**
     * @Route("/questions/download", name="app_questions_download")
     *
     * @IsGranted("ROLE_QUESTIONS_EXPORT")
     */
    public function downloadQuestions(
        FlashMessageService $flashMessage,
        QuestionExporter $exporter,
        RateLimiterFactory $questionExportLimiter,
        TranslatorInterface $translator
    ): Response {
        $user = $this->getUser();

        $response = $exporter->create($user);

        if (null !== $response->getError()) {
            $flashMessage->add(FlashMessageService::ERROR, $response->getTitle(), $response->getMessage());
        } else {
            $flashMessage->add(FlashMessageService::SUCCESS, $response->getTitle(), $response->getMessage());
        }

        return $this->redirectToReferer();
    }
}
