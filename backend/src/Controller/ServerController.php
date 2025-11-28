<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Abstract\ExtendedAbstractController;
use App\Entity\Server;
use App\Entity\User;
use App\Enum\UserRole;
use App\Form\ServerType;
use App\OpenApi\Attribute as OAC;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\ListQueryManagement\Attribute\OpenApi as LqmA;
use App\Repository\ServerRepository;
use App\Service\MailerService;

#[Route('/api/v1/servers', name: 'api_v1_server_')]
final class ServerController extends ExtendedAbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServerRepository $serverRepository,
    ) {}

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
            new OAC\DatabaseIdParameter('Server ID'),
            new LqmA\ListParameters(Server::class),
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
            schema: new OAC\Model(ServerType::class)
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
            ]
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
            new OAC\DatabaseIdParameter('Server ID'),
        ],
        requestBody: new OAC\FormDataBody(
            'Server update information.',
            schema: new OAC\Model(ServerType::class)
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
            ]
        );

        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $this->jsonl($server);
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
}
