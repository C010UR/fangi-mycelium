<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Abstract\ExtendedAbstractController;
use App\Entity\Module;
use App\Enum\UserRole;
use App\Form\ModuleType;
use App\ListQueryManagement\Attribute\OpenApi as LqmA;
use App\OpenApi\Attribute as OAC;
use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/modules', name: 'api_v1_module_')]
final class ModuleController extends ExtendedAbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleRepository $moduleRepository,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted(UserRole::USER)]
    #[OA\Get(
        operationId: 'v1ModuleList',
        summary: 'Fetch Module List',
        tags: [
            'modules',
        ],
        parameters: [
            new LqmA\ListParameters(Module::class),
        ],
        responses: [
            new OAC\JsonResponse(200, 'Module List', schema: new LqmA\ListResponse(Module::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::USER),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function list(Request $request): JsonResponse
    {
        return $this->jsonl($this->moduleRepository->findListByRequest($request, $this->getUser()));
    }

    #[Route('/{id}', name: 'get', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::USER)]
    #[IsGranted('view_module', subject: 'module')]
    #[OA\Get(
        operationId: 'v1ModuleGet',
        summary: 'Fetch Module',
        tags: [
            'modules',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Module ID'),
        ],
        responses: [
            new OAC\JsonResponse(200, 'Module', schema: new LqmA\Model(Module::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::USER),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function get(Request $request, Module $module): JsonResponse
    {
        return $this->jsonl($module);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN)]
    #[OA\Post(
        operationId: 'v1ModuleCreate',
        summary: 'Create Module',
        tags: [
            'modules',
        ],
        requestBody: new OAC\FormDataBody(
            'Module creation information.',
            schema: new OAC\Model(ModuleType::class),
        ),
        responses: [
            new OAC\JsonResponse(200, 'Module', schema: new LqmA\Model(Module::class)),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function create(Request $request): JsonResponse
    {
        $module = $this->submitForm(
            $request,
            ModuleType::class,
            new Module(),
            [
                'created_by' => $this->getUser(),
            ],
        );

        $this->entityManager->persist($module);
        $this->entityManager->flush();

        return $this->jsonl($module);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('update_module', subject: 'module')]
    #[OA\Post(
        operationId: 'v1ModuleUpdate',
        summary: 'Update Module',
        tags: [
            'modules',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Module ID'),
        ],
        requestBody: new OAC\FormDataBody(
            'Module update information.',
            schema: new OAC\Model(ModuleType::class),
        ),
        responses: [
            new OAC\JsonResponse(200, 'Module', schema: new LqmA\Model(Module::class)),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function update(Request $request, Module $module): JsonResponse
    {
        $module = $this->submitForm(
            $request,
            ModuleType::class,
            $module,
            [
                'created_by' => $this->getUser(),
            ],
        );

        $this->entityManager->persist($module);
        $this->entityManager->flush();

        return $this->jsonl($module);
    }

    #[Route('/{id}/activate', name: 'activate', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('update_module', subject: 'module')]
    #[OA\Get(
        operationId: 'v1ModuleActivate',
        summary: 'Activate Module',
        tags: [
            'modules',
        ],
        responses: [
            new OAC\SuccessResponse('Module activated successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function activate(Module $module): JsonResponse
    {
        $module->setIsActive(true);
        $this->entityManager->persist($module);
        $this->entityManager->flush();

        return $this->jsonm('entity.module.activated');
    }

    #[Route('/{id}/deactivate', name: 'deactivate', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('update_module', subject: 'module')]
    #[OA\Get(
        operationId: 'v1ModuleDeactivate',
        summary: 'Deactivate Module',
        tags: [
            'modules',
        ],
        responses: [
            new OAC\SuccessResponse('Module deactivated successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function deactivate(Module $module): JsonResponse
    {
        $module->setIsActive(false);
        $this->entityManager->persist($module);
        $this->entityManager->flush();

        return $this->jsonm('entity.module.deactivated');
    }
}
