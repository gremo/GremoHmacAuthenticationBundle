<?php

/*
 * This file is part of the hmac-authentication package.
 *
 * (c) Marco Polichetti <gremo1982@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gremo\HmacAuthenticationBundle;

use Gremo\HmacAuthenticationBundle\DependencyInjection\GremoHmacAuthenticationExtension;
use Gremo\HmacAuthenticationBundle\DependencyInjection\Security\Factory\HmacFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GremoHmacAuthenticationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var \Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new HmacFactory());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new GremoHmacAuthenticationExtension();
    }
}
