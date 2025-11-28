<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\Interface\PostSubmitFormInterface;
use App\OpenApi\Attribute as OAC;
use App\Service\FileService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[OAC\Schema('#/components/schemas/RegistrationForm', hasFiles: true)]
class RegistrationType extends AbstractType implements PostSubmitFormInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private FileService $fileService,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'invalid_message' => 'form.email.invalid',
                'constraints' => [
                    new Assert\NotBlank(message: 'form.email.empty'),
                    new Assert\Email(message: 'form.email.invalid'),
                    new Assert\Length(max: 180, maxMessage: 'form.email.too_long'),
                ],
                'required' => true,
            ])
            ->add('username', TextType::class, [
                'invalid_message' => 'form.username.invalid',
                'constraints' => [
                    new Assert\NotBlank(message: 'form.username.empty'),
                    new Assert\Length(min: 3, max: 255, minMessage: 'form.username.too_short', maxMessage: 'form.username.too_long'),
                ],
                'required' => true,
            ])
            ->add('image', FileType::class, [
                'invalid_message' => 'form.image.invalid',
                'constraints' => [
                    new Assert\File(mimeTypes: ['image/jpeg', 'image/png', 'image/gif'], mimeTypesMessage: 'form.image.invalid_mime_type'),
                    new Assert\Image(maxSize: '8192k', maxSizeMessage: 'form.image.too_large'),
                ],
                'required' => false,
                'mapped' => false,
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new Assert\NotBlank(message: 'form.password.empty'),
                    new Assert\PasswordStrength(minScore: Assert\PasswordStrength::STRENGTH_MEDIUM, message: 'form.password.too_weak'),
                ],
                'required' => true,
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'extra_fields_message' => 'form.generic.extra_fields',
        ]);
    }

    /**
     *
     * @param FormInterface $form
     * @param User $entity
     * @param array $options
     * @return User
     */
    public function postSubmit(FormInterface $form, object $entity, array $options): object
    {
        $entity->setPassword($this->passwordHasher->hashPassword($entity, $form['password']->getData()));
        $entity->setRoles([UserRole::ADMIN]);

        if ($image = $form['image']->getData()) {
            $url = $this->fileService->upload($image);
            $entity->setImageUrl($url);
        }

        return $entity;
    }
}
