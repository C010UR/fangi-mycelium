<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\SerializableTimestampableEntityTrait;
use App\Enum\MfaType;
use App\Enum\UserRole;
use App\ListQueryManagement\Attribute as LqmA;
use App\Model\MFA\EmailTwoFactorInterface;
use App\Model\MFA\MFAMethodInterface;
use App\Model\RelativeUrl;
use App\Repository\UserRepository;
use App\Serializer\Interface\DepthAwareNormalizableInterface;
use DateTimeImmutable;
use Deprecated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USER_USERNAME', fields: ['username'])]
#[ORM\HasLifecycleCallbacks]
#[Assert\UniqueEntity(['email'], message: 'form.email.already_taken')]
#[Assert\UniqueEntity(['username'], message: 'form.username.already_taken')]
#[LqmA\Schema('#/components/schemas/User')]
#[LqmA\Orders(new LqmA\Order('id', '{user}.id'), [
    new LqmA\Order('id', '{user}.id'),
    new LqmA\Order('username', '{user}.username'),
    new LqmA\Order('is_active', '{user}.isActive'),
    new LqmA\Order('is_activated', '{user}.isActivated'),
    new LqmA\Order('is_banned', '{user}.isBanned'),
])]
#[LqmA\Search([
    new LqmA\SearchParam('email', '{user}.email'),
    new LqmA\SearchParam('username', '{user}.username'),
    new LqmA\SearchParam('server_name', '{server}.name'),
    new LqmA\SearchParam('server_urls', '{server}.urls', type: LqmA\SearchParam::TYPE_ARRAY),
    new LqmA\SearchParam('server_client_id', '{server}.clientId', type: LqmA\SearchParam::TYPE_UUID),
])]
#[LqmA\Pagination]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EmailTwoFactorInterface, DepthAwareNormalizableInterface
{
    use SerializableTimestampableEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue('SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [UserRole::USER];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = '';

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $createdBy = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    /**
     * @var Collection<int, MfaMethod>
     */
    #[ORM\OneToMany(targetEntity: MfaMethod::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $mfaMethods;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?bool $isBanned = false;

    #[ORM\Column]
    private ?bool $isActivated = false;

    /**
     * @var Collection<int, Server>
     */
    #[ORM\ManyToMany(targetEntity: Server::class, mappedBy: 'users')]
    private Collection $servers;

    /**
     * @var Collection<int, UserModuleChoice>
     */
    #[ORM\OneToMany(targetEntity: UserModuleChoice::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userModuleChoices;

    public function __construct()
    {
        $this->mfaMethods = new ArrayCollection();
        $this->servers = new ArrayCollection();
        $this->userModuleChoices = new ArrayCollection();
    }

    /**
     * Returns the User for serialization.
     *
     * @return array returns the User as an array of data for serialization
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => $this->password,
            'roles' => $this->roles,
        ];
    }

    /**
     * Unserializes the User.
     *
     * @param array $serialized the serialized User (as an array)
     */
    public function __unserialize(array $serialized): void
    {
        foreach ($serialized as $key => $value) {
            $this->$key = $value;
        }
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('id')]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('email')]
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('username')]
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    #[Serializer\Ignore]
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     *
     * @return string[]
     */
    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('roles')]
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = array_unique($roles);

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    #[Serializer\Ignore]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFormattedName(): string
    {
        return \sprintf('%s (%s)', $this->username, $this->email);
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('created_by')]
    public function getCreatedByFormattedName(): ?string
    {
        return $this->createdBy?->getFormattedName();
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

    #[Serializer\Groups(['long'])]
    #[Serializer\SerializedName('created_by_entity')]
    public function getCreatedBy(): ?self
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?self $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    #[Serializer\Groups(['short', 'long'])]
    #[Serializer\SerializedName('last_login_at')]
    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    #[Serializer\Ignore]
    public function getName(): string
    {
        return $this->username;
    }

    /**
     * @return Collection<int, MfaMethod>
     */
    #[Serializer\Ignore]
    public function getMfaMethods(): Collection
    {
        return $this->mfaMethods;
    }

    public function getMfaMethodByFullName(string $method): ?MfaMethod
    {
        $_method = MfaType::fromFullName($method);

        return $this->getMfaMethods()
            ->findFirst(fn($_, MfaMethod $method) =>  MfaType::from($method->getMethod()) === $_method);
    }

    public function addMfaMethod(MfaMethod $mfaMethod): static
    {
        if (!$this->mfaMethods->contains($mfaMethod)) {
            $this->mfaMethods->add($mfaMethod);
            $mfaMethod->setUser($this);
        }

        return $this;
    }

    public function removeMfaMethod(MfaMethod $mfaMethod): static
    {
        if ($this->mfaMethods->removeElement($mfaMethod)) {
            // set the owning side to null (unless already changed)
            if ($mfaMethod->getUser() === $this) {
                $mfaMethod->setUser(null);
            }
        }

        return $this;
    }

    #[Serializer\Ignore]
    public function getPriorityMfaMethod(): ?MfaMethod
    {
        $maxPriority = null;
        $maxPriorityMethod = null;

        /** @var MfaMethod $mfaMethod */
        foreach ($this->getMfaMethods() as $mfaMethod) {
            if (null === $maxPriority || $mfaMethod->getPriority() > $maxPriority) {
                $maxPriority = $mfaMethod->getPriority();
                $maxPriorityMethod = $mfaMethod;
            }
        }

        return $maxPriorityMethod;
    }

    #[Serializer\Ignore]
    public function getEmailMfa(): ?MFAMethodInterface
    {
        return $this->getMfaMethods()
            ->findFirst(fn($_, MfaMethod $method) => $method->getMethod() === MfaType::Email->value);
    }

    #[Serializer\Ignore]
    public function isEmailMFAEnabled(): bool
    {
        return null !== $this->getEmailMfa();
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
    #[Serializer\SerializedName('is_activated')]
    public function isActivated(): ?bool
    {
        return $this->isActivated;
    }

    public function setIsActivated(bool $isActivated): static
    {
        $this->isActivated = $isActivated;

        return $this;
    }

    /**
     * @return Collection<int, Server>
     */
    #[Serializer\Groups(['long'])]
    #[Serializer\SerializedName('servers')]
    public function getServers(): Collection
    {
        return $this->servers;
    }

    public function setServer(Server $server): static
    {
        $this->servers = new ArrayCollection([$server]);

        return $this;
    }

    public function addServer(Server $server): static
    {
        if (!$this->servers->contains($server)) {
            $this->servers->add($server);
            $server->addUser($this);
        }

        return $this;
    }

    public function removeServer(Server $server): static
    {
        if ($this->servers->removeElement($server)) {
            $server->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, UserModuleChoice>
     */
    #[Serializer\Ignore]
    public function getUserModuleChoices(): Collection
    {
        return $this->userModuleChoices;
    }

    public function addUserModuleChoice(UserModuleChoice $userModuleChoice): static
    {
        if (!$this->userModuleChoices->contains($userModuleChoice)) {
            $this->userModuleChoices->add($userModuleChoice);
            $userModuleChoice->setUser($this);
        }

        return $this;
    }

    public function removeUserModuleChoice(UserModuleChoice $userModuleChoice): static
    {
        if ($this->userModuleChoices->removeElement($userModuleChoice)) {
            // set the owning side to null (unless already changed)
            if ($userModuleChoice->getUser() === $this) {
                $userModuleChoice->setUser(null);
            }
        }

        return $this;
    }
}
