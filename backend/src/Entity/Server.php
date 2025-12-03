<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SerializableTimestampableEntityTrait;
use App\ListQueryManagement\Attribute as LqmA;
use App\Model\RelativeUrl;
use App\Repository\ServerRepository;
use App\Serializer\Interface\DepthAwareNormalizableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_SERVER_NAME', fields: ['name'])]
#[ORM\HasLifecycleCallbacks]
#[Assert\UniqueEntity(['name'], message: 'form.name.already_taken')]
#[LqmA\Schema('#/components/schemas/Server')]
#[LqmA\Orders(new LqmA\Order('id', '{server}.id'), [
    new LqmA\Order('id', '{server}.id'),
    new LqmA\Order('name', '{server}.name'),
    new LqmA\Order('is_active', '{server}.isActive'),
    new LqmA\Order('is_banned', '{server}.isBanned'),
    new LqmA\Order('created_at', '{server}.createdAt'),
    new LqmA\Order('updated_at', '{server}.updatedAt'),
])]
#[LqmA\Search([
    new LqmA\SearchParam('name', '{server}.name'),
    new LqmA\SearchParam('urls', '{server}.urls', type: LqmA\SearchParam::TYPE_ARRAY),
    new LqmA\SearchParam('client_id', '{server}.clientId', type: LqmA\SearchParam::TYPE_UUID),
])]
#[LqmA\Pagination]
class Server implements DepthAwareNormalizableInterface
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

    #[ORM\Column(type: Types::JSON)]
    private array $urls = [];

    #[ORM\Column(type: 'uuid')]
    private ?Uuid $clientId = null;

    #[ORM\Column(length: 255)]
    private ?string $secret = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $authToken = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?bool $isBanned = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'servers')]
    private Collection $users;

    /**
     * @var Collection<int, ServerAllowedModule>
     */
    #[ORM\OneToMany(targetEntity: ServerAllowedModule::class, mappedBy: 'server', orphanRemoval: true)]
    private Collection $serverAllowedModules;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->serverAllowedModules = new ArrayCollection();
    }

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
    #[Serializer\SerializedName('urls')]
    public function getUrls(): array
    {
        return $this->urls;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('url')]
    public function getUrl(): ?string
    {
        return empty($this->urls) ? null : reset($this->urls);
    }

    /**
     * @param string[] $urls
     */
    public function setUrls(array $urls): static
    {
        $this->urls = $urls;

        return $this;
    }

    #[Serializer\Ignore]
    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    #[Serializer\Ignore]
    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }

    public function setAuthToken(?string $authToken): static
    {
        $this->authToken = $authToken;

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

    /**
     * @return Collection<int, User>
     */
    #[Serializer\Ignore]
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

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

    public function generateClientId(): Uuid
    {
        $this->clientId = Uuid::v4();

        return $this->clientId;
    }

    public function generateSecret(): string
    {
        $secret = bin2hex(random_bytes(32));
        $this->secret = sha1($secret);

        return $secret;
    }

    public function validateSecret(string $secret): bool
    {
        return $this->secret === sha1($secret);
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
            $serverAllowedModule->setServer($this);
        }

        return $this;
    }

    public function removeServerAllowedModule(ServerAllowedModule $serverAllowedModule): static
    {
        if ($this->serverAllowedModules->removeElement($serverAllowedModule)) {
            // set the owning side to null (unless already changed)
            if ($serverAllowedModule->getServer() === $this) {
                $serverAllowedModule->setServer(null);
            }
        }

        return $this;
    }
}
