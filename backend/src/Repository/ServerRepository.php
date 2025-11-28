<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Server;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\ListQueryManagement\LqmFactory;
use App\ListQueryManagement\LqmResult;
use App\ListQueryManagement\Model\QueryParamAliasMap;
use App\Service\UserService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Server>
 */
class ServerRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LqmFactory $lqmFactory,
        private Security $security
    ) {
        parent::__construct($registry, Server::class);
    }

    public function findListByRequest(Request $request, ?User $user = null): LqmResult
    {
        $processor = $this->lqmFactory->create($request, Server::class, $user);

        $query = $this->createQueryBuilder('server')
            ->leftJoin('server.users', 'user')
            ->andWhere('user = :user')
            ->setParameter('user', $user);

        if (!$this->security->isGranted(UserRole::ADMIN)) {
            $query
            ->andWhere('server.isActive = true')
            ->andWhere('server.isBanned = false');
        }

        $result = $processor->processQuery($query, new QueryParamAliasMap([
            'server' => 'server',
        ]));

        return $result;
    }
}
