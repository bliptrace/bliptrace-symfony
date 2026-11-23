<?php

declare(strict_types=1);

namespace G8Scope\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Declares the `g8scope` config tree. The project is NEVER configured here —
 * it is resolved server-side from the ingest key (anti-spoof).
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('g8scope');

        $treeBuilder->getRootNode()
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->scalarNode('key')->defaultNull()->end()
            ->scalarNode('endpoint')->defaultValue('https://ingest.bliptrace.com/v1/ingest')->end()
            ->scalarNode('environment')->defaultValue('production')->end()
            ->scalarNode('release')->defaultNull()->end()
            ->integerNode('timeout')->defaultValue(3)->end()
            ->integerNode('max_frames')->defaultValue(30)->end()
            ->integerNode('max_message')->defaultValue(2000)->end()
            ->arrayNode('ignore_exceptions')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->end()
            ->arrayNode('scrub_keys')
            ->scalarPrototype()->end()
            ->defaultValue([
                'password', 'password_confirmation', 'token', 'secret', 'authorization',
                'api_key', 'apikey', 'access_token', 'refresh_token', 'cookie', 'set-cookie',
            ])
            ->end()
            ->end();

        return $treeBuilder;
    }
}
