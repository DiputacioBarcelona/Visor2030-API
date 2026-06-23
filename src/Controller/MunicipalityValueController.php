<?php

namespace App\Controller;

use App\Repository\MunicipalityValueRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MunicipalityValueController
{
    private MunicipalityValueRepository $repository;

    public function __construct(MunicipalityValueRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request)
    {
        $comarcaCode = $request->query->get('comarcaCode');
        if (!$comarcaCode) {
            return new JsonResponse(['error' => 'comarcaCode is required'], 400);
        }

        // $values = $this->repository->findLatestValuesByComarca($comarcaCode);

        // creat a feke object to test the response
        $values = new \stdClass();

        return new JsonResponse($values);
    }
}
