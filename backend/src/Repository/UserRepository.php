<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\ListQueryManagement\LqmFactory;
use App\ListQueryManagement\LqmResult;
use App\ListQueryManagement\Model\QueryParamAliasMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface, UserProviderInterface
{
    public function __construct(ManagerRegistry $registry, private LqmFactory $lqmFactory)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** @var User $user */
        $user = $this->createQueryBuilder('user')
            ->addSelect('mfa_methods')
            ->leftJoin('user.mfaMethods', 'mfa_methods')
            ->andWhere('user.email = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $user) {
            $e = new UserNotFoundException('User with identifier ' . $identifier . ' not found.');
            $e->setUserIdentifier($identifier);

            throw $e;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!($user instanceof User)) {
            throw new InvalidArgumentException(\sprintf('User of class "%s" is not supported.', $user::class));
        }

        $id = $user->getId();

        /** @var User $user */
        $refreshedUser = $this->createQueryBuilder('user')
            ->addSelect('mfa_methods')
            ->leftJoin('user.mfaMethods', 'mfa_methods')
            ->andWhere('user.id = :id')
            ->setParameter('id', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $refreshedUser) {
            $e = new UserNotFoundException('User with id ' . json_encode($id) . ' not found.');
            $e->setUserIdentifier(json_encode($id));

            throw $e;
        }

        return $refreshedUser;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('user')
            ->andWhere('user.email = :username')
            ->setParameter('username', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findListByRequest(Request $request, User $user): LqmResult
    {
        $processor = $this->lqmFactory->create($request, User::class, $user);

        $query = $this->createQueryBuilder('user')
            ->addSelect('server')
            ->distinct()
            ->leftJoin('user.servers', 'server')
            ->andWhere('user != :current_user')
            ->setParameter('current_user', $user)
            ->andWhere('server IN (:servers)')
            ->setParameter('servers', $user->getServers());

        $result = $processor->processQuery($query, new QueryParamAliasMap('user', [
            'user' => 'user',
            'server' => 'server',
        ]));

        return $result;
    }
}
