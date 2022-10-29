<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\DataList\User\UserDataList;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/users")
 * @IsGranted("ROLE_USER")
 */
class UserController extends BaseApiController
{
    /**
     * @Route("", name="api_v1_users_list", methods={"GET"})
     * @Rest\QueryParam(name="email", map=true, nullable=true, description="search by user email")
     * @Rest\QueryParam(name="sort", requirements="(asc|desc)", allowBlank=false, default="asc", description="Sort direction")
     * @Rest\QueryParam(name="sort_by", requirements="\w+", default="email", description="Sort by field name")
     * @Rest\QueryParam(name="limit", map=false, requirements="\d+", default=23, description="size of the page")
     * @Rest\QueryParam(name="page", map=false, requirements="\d+", default=1, description="page number")
     * @Rest\View(serializerGroups={"api:user", "api:response:list"})
     */
    public function getUsersList(ParamFetcher $paramFetcher, UserDataList $userDataList): View
    {
        $users = $userDataList->list($paramFetcher);

        return $this->view(
            $users,
            Response::HTTP_OK,
        );
    }
}
