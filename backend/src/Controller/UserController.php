<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Abstract\ExtendedAbstractController;
use App\Entity\User;
use App\Enum\UserRole;
use App\Form\UserCreateType;
use App\Form\UserUpdateType;
use App\ListQueryManagement\Attribute\OpenApi as LqmA;
use App\OpenApi\Attribute as OAC;
use App\Repository\UserRepository;
use App\Security\PasswordReset\PasswordResetHandler;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/users', name: 'api_v1_users_')]
class UserController extends ExtendedAbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private UserRepository $userRepository,
        private UserService $userService,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[OA\Get(
        operationId: 'v1UserList',
        summary: 'Fetch User List',
        tags: [
            'users',
        ],
        parameters: [
            new LqmA\ListParameters(User::class),
        ],
        responses: [
            new OAC\JsonResponse(200, 'User List', schema: new LqmA\ListResponse(User::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function list(Request $request): JsonResponse
    {
        return $this->jsonl($this->userRepository->findListByRequest($request, $this->getUser()));
    }

    #[Route('/{id}', name: 'get', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_user', subject: 'user')]
    #[OA\Get(
        operationId: 'v1UserGet',
        summary: 'Fetch One User',
        tags: [
            'users',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'User ID.'),
        ],
        responses: [
            new OAC\JsonResponse(200, 'User', schema: new LqmA\Model(User::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function get(User $user): JsonResponse
    {
        return $this->jsonl($user);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN)]
    #[OA\Post(
        operationId: 'v1UserCreate',
        summary: 'Create New User',
        tags: [
            'users',
        ],
        requestBody: new OAC\JsonBody(
            description: 'User Form.',
            schema: new OAC\Model(UserCreateType::class),
        ),
        responses: [
            new OAC\JsonResponse(200, 'User', schema: new LqmA\Model(User::class)),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function create(Request $request, PasswordResetHandler $passwordResetHandler): JsonResponse
    {
        /** @var User $user */
        $user = $this->submitForm(
            $request,
            UserCreateType::class,
            new User(),
            ['created_by' => $this->getUser()],
        );

        $this->entityManager->persist($user);
        $passwordResetHandler->prepareAccountActivation($user);

        $this->entityManager->flush();

        return $this->jsonl($user);
    }

    #[Route('/{id}', name: 'update', methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_user', subject: 'user')]
    #[OA\Post(
        operationId: 'v1UserUpdate',
        summary: 'Update Existing User',
        tags: [
            'users',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'User ID.'),
        ],
        requestBody: new OAC\JsonBody(
            description: 'User Form.',
            schema: new OAC\Model(UserUpdateType::class),
        ),
        responses: [
            new OAC\JsonResponse(200, 'User', schema: new LqmA\Model(User::class)),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function update(Request $request, User $user): JsonResponse
    {
        /** @var User $user */
        $user = $this->submitForm(
            $request,
            UserUpdateType::class,
            $user,
            ['created_by' => $this->getUser()],
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->jsonl($user);
    }

    #[Route('/{id}/activate', name: 'activate', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_user', subject: 'user')]
    #[OA\Get(
        operationId: 'v1UserActivate',
        summary: 'Activate Existing User',
        tags: [
            'users',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'User ID.'),
        ],
        responses: [
            new OAC\SuccessResponse('User activated successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function activate(User $user): JsonResponse
    {
        $this->userService->activate($user);
        $this->entityManager->flush();

        return $this->jsonm('entity.user.activated');
    }

    #[Route('/{id}/deactivate', name: 'deactivate', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_user', subject: 'user')]
    #[OA\Get(
        operationId: 'v1UserDeactivate',
        summary: 'Deactivate Existing User',
        tags: [
            'users',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'User ID.'),
        ],
        responses: [
            new OAC\SuccessResponse('User deactivated successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function deactivate(User $user): JsonResponse
    {
        $this->userService->deactivate($user);
        $this->entityManager->flush();

        return $this->jsonm('entity.user.deactivated');
    }

    #[Route('/{id}/resend-activation-email', name: 'resend_activation_email', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(UserRole::ADMIN)]
    #[IsGranted('view_user', subject: 'user')]
    #[OA\Get(
        operationId: 'v1ResendActivationEmail',
        summary: 'Resend Activation Email to Existing User',
        tags: [
            'users',
        ],
        parameters: [
            new OAC\DatabaseIdParameter(description: 'User ID.'),
        ],
        responses: [
            new OAC\SuccessResponse('A new activation email was sent successfully.'),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::ADMIN),
            new OAC\NotFoundResponse(),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function resendActivationEmail(User $user, PasswordResetHandler $passwordResetHandler): JsonResponse
    {
        if ($user->isActivated()) {
            return $this->jsonm('entity.user.already_activated', status: 400);
        }

        $passwordResetHandler->prepareAccountActivation($user);
        $this->entityManager->flush();

        return $this->jsonm('entity.user.resend_activation_email');
    }
}
