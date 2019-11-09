<?php

namespace App\Controller;

use App\Entity\Enterprise;
use App\Repository\EnterpriseRepository;
use App\Repository\UserRepository;
use Hateoas\HateoasBuilder;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class EnterpriseController extends AbstractController
{
    /**
     * @Route("/api/enterprises", methods={"GET"})
     * @return Response
     *
     * @SWG\Get(
     * summary="Get enterprise list",
     * description="",
     * produces={"application/json"},
     * @SWG\Response(
     *     response=200,
     *     description="Return enterprise list",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Enterprise::class, groups={"full"}))
     *     )
     *   )
     * )
     * @SWG\Tag(name="Enterprise")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getBrands(Request $request, EnterpriseRepository $enterpriseRepository, CacheInterface $cache)
    {
        $page = $request->query->get('page');
        $limit = $request->query->get('limit');

        $enterprises = $cache->get('brands-list', function (ItemInterface $item) use($enterpriseRepository, $page, $limit) {
            $item->expiresAfter($this->getParameter("cache.expiration"));

            return $enterpriseRepository->findAllEnterprises($page, $limit);
        });

        if (empty($enterprises)) {
            return new JsonResponse(['code' => 404, 'message' => 'Enterprise not found'], Response::HTTP_NOT_FOUND);
        }

        $hateoas = HateoasBuilder::create()->build();

        $data = $hateoas->serialize($enterprises, 'json');

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/enterprises/{id}", methods={"GET"})
     * @param $id
     * @return Response
     *
     * @SWG\Get(
     * summary="Get brand detail",
     * description="",
     * produces={"application/json"},
     * @SWG\Response(
     *     response=200,
     *     description="Return enterprise detail",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Enterprise::class, groups={"full"}))
     *     )
     *   )
     * )
     * @SWG\Tag(name="Enterprise")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getBrand(EnterpriseRepository $enterpriseRepository, CacheInterface $cache, $id)
    {
        $enterprise = $cache->get('brand-detail-'.$id, function (ItemInterface $item) use ($enterpriseRepository, $id){
            $item->expiresAfter($this->getParameter("cache.expiration"));

            return $enterpriseRepository->findOneBy(['id' => $id]);
        });

        if (empty($enterprise)) {
            return new JsonResponse(['code' => 404, 'message' => 'Enterprise not found for id = '.$id], Response::HTTP_NOT_FOUND);
        }

        $hateoas = HateoasBuilder::create()->build();

        $data = $hateoas->serialize($enterprise, 'json');

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/api/enterprises/{id}/users", methods={"GET"})
     * @param $id
     * @return Response
     *
     * @SWG\Get(
     * summary="",
     * description="",
     * produces={"application/json"},
     * @SWG\Response(
     *     response=200,
     *     description="Return list user per enterprise",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Enterprise::class, groups={"full"}))
     *     )
     *   )
     * )
     * @SWG\Tag(name="Enterprise")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getUsersByEnterprise(UserRepository $userRepository, $id, CacheInterface $cache)
    {
        $users = $cache->get('user-detail-'.$id, function (ItemInterface $item) use ($userRepository, $id){
            $item->expiresAfter($this->getParameter("cache.expiration"));

            return $userRepository->findUsersByEnterprise($id);
        });

        if (empty($users)) {
            return new JsonResponse(['code' => 404, 'message' => 'Users not found for enterprise with id = '.$id], Response::HTTP_NOT_FOUND);
        }

        $hateoas = HateoasBuilder::create()->build();

        $data = $hateoas->serialize($users, 'json');

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
