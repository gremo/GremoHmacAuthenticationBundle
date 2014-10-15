<?php

/*
 * This file is part of the hmac-authentication package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\HmacAuthenticationBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\HttpBasicFactory;

class HmacFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.hmac.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('hmac.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(1, $config['service_label'])
            ->replaceArgument(2, $config['algorithm'])
            ->replaceArgument(3, $config['verify_headers']);

        $listenerId = 'security.authentication.listener.hmac.'.$id;
        $container->setDefinition($listenerId, new DefinitionDecorator('hmac.security.authentication.listener'))
            ->replaceArgument(0, new Reference($container->hasDefinition('security.token_storage') ? 'security.token_storage' : 'security.context'))
            ->replaceArgument(2, $config['auth_header']);

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'hmac';
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->fixXmlConfig('header')
            ->children()
                ->scalarNode('auth_header')
                    ->cannotBeEmpty()
                    ->defaultValue('Authorization')
                ->end()
                ->scalarNode('service_label')->cannotBeEmpty()->defaultValue('HMAC')->end()
                ->scalarNode('algorithm')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) { return strtolower($v); })
                    ->end()
                    ->validate()
                        ->ifNotInArray(hash_algos())
                        ->thenInvalid('value %s is not supported, see hash_algos() for available hashing algorithms.')
                    ->end()
                    ->defaultValue('sha256')
                ->end()
                ->arrayNode('verify_headers')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) { return array_map('trim', explode(',', $v)); })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
            ->end();
    }
}
