<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Server;
use App\Entity\User;
use App\Form\Interface\PostSubmitFormInterface;
use App\OpenApi\Attribute as OAC;
use App\Service\FileService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

#[OAC\Schema('#/components/schemas/ServerForm')]
class ServerType extends AbstractType implements PostSubmitFormInterface
{
    public function __construct(
        private FileService $fileService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'invalid_message' => 'form.name.invalid',
                'constraints' => [
                    new Assert\NotBlank(message: 'form.name.empty'),
                    new Assert\Length(min: 3, max: 255, minMessage: 'form.name.too_short', maxMessage: 'form.name.too_long'),
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
            ->add('urls', CollectionType::class, [
                'entry_type' => TextType::class,
                'invalid_message' => 'form.allowed_urls.invalid',
                'constraints' => [
                    new Assert\All([
                        new Assert\NotBlank(message: 'form.allowed_urls.empty_url'),
                        new Assert\Regex(
                            pattern: '/^https?:\/\/([a-zA-Z0-9.-]+|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(:\d{1,5})?$/',
                            message: 'form.allowed_urls.invalid_format',
                        ),
                    ]),
                    new Assert\Unique(message: 'form.allowed_urls.duplicate'),
                    new Assert\Count(min: 1, minMessage: 'form.allowed_urls.at_least_one'),
                    new Assert\Count(max: 10, maxMessage: 'form.allowed_urls.too_many'),
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Server::class,
                'extra_fields_message' => 'form.generic.extra_fields',
            ])
            ->setRequired('created_by')
            ->setAllowedTypes('created_by', User::class);
    }

    /**
     * @param Server $entity
     *
     * @return Server
     */
    public function postSubmit(FormInterface $form, object $entity, array $options): object
    {
        $entity->setCreatedBy($options['created_by']);

        if ($image = $form['image']->getData()) {
            $url = $this->fileService->upload($image);
            $entity->setImageUrl($url);
        }

        if (null === $entity->getClientId()) {
            $entity->generateClientId();
            $entity->generateSecret();
        }

        $options['created_by']->addServer($entity);

        return $entity;
    }
}
