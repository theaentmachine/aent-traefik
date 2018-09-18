<?php

namespace TheAentMachine\AentTraefik\Event\Exception;

use TheAentMachine\Aent\Exception\AentException;
use TheAentMachine\Service\Service;

final class NewVirtualHostEventException extends AentException
{
    /**
     * @param Service $service
     * @return NewVirtualHostEventException
     */
    public static function noVirtualHostForService(Service $service): self
    {
        $serviceName = $service->getServiceName();
        return new self("No virtual host found for service \"$serviceName\"!");
    }
}
