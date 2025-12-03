<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Abstract\ExtendedAbstractController;
use App\DataFixtures\Consts\FixtureConsts;
use App\Entity\User;
use App\Enum\UserRole;
use App\Form\ProfileUpdateType;
use App\ListQueryManagement\Attribute\OpenApi as LqmA;
use App\OpenApi\Attribute as OAC;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_v1_auth_')]
final class AuthenticationController extends ExtendedAbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        operationId: 'v1Login',
        summary: 'User Login',
        tags: [
            'auth',
        ],
        parameters: [
            new OA\Parameter(
                name: 'ignore-mfa',
                description: 'Skip MFA authentication. __[LOCAL DEVELOPMENT ONLY]__',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false),
            ),
        ],
        requestBody: new OAC\JsonBody(
            description: 'Authentication token with MFA information.',
            properties: [
                new OA\Property(
                    property: 'username',
                    type: 'string',
                    format: 'email',
                    description: 'User email.',
                    example: FixtureConsts::ADMIN_USER_EMAIL,
                ),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    description: 'User password.',
                    example: FixtureConsts::USER_PASSWORD,
                ),
            ],
            examples: [
                new OA\Examples(
                    example: 'Administrator',
                    summary: 'Authenticate with ROLE_ADMIN',
                    value: [
                        'username' => FixtureConsts::ADMIN_USER_EMAIL,
                        'password' => FixtureConsts::USER_PASSWORD,
                    ],
                ),
                new OA\Examples(
                    example: 'Manager',
                    summary: 'Authenticate with ROLE_MANAGER',
                    value: [
                        'username' => FixtureConsts::USER_EMAIL,
                        'password' => FixtureConsts::USER_PASSWORD,
                    ],
                ),
            ],
        ),
        responses: [
            new OAC\JsonResponse(
                response: 200,
                description: 'Authentication token with MFA information.',
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'token',
                        type: 'string',
                        description: 'Authentication Token.',
                        example: 'e93a49a530be76f1afbe54605a771d462d407f3911ded5ed3a0968431bf43117',
                    ),
                    new OA\Property(
                        property: 'mfa_required',
                        type: 'boolean',
                        description: 'Whether MFA Authentication is required.',
                        example: false,
                    ),
                    new OA\Property(
                        property: 'priority_mfa_method',
                        type: 'object',
                        nullable: true,
                        ref: '#/components/schemas/MFAMethod',
                        description: 'Priority MFA method information.',
                    ),
                    new OA\Property(
                        property: 'available_mfa_methods',
                        type: 'array',
                        nullable: true,
                        description: 'Available MFA method information.',
                        items: new OA\Items(
                            ref: '#/components/schemas/MFAMethod',
                        ),
                    ),
                ],
            ),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse('Bad credentials.'),
            new OAC\InternalServerErrorResponse(),
        ],
        security: [],
    )]
    public function login(): JsonResponse
    {
        /*
         * @see App\Security\AuthenticationHandler
         */
        throw new LogicException('This method should be unreachable');
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    #[OA\Get(
        operationId: 'v1Logout',
        summary: 'User Logout',
        tags: [
            'auth',
        ],
        responses: [
            new OAC\SuccessResponse('Successfully logged out.'),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function logout(): JsonResponse
    {
        /*
         * @see App\EventListener\LogoutListener
         */
        throw new LogicException('This method should be unreachable');
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    #[OA\Get(
        operationId: 'v1Profile',
        summary: 'User Profile',
        tags: [
            'auth',
        ],
        responses: [
            new OAC\JsonResponse(200, 'User profile.', schema: new LqmA\Model(User::class)),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::USER),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function profile(): JsonResponse
    {
        return $this->jsonl($this->getUser(), context: ['groups' => ['extended']]);
    }

    #[Route('/profile', name: 'profile_update', methods: ['POST'])]
    #[OA\Post(
        operationId: 'v1ProfileUpdate',
        summary: 'Update User Profile',
        tags: [
            'auth',
        ],
        requestBody: new OAC\JsonBody(
            description: 'User Form.',
            schema: new OAC\Model(ProfileUpdateType::class),
        ),
        responses: [
            new OAC\JsonResponse(200, 'User', schema: new LqmA\Model(User::class)),
            new OAC\BadRequestResponse(),
            new OAC\UnauthorizedResponse(),
            new OAC\AccessDeniedResponse(UserRole::USER),
            new OAC\InternalServerErrorResponse(),
        ],
    )]
    public function profileUpdate(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->submitForm(
            $request,
            ProfileUpdateType::class,
            $this->getUser(),
            ['method' => 'PATCH'],
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->jsonl($user);
    }
}
