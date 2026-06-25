<?php

declare(strict_types=1);

namespace G8Scope\Symfony;

use G8Scope\Symfony\DependencyInjection\G8ScopeExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/** The g8scope Symfony bundle — registers the client, reporter and subscriber. */
final class G8ScopeBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new G8ScopeExtension;
    }
}
