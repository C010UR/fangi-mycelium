<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Consts\FixtureConsts;
use App\DataFixtures\Consts\FixtureReferenceConsts;
use App\Entity\Module;
use App\Entity\Server;
use App\Entity\ServerAllowedModule;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ModuleFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->createModule(
            $manager,
            name: FixtureConsts::MODULE_NAME,
            description: $this->faker->sentence(32),
            urls: ['http://host.docker.internal:10000'],
            createdByReference: FixtureReferenceConsts::USER_ADMIN,
            serverReference: FixtureReferenceConsts::SERVER_DEFAULT,
            reference: FixtureReferenceConsts::MODULE_DEFAULT,
        );

        for ($i = 0; $i < 100; ++$i) {
            $this->createModule(
                $manager,
                name: $this->faker->company() . ' #' . $this->faker->numberBetween(1, 1000),
                description: $this->faker->sentence(32),
                urls: array_map(
                    fn() => 'https://' . $this->faker->domainName(),
                    array_fill(0, $this->faker->numberBetween(1, 10), null),
                ),
                createdByReference: FixtureReferenceConsts::USER_ADMIN,
                serverReference: FixtureReferenceConsts::SERVER_DEFAULT,
            );
        }

        $manager->flush();
    }

    private function createModule(
        ObjectManager $manager,
        string $name,
        string $description,
        array $urls,
        string $createdByReference,
        string $serverReference,
        ?string $reference = null,
    ): Module {
        /** @var User $user */
        $user = $this->getReference($createdByReference, User::class);

        /** @var Server $server */
        $server = $this->getReference($serverReference, Server::class);

        $module = new Module()
            ->setName($name)
            ->setDescription($description)
            ->setUrls($urls)
            ->setCreatedBy($user)
            ->setIsActive(true)
            ->setIsBanned(false);

        $module->generateClientId();

        $manager->persist($module);

        if (null !== $reference) {
            $this->addReference($reference, $module);
        }

        $serverAllowedModule = new ServerAllowedModule()
            ->setModule($module)
            ->setServer($server)
            ->setCreatedBy($user);

        $manager->persist($serverAllowedModule);

        return $module;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ServerFixtures::class,
        ];
    }
}
