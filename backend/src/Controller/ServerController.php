<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Abstract\ExtendedAbstractController;
use App\Entity\Module;
use App\Entity\Server;
use App\Entity\ServerAllowedModule;
use App\Enum\UserRole;
use App\Form\ServerAllowedModuleType;
use App\Form\ServerType;
use App\ListQueryManagement\Attribute\OpenApi as LqmA;
use App\OpenApi\Attribute as OAC;
use App\Repository\ModuleRepository;
use App\Repository\ServerAllowedModuleRepository;
use App\Repository\ServerRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/servers', name: 'api_v1_server_')]
final class ServerController extends ExtendedAbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServerRepository $serverRepository,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted(UserRole::USER)]
    #[OA\Get(
        operationId: 'v1ServerList',
        summary: 'Fetch Server List',
        tags: [
            'servers',
        ],
        parameters: [
            new LqmA\ListParameters(Server::class),
        ],
        responses: [
            new OAC\JsonResponse(200, 'Server List', schema: new LqmA\ListResponse(Server::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::USER),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function list(Request $request): JsonResponse
    {
        return $this->jsonl($this->serverRepository->findListByRequest($request, $this->getUser()));
    }

    #[Route('/active', name: 'list_active', methods: ['GET'])]
    #[IsGranted(UserRole::USER)]
    #[OA\Get(
        operationId: 'v1ServerListActive',
        summary: 'Fetch Active Server List',
        tags: [
            'servers',
        ],
        parameters: [
            new LqmA\ListParameters(Server::class),
        ],
        responses: [
            new OAC\JsonResponse(200, 'Active Server List', schema: new LqmA\ListResponse(Server::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::USER),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function active(Request $request): JsonResponse
    {
        return $this->jsonl($this->serverRepository->findListActiveByRequest($request, $this->getUser()));
    }

    #[Route('/{id}', name: 'get', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::USER)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Get(
        operationId: 'v1ServerGet',
        summary: 'Fetch Server',
        tags: [
            'servers',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Server ID'),
        ],
        responses: [
            new OAC\JsonResponse(200, 'Server', schema: new LqmA\Model(Server::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::USER),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function get(Request $request, Server $server): JsonResponse
    {
        return $this->jsonl($server);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN)]
    #[OA\Post(
        operationId: 'v1ServerCreate',
        summary: 'Create Server',
        tags: [
            'servers',
        ],
        requestBody: new OAC\FormDataBody(
            'Server creation information.',
            schema: new OAC\Model(ServerType::class),
        ),
        responses: [
            new OAC\JsonResponse(200, 'Server', schema: new LqmA\Model(Server::class)),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function create(Request $request, MailerService $mailer): JsonResponse
    {
        $server = $this->submitForm(
            $request,
            ServerType::class,
            new Server(),
            [
                'created_by' => $this->getUser(),
            ],
        );

        $mailer->sendServerSetupEmail($server);

        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $this->jsonl($server);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Post(
        operationId: 'v1ServerUpdate',
        summary: 'Update Server',
        tags: [
            'servers',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Server ID'),
        ],
        requestBody: new OAC\FormDataBody(
            'Server update information.',
            schema: new OAC\Model(ServerType::class),
        ),
        responses: [
            new OAC\JsonResponse(200, 'Server', schema: new LqmA\Model(Server::class)),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function update(Request $request, Server $server): JsonResponse
    {
        $server = $this->submitForm(
            $request,
            ServerType::class,
            $server,
            [
                'created_by' => $this->getUser(),
            ],
        );

        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $this->jsonl($server);
    }

    #[Route('/{id}/generate-secret', name: 'generate_secret', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Get(
        operationId: 'v1ServerGenerateSecret',
        summary: 'Generate Server Secret',
        tags: [
            'servers',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Server ID'),
        ],
        responses: [
            new OAC\SuccessResponse('Server secret generated successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function generateSecret(Server $server, MailerService $mailer): JsonResponse
    {
        $server->generateSecret();
        $server->generateClientId();
        $this->entityManager->persist($server);
        $this->entityManager->flush();

        $mailer->sendServerSetupEmail($server);

        return $this->jsonm('entity.server.secret_generated');
    }

    #[Route('/{id}/activate', name: 'activate', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Get(
        operationId: 'v1ServerActivate',
        summary: 'Activate Server',
        tags: [
            'servers',
        ],
        responses: [
            new OAC\SuccessResponse('Server activated successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function activate(Server $server): JsonResponse
    {
        $server->setIsActive(true);
        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $this->jsonm('entity.server.activated');
    }

    #[Route('/{id}/deactivate', name: 'deactivate', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Get(
        operationId: 'v1ServerDeactivate',
        summary: 'Deactivate Server',
        tags: [
            'servers',
        ],
        responses: [
            new OAC\SuccessResponse('Server deactivated successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function deactivate(Server $server): JsonResponse
    {
        $server->setIsActive(false);
        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $this->jsonm('entity.server.deactivated');
    }

    #[Route('/{id}/modules', name: 'modules', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::USER)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Get(
        operationId: 'v1ServerModules',
        summary: 'Fetch Server Modules',
        tags: [
            'servers',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Server ID'),
        ],
        responses: [
            new OAC\JsonResponse(200, 'Server Modules', schema: new LqmA\ListResponse(ServerAllowedModule::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::USER),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function modules(Request $request, Server $server, ServerAllowedModuleRepository $serverAllowedModuleRepository): JsonResponse
    {
        return $this->jsonl($serverAllowedModuleRepository->findListByRequest($request, $server, $this->getUser()));
    }

    #[Route('/{id}/modules/can-add', name: 'modules_can_add', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Get(
        operationId: 'v1ServerModulesCanAdd',
        summary: 'Fetch Server Modules that can be added',
        tags: [
            'servers',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Server ID'),
        ],
        responses: [
            new OAC\JsonResponse(200, 'Server Modules', schema: new LqmA\ListResponse(Module::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function modulesCanAdd(Request $request, Server $server, ModuleRepository $moduleRepository): JsonResponse
    {
        return $this->jsonl($moduleRepository->findListAvailableByRequest($request, $server, $this->getUser()));
    }

    #[Route('/{id}/modules/add', name: 'modules_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Post(
        operationId: 'v1ServerModulesAdd',
        summary: 'Add Module to Server',
        tags: [
            'servers',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Server ID'),
        ],
        requestBody: new OAC\FormDataBody(
            'Module ID.',
            schema: new OAC\Model(ServerAllowedModuleType::class),
        ),
        responses: [
            new OAC\SuccessResponse('Module added to server successfully.'),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function modulesAdd(Request $request, Server $server, ServerAllowedModuleRepository $serverAllowedModuleRepository): JsonResponse
    {
        $serverAllowedModule = $this->submitForm(
            $request,
            ServerAllowedModuleType::class,
            new ServerAllowedModule(),
            [
                'server' => $server,
                'created_by' => $this->getUser(),
            ],
        );

        $this->entityManager->persist($serverAllowedModule);
        $this->entityManager->flush();

        return $this->jsonm('entity.server.module_added');
    }

    #[Route('/{id}/modules/{module_id}/remove', name: 'modules_remove', requirements: ['id' => '\d+', 'module_id' => '\d+'], methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_server', subject: 'server')]
    #[OA\Post(
        operationId: 'v1ServerModulesRemove',
        summary: 'Remove Module from Server',
        tags: [
            'servers',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'Server ID'),
            new OAC\DatabaseIdParameter('module_id', description: 'Module ID'),
        ],
        responses: [
            new OAC\SuccessResponse('Module removed from server successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function modulesRemove(
        #[MapEntity(mapping: ['id' => 'id'])]
        Server $server,
        #[MapEntity(mapping: ['module_id' => 'id'])]
        Module $module,
        ServerAllowedModuleRepository $serverAllowedModuleRepository,
    ): JsonResponse {
        $serverAllowedModule = $serverAllowedModuleRepository->findOneByServerAndModule($server, $module);

        if (!$serverAllowedModule) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($serverAllowedModule);
        $this->entityManager->flush();

        return $this->jsonm('entity.server.module_removed');
    }
}
