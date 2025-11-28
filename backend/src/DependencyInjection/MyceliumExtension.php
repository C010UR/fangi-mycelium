<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\Security\AccountRegistration\AccountRegistrationHandler;
use App\Security\AccountRegistration\AccountRegistrationTokenGenerator;
use App\Security\MFA\MFAHandler;
use App\Security\PasswordReset\PasswordResetHandler;
use App\Security\PasswordReset\PasswordResetTokenGenerator;
use App\Security\State\StateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;

class MyceliumExtension extends Extension
{
    private const MFA_METHODS = ['email'];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new MyceliumConfiguration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->configureSecurity($container, $config['security']);
        $this->configureEmails($container, $config['emails']);
    }

    private function configureSecurity(ContainerBuilder $container, array $config): void
    {
        if (isset($config['state_manager'])) {
            $this->configureStateManager($container, $config['state_manager']);
        }

        if (isset($config['mfa'])) {
            $this->configureMFA($container, $config['mfa']);
        }

        if (isset($config['password_reset'])) {
            $this->configurePasswordResetServices($container, $config['password_reset']);
        }

        if (isset($config['account_registration'])) {
            $this->configureAccountRegistrationServices($container, $config['account_registration']);
        }

        $this->createTrustResolverAliases($container);
    }

    private function configureStateManager(ContainerBuilder $container, array $config): void
    {
        $stateManagerDefinition = new Definition($config['class']);
        $stateManagerDefinition->setAutowired(true);
        $stateManagerDefinition->setArguments([
            '$keyLength' => $config['key_length'],
            '$keyPrefix' => $config['key_prefix'],
            '$ttl' => $config['ttl'] ?? (int)\ini_get('session.gc_maxlifetime'),
        ]);
        $container->setDefinition($config['class'], $stateManagerDefinition);

        $container->setAlias(
            StateManagerInterface::class,
            $config['class'],
        )->setPublic(true);

        $container->setAlias(
            'security.state_manager',
            StateManagerInterface::class,
        )->setPublic(true);
    }

    private function configureMFA(ContainerBuilder $container, array $config): void
    {
        $activeProviders = [];

        foreach (self::MFA_METHODS as $method) {
            if (isset($config[$method])) {
                $providerAlias = $this->configureMFAMethod($container, $method, $config[$method]);
                $activeProviders[$method] = [
                    'provider' => $providerAlias,
                ];
            }
        }

        $this->configureMFAHandler($container, $config, $activeProviders);
    }

    private function configureMFAMethod(ContainerBuilder $container, string $method, array $config): string
    {
        $providerAlias = \sprintf('mycelium_%s_mfa_provider', $method);

        if (isset($config['generator'])) {
            $generatorDefinition = new Definition($config['generator']);
            $generatorDefinition->setAutowired(true);
            $generatorDefinition->setArguments([
                '$digits' => $config['code_length'],
                '$expirationTime' => $config['code_expiration_time'],
            ]);
            $container->setDefinition($config['generator'], $generatorDefinition);
        }

        if (isset($config['provider'])) {
            $providerDefinition = new Definition($config['provider']);
            $providerDefinition->setAutowired(true);
            $providerDefinition->setArguments([]);

            $providerDefinition->addTag('scheb_two_factor.provider', [
                'alias' => $providerAlias,
            ]);

            $serviceId = \sprintf('mycelium.%s_mfa_provider', $method);
            $container->setDefinition($serviceId, $providerDefinition);
        }

        return $providerAlias;
    }

    private function configureMFAHandler(ContainerBuilder $container, array $config, array $activeProviders): void
    {
        $handlerDefinition = new Definition(MFAHandler::class);
        $handlerDefinition->setAutowired(true);
        $handlerDefinition->setAutoconfigured(true);

        $arguments = [
            '$providerRegistry' => new Reference('scheb_two_factor.provider_registry'),
            '$preparationRecorder' => new Reference('scheb_two_factor.provider_preparation_recorder'),
        ];

        foreach ($activeProviders as $method => $data) {
            $arguments[\sprintf('$%sProvider', $method)] = $data['provider'];
        }

        $handlerDefinition->setArguments($arguments);
        $container->setDefinition(MFAHandler::class, $handlerDefinition);
    }

    private function createTrustResolverAliases(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition(AuthenticationTrustResolverInterface::class)
            && !$container->hasAlias(AuthenticationTrustResolverInterface::class)
        ) {
            $trustResolverDefinition = new Definition(AuthenticationTrustResolver::class);
            $container->setDefinition(AuthenticationTrustResolverInterface::class, $trustResolverDefinition);
        }
    }

    private function configurePasswordResetServices(ContainerBuilder $container, array $config): void
    {
        $tokenGeneratorDefinition = new Definition(PasswordResetTokenGenerator::class);
        $tokenGeneratorDefinition->setAutowired(true);
        $tokenGeneratorDefinition->setArguments([
            '$signingKey' => $config['signing_key'],
            '$selectorLength' => $config['selector_length'],
            '$verifierLength' => $config['verifier_length'],
        ]);
        $container->setDefinition(PasswordResetTokenGenerator::class, $tokenGeneratorDefinition);

        $handlerDefinition = new Definition(PasswordResetHandler::class);
        $handlerDefinition->setAutowired(true);
        $handlerDefinition->setArguments([
            '$selectorLength' => $config['selector_length'],
            '$verifierLength' => $config['verifier_length'],
            '$expirationTime' => $config['token_expiration_time'],
        ]);
        $container->setDefinition(PasswordResetHandler::class, $handlerDefinition);
    }

    private function configureAccountRegistrationServices(ContainerBuilder $container, array $config): void
    {
        $tokenGeneratorDefinition = new Definition(AccountRegistrationTokenGenerator::class);
        $tokenGeneratorDefinition->setAutowired(true);
        $tokenGeneratorDefinition->setArguments([
            '$signingKey' => $config['signing_key'],
            '$selectorLength' => $config['selector_length'],
            '$verifierLength' => $config['verifier_length'],
        ]);
        $container->setDefinition(AccountRegistrationTokenGenerator::class, $tokenGeneratorDefinition);

        $handlerDefinition = new Definition(AccountRegistrationHandler::class);
        $handlerDefinition->setAutowired(true);
        $handlerDefinition->setArguments([
            '$selectorLength' => $config['selector_length'],
            '$verifierLength' => $config['verifier_length'],
        ]);
        $container->setDefinition(AccountRegistrationHandler::class, $handlerDefinition);
    }

    private function configureEmails(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('mycelium.emails', $config);
    }

    public function getAlias(): string
    {
        return 'mycelium';
    }
}
