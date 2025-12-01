<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Consts\FixtureConsts;
use App\DataFixtures\Consts\FixtureReferenceConsts;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ServerFixtures extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $this->createServer(
            $manager,
            name: FixtureConsts::SERVER_NAME,
            urls: ['http://host.docker.internal:9000'],
            createdByReference: FixtureReferenceConsts::USER_ADMIN,
            reference: FixtureReferenceConsts::SERVER_DEFAULT,
        );

        for ($i = 0; $i < 100; ++$i) {
            $this->createServer(
                $manager,
                name: $this->faker->company() . ' #' . $this->faker->numberBetween(1, 1000),
                urls: array_map(
                    fn() => 'https://' . $this->faker->domainName(),
                    array_fill(0, $this->faker->numberBetween(1, 10), null),
                ),
                createdByReference: FixtureReferenceConsts::USER_ADMIN,
            );
        }

        $manager->flush();
    }

    private function createServer(
        ObjectManager $manager,
        string $name,
        array $urls,
        string $createdByReference,
        ?string $reference = null,
    ): Server {
        /** @var User $user */
        $user = $this->getReference($createdByReference, User::class);

        $server = new Server()
            ->setName($name)
            ->setUrls($urls)
            ->setCreatedBy($user)
            ->setIsActive(true)
            ->setIsBanned(false);

        $server->generateClientId();
        $server->generateSecret();

        $server->addUser($user);

        $manager->persist($server);

        if (null !== $reference) {
            $this->addReference($reference, $server);
        }

        return $server;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
