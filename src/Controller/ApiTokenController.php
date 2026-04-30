<?php

namespace App\Controller;

use App\DataList\ApiToken\ApiTokenDataList;
use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/profile/tokens")
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class ApiTokenController extends BaseController
{
    /**
     * @Route("", name="app_api_token_list", methods={"GET"})
     */
    public function list(Request $request, ApiTokenDataList $apiTokenDataList): Response
    {
        $params = $request->query->all();
        $params['user'] = (string) $this->getUser()->getId();

        $input = $apiTokenDataList->buildInput($params);
        $result = $apiTokenDataList->list($input);

        return $this->render('api_token/list.html.twig', [
            'result' => $result,
        ]);
    }

    /**
     * @Route("/new", name="app_api_token_create", methods={"POST"})
     */
    public function create(ApiTokenRepository $apiTokenRepository): Response
    {
        $apiToken = new ApiToken($this->getUser());
        $plainToken = $apiToken->getPlainToken();
        $apiTokenRepository->add($apiToken, true);

        $this->addFlash('new_api_token', $plainToken);

        return $this->redirectToRoute('app_api_token_list');
    }

    /**
     * @Route("/{identifier}/delete", name="app_api_token_delete", methods={"POST"})
     */
    public function delete(string $identifier, Request $request, ApiTokenRepository $apiTokenRepository): Response
    {
        $token = $apiTokenRepository->findOneByIdentifierAndUser($identifier, $this->getUser());

        if (null === $token) {
            throw $this->createNotFoundException();
        }

        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_api_token_'.$identifier, \is_string($csrfToken) ? $csrfToken : null)) {
            throw $this->createAccessDeniedException();
        }

        $apiTokenRepository->remove($token, true);
        $this->addFlash('success', 'Token deleted successfully.');

        return $this->redirectToRoute('app_api_token_list');
    }
}
