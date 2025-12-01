<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Module;
use App\Entity\Server;
use App\Entity\ServerAllowedModule;
use App\Entity\User;
use App\Form\Interface\PostSubmitFormInterface;
use App\OpenApi\Attribute as OAC;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[OAC\Schema('#/components/schemas/ServerAllowedModuleForm')]
class ServerAllowedModuleType extends AbstractType implements PostSubmitFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('module', EntityType::class, [
                'class' => Module::class,
                'choice_label' => 'id',
                'invalid_message' => 'form.module.invalid_inchoice',
                'query_builder' => function (EntityRepository $er) use ($options): QueryBuilder {
                    $expr = new Expr();

                    return $er
                        ->createQueryBuilder('module')
                        ->leftJoin('module.serverAllowedModules', 'server_allowed_modules')
                        ->andWhere($expr->orX(
                            $expr->isNull('server_allowed_modules'),
                            $expr->neq('server_allowed_modules.server', ':server'),
                        ))
                        ->andWhere('module.isActive = true')
                        ->andWhere('module.isBanned = false')
                        ->setParameter('server', $options['server']);
                },
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => ServerAllowedModule::class,
                'extra_fields_message' => 'form.generic.extra_fields',
            ])
            ->setRequired([
                'server',
                'created_by',
            ])
            ->setAllowedTypes('server', Server::class)
            ->setAllowedTypes('created_by', User::class);
    }

    /**
     * @param ServerAllowedModule $entity
     *
     * @return ServerAllowedModule
     */
    public function postSubmit(FormInterface $form, object $entity, array $options): object
    {
        $entity->setCreatedBy($options['created_by']);
        $entity->setServer($options['server']);

        return $entity;
    }
}
