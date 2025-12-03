<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SerializableTimestampableEntityTrait;
use App\ListQueryManagement\Attribute as LqmA;
use App\Model\RelativeUrl;
use App\Repository\ModuleRepository;
use App\Serializer\Interface\DepthAwareNormalizableInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_MODULE_NAME', fields: ['name'])]
#[ORM\HasLifecycleCallbacks]
#[Assert\UniqueEntity(['name'], message: 'form.name.already_taken')]
#[LqmA\Schema('#/components/schemas/Module')]
#[LqmA\Filters([
    new LqmA\Filter(
        'owner',
        '{module}.createdBy',
        [
            LqmA\FilterOperator::Equal,
        ],
        roles: ['ROLE_ADMIN'],
    ),
])]
#[LqmA\Orders(new LqmA\Order('id', '{module}.id'), [
    new LqmA\Order('id', '{module}.id'),
    new LqmA\Order('name', '{module}.name'),
    new LqmA\Order('is_active', '{module}.isActive'),
    new LqmA\Order('is_banned', '{module}.isBanned'),
    new LqmA\Order('created_at', '{module}.createdAt'),
    new LqmA\Order('updated_at', '{module}.updatedAt'),
])]
#[LqmA\Search([
    new LqmA\SearchParam('name', '{module}.name'),
    new LqmA\SearchParam('description', '{module}.description'),
    new LqmA\SearchParam('urls', '{module}.urls', type: LqmA\SearchParam::TYPE_ARRAY),
    new LqmA\SearchParam('client_id', '{module}.clientId', type: LqmA\SearchParam::TYPE_UUID),
])]
#[LqmA\Pagination]
class Module implements DepthAwareNormalizableInterface
{
    use SerializableTimestampableEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $clientId = null;

    #[ORM\Column(type: Types::JSON)]
    private array $urls = [];

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?bool $isBanned = false;

    /**
     * @var Collection<int, ServerAllowedModule>
     */
    #[ORM\OneToMany(targetEntity: ServerAllowedModule::class, mappedBy: 'module', orphanRemoval: true)]
    private Collection $serverAllowedModules;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('id')]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('name')]
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    #[Serializer\Ignore]
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('image_url')]
    public function getImageUrlObject(): ?RelativeUrl
    {
        return $this->imageUrl ? new RelativeUrl($this->imageUrl) : null;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('description')]
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('client_id')]
    public function getClientId(): ?Uuid
    {
        return $this->clientId;
    }

    public function setClientId(Uuid $clientId): static
    {
        $this->clientId = $clientId;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('urls')]
    public function getUrls(): array
    {
        return $this->urls;
    }

    /**
     * @param string[] $urls
     */
    public function setUrls(array $urls): static
    {
        $this->urls = $urls;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('is_active')]
    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('is_banned')]
    public function isBanned(): ?bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): static
    {
        $this->isBanned = $isBanned;

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

    public function generateClientId(): Uuid
    {
        $this->clientId = Uuid::v4();

        return $this->clientId;
    }

    /**
     * @return Collection<int, ServerAllowedModule>
     */
    #[Serializer\Ignore]
    public function getServerAllowedModules(): Collection
    {
        return $this->serverAllowedModules;
    }

    public function addServerAllowedModule(ServerAllowedModule $serverAllowedModule): static
    {
        if (!$this->serverAllowedModules->contains($serverAllowedModule)) {
            $this->serverAllowedModules->add($serverAllowedModule);
            $serverAllowedModule->setModule($this);
        }

        return $this;
    }

    public function removeServerAllowedModule(ServerAllowedModule $serverAllowedModule): static
    {
        if ($this->serverAllowedModules->removeElement($serverAllowedModule)) {
            // set the owning side to null (unless already changed)
            if ($serverAllowedModule->getModule() === $this) {
                $serverAllowedModule->setModule(null);
            }
        }

        return $this;
    }
}
