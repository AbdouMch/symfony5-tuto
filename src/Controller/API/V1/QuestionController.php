<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\Entity\Question;
use App\Form\Exception\Api\FormValidationException;
use App\Form\QuestionFormType;
use App\Model\Api\Response as ApiResponse;
use App\Repository\QuestionRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @Rest\Route("/questions")
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 */
class QuestionController extends BaseApiController
{
    /**
     * @Route("", name="api_v1_question_create", methods={"POST"})
     */
    public function create(Request $request, QuestionRepository $questionRepository): Response
    {
        $form = $this->createForm(QuestionFormType::class, null, [
            'mode' => QuestionFormType::API_MODE,
            'csrf_protection' => false,
        ]);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Question $question */
            $question = $form->getData();
            $user = $this->getUser();
            $question->setOwner($user);
            $questionRepository->add($question, true);

            return $this->json(
                new ApiResponse($question, Response::HTTP_CREATED),
                Response::HTTP_CREATED,
                [],
                [
                    AbstractNormalizer::GROUPS => ['api:question', 'api:response', 'api:user', 'api:spell'],
                ]
            );
        }

        throw new FormValidationException($form);
    }
}
