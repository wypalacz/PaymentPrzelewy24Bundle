<?php

namespace TicketSwap\PaymentPrzelewy24Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ticket_swap_payment_przelewy24');

        $rootNode
            ->children()
                ->scalarNode('merchant_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('pos_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('crc')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('test')
                    ->defaultTrue()
                ->end()
                ->scalarNode('report_url')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('logger')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
