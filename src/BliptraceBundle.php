<?php

declare(strict_types=1);

namespace Bliptrace\Symfony;

use Bliptrace\Symfony\DependencyInjection\BliptraceExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/** The Bliptrace Symfony bundle — registers the client, reporter and subscriber. */
final class BliptraceBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new BliptraceExtension;
    }
}
