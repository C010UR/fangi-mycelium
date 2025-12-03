<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Form\Interface\PostSubmitFormInterface;
use App\OpenApi\Attribute as OAC;
use App\Service\FileService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

#[OAC\Schema('#/components/schemas/ProfileUpdateForm')]
class ProfileUpdateType extends AbstractType implements PostSubmitFormInterface
{
    public function __construct(
        private FileService $fileService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'invalid_message' => 'form.image.invalid',
                'constraints' => [
                    new Assert\File(mimeTypes: ['image/jpeg', 'image/png', 'image/gif'], mimeTypesMessage: 'form.image.invalid_mime_type'),
                    new Assert\Image(maxSize: '8192k', maxSizeMessage: 'form.image.too_large'),
                ],
                'required' => false,
                'mapped' => false,
            ])
            ->add('username', TextType::class, [
                'invalid_message' => 'form.username.invalid',
                'constraints' => [
                    new Assert\NotBlank(message: 'form.username.empty'),
                    new Assert\Length(min: 3, max: 255, minMessage: 'form.username.too_short', maxMessage: 'form.username.too_long'),
                ],
                'required' => false,
                'empty_data' => $options['data']->getUsername(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => User::class,
                'extra_fields_message' => 'form.generic.extra_fields',
            ]);
    }

    /**
     * @param User $entity
     *
     * @return User
     */
    public function postSubmit(FormInterface $form, object $entity, array $options): object
    {
        if ($image = $form['image']->getData()) {
            $url = $this->fileService->upload($image);
            $entity->setImageUrl($url);
        }

        return $entity;
    }
}
