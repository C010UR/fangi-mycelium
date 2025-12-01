<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Module;
use App\Entity\Server;
use App\Entity\User;
use App\ListQueryManagement\LqmFactory;
use App\ListQueryManagement\LqmResult;
use App\ListQueryManagement\Model\QueryParamAliasMap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Module>
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LqmFactory $lqmFactory,
        private ServerAllowedModuleRepository $serverAllowedModuleRepository,
    ) {
        parent::__construct($registry, Module::class);
    }

    public function findListByRequest(Request $request, ?User $user = null): LqmResult
    {
        $processor = $this->lqmFactory->create($request, Module::class, $user);

        $query = $this->createQueryBuilder('module');

        if (null !== $user) {
            $expr = $query->expr();

            $expr->orX(
                $expr->eq('module.createdBy', ':user'),
                $expr->andX(
                    $expr->eq('module.isActive', 'true'),
                    $expr->eq('module.isBanned', 'false'),
                ),
            );

            $query
                ->andWhere($expr)
                ->setParameter('user', $user);
        } else {
            $query
                ->andWhere('module.isActive = true')
                ->andWhere('module.isBanned = false');
        }

        $result = $processor->processQuery($query, new QueryParamAliasMap(
            'module',
            [
                'module' => 'module',
            ],
        ));

        return $result;
    }

    public function findListAvailableByRequest(Request $request, Server $server, ?User $user = null): LqmResult
    {
        $processor = $this->lqmFactory->create($request, Module::class, $user);

        $query = $this->createQueryBuilder('module')
            ->leftJoin('module.serverAllowedModules', 'server_allowed_modules');

        $expr = $query->expr();

        $query
            ->andWhere($expr->orX(
                $expr->isNull('server_allowed_modules'),
                $expr->neq('server_allowed_modules.server', ':server'),
            ))
            ->andWhere('module.isActive = true')
            ->andWhere('module.isBanned = false')
            ->setParameter('server', $server);

        $result = $processor->processQuery($query, new QueryParamAliasMap(
            'module',
            [
                'module' => 'module',
            ],
        ));

        return $result;
    }
}
