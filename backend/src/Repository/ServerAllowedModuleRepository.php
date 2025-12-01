<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Module;
use App\Entity\Server;
use App\Entity\ServerAllowedModule;
use App\Entity\User;
use App\ListQueryManagement\LqmFactory;
use App\ListQueryManagement\LqmResult;
use App\ListQueryManagement\Model\QueryParamAliasMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<ServerAllowedModule>
 */
class ServerAllowedModuleRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LqmFactory $lqmFactory,
    ) {
        parent::__construct($registry, ServerAllowedModule::class);
    }

    public function findListByRequest(Request $request, Server $server, ?User $user = null): LqmResult
    {
        $processor = $this->lqmFactory->create($request, ServerAllowedModule::class, $user);

        $query = $this->createQueryBuilder('server_allowed_module')
            ->addSelect('server', 'module')
            ->leftJoin('server_allowed_module.server', 'server')
            ->leftJoin('server_allowed_module.module', 'module')
            ->andWhere('server_allowed_module.server = :server')
            ->setParameter('server', $server);

        $result = $processor->processQuery($query, new QueryParamAliasMap(
            ['server', 'module'],
            [
                'server_allowed_module' => 'server_allowed_module',
                'server' => 'server',
                'module' => 'module',
            ],
        ));

        return $result;
    }

    public function findAvailableModules(Server $server): array
    {
        $query = $this->createQueryBuilder('server_allowed_module')
            ->select('IDENTITY(module)')
            ->leftJoin('server_allowed_module.module', 'module')
            ->andWhere('server_allowed_module.server != :server')
            ->setParameter('server', $server)
            ->andWhere('module.isActive = true')
            ->andWhere('module.isBanned = false');

        return $query->getQuery()->getArrayResult();
    }

    public function findOneByServerAndModule(Server $server, Module $module): ?ServerAllowedModule
    {
        return $this->createQueryBuilder('server_allowed_module')
            ->andWhere('server_allowed_module.server = :server')
            ->andWhere('server_allowed_module.module = :module')
            ->setParameter('server', $server)
            ->setParameter('module', $module)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
