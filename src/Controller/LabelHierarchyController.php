<?php

namespace App\Controller;

use App\Entity\Label;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class LabelHierarchyController
{
    #[Route('/labels-hierarchy', name: 'labels_hierarchy', methods: ['GET'])]
    public function getHierarchy(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $language = $request->query->get('language');

        if (!$language) {
            return new JsonResponse(['error' => 'Language parameter is required'], 400);
        }

        $hierarchy = $this->getLanguageLabels($language, $entityManager);

        return new JsonResponse($hierarchy);
    }

    #[Route('/labels-import', name: 'labels_import', methods: ['POST'])]
    public function importLabels(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $language = $request->query->get('language');

        if (!$language) {
            return new JsonResponse(['error' => 'Language parameter is required'], 400);
        }

        $jsonContent = $request->getContent();

        if (!$jsonContent) {
            return new JsonResponse(['error' => 'Empty request body'], 400);
        }

        $data = json_decode($jsonContent, true);

        if ($data === null) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $this->processJson($data, '', $language, $entityManager, $validator);

        $entityManager->flush();

        // return the hierarchy after the import
        $hierarchy = $this->getLanguageLabels($language, $entityManager);

        return new JsonResponse($hierarchy);
    }

    private function getLanguageLabels(string $language, EntityManagerInterface $entityManager): array
    {
        return $entityManager->getRepository(Label::class)->getHierarchyByLanguage($language);
    }

    private function processJson(
        array $data,
        string $prefix,
        string $language,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {

        ini_set('memory_limit', '1024M');
        
        foreach ($data as $key => $value) {
            $currentCode = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $this->processJson($value, $currentCode, $language, $entityManager, $validator);
            } else {
                $label = $entityManager->getRepository(Label::class)->findOneBy([
                    'code' => $currentCode,
                    'language' => $language,
                ]);

                if (!$label) {
                    $label = new Label();
                    $label->setCode($currentCode);
                    $label->setLanguage($language);
                }

                $label->setText($value);

                $errors = $validator->validate($label);
                if (count($errors) > 0) {
                    throw new BadRequestException('Validation failed');
                }

                $entityManager->persist($label);
            }
        }
    }
}
