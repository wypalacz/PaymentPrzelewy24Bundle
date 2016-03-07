<?php

namespace TicketSwap\Payment\Przelewy24Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

class TicketSwapPaymentPrzelewy24Extension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('ticket_swap_payment_przelewy24.merchant_id', $config['merchant_id']);
        $container->setParameter('ticket_swap_payment_przelewy24.pos_id', $config['pos_id']);
        $container->setParameter('ticket_swap_payment_przelewy24.crc', $config['crc']);
        $container->setParameter('ticket_swap_payment_przelewy24.test', $config['test']);
        $container->setParameter('ticket_swap_payment_przelewy24.report_url', $config['report_url']);

        /**
         * When logging is disabled, remove logger and setLogger calls
         */
        if (false === $config['logger']) {
            $container
                ->getDefinition('ticket_swap_payment_przelewy24.controller.notification')
                ->removeMethodCall('setLogger');
            $container->getDefinition('ticket_swap_payment_przelewy24.plugin.default')->removeMethodCall('setLogger');
            $container->removeDefinition('monolog.logger.ticket_swap_payment_przelewy24');
        }
    }
}
