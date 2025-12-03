<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SerializableTimestampableEntityTrait;
use App\ListQueryManagement\Attribute as LqmA;
use App\Repository\ServerAllowedModuleRepository;
use App\Serializer\Interface\DepthAwareNormalizableInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: ServerAllowedModuleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[LqmA\Schema('#/components/schemas/ServerAllowedModule')]
#[LqmA\Orders(new LqmA\Order('module_id', '{module}.id'), [
    new LqmA\Order('module_id', '{module}.id'),
    new LqmA\Order('module_name', '{module}.name'),
    new LqmA\Order('module_is_active', '{module}.isActive'),
    new LqmA\Order('module_is_banned', '{module}.isBanned'),
    new LqmA\Order('module_created_at', '{module}.createdAt'),
    new LqmA\Order('module_updated_at', '{module}.updatedAt'),
])]
#[LqmA\Search([
    new LqmA\SearchParam('module_name', '{module}.name'),
    new LqmA\SearchParam('module_description', '{module}.description'),
    new LqmA\SearchParam('module_urls', '{module}.urls', type: LqmA\SearchParam::TYPE_ARRAY),
    new LqmA\SearchParam('module_client_id', '{module}.clientId', type: LqmA\SearchParam::TYPE_UUID),
])]
#[LqmA\Pagination]
class ServerAllowedModule implements DepthAwareNormalizableInterface
{
    use SerializableTimestampableEntityTrait;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'serverAllowedModules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Server $server = null;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Module $module = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('server')]
    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('module')]
    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): static
    {
        $this->module = $module;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('created_by_entity')]
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('created_by')]
    public function getCreatedByFormattedName(): ?string
    {
        return $this->createdBy?->getFormattedName();
    }
}
