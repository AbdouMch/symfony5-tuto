<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\Entity\Question;
use App\Form\Exception\Api\FormValidationException;
use App\Form\QuestionFormType;
use App\Model\Api\Response as ApiResponse;
use App\Repository\QuestionRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @Rest\Route("/questions")
 *
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 *
 * @OA\Tag(name="Questions")
 */
class QuestionController extends BaseApiController
{
    /**
     * @Route("", name="api_v1_question_create", methods="POST")
     *
     * @OA\Post(
     *     summary="Create a question",
     *     tags={"Questions"},
     *     security={{"ApiToken":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *
     *             @OA\Schema(
     *                 required={"name", "question"},
     *
     *                 @OA\Property(property="name", type="string", minLength=4, description="Question title"),
     *                 @OA\Property(property="question", type="string", minLength=4, description="Question body"),
     *                 @OA\Property(property="spell", type="integer", nullable=true, description="ID of the related spell")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Question created",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", ref="#/components/schemas/QuestionRead"),
     *             @OA\Property(property="code", type="integer", example=201)
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Validation failed"),
     *     @OA\Response(response=401, description="Not authenticated")
     * )
     */
    public function create(Request $request, QuestionRepository $questionRepository): Response
    {
        $question = new Question();

        $form = $this->createForm(QuestionFormType::class, $question, [
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
