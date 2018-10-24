<?php

namespace TheAentMachine\AentTraefik\Event;

use Safe\Exceptions\StringsException;
use TheAentMachine\Aent\Context\Context;
use TheAentMachine\Aent\Event\ReverseProxy\AbstractNewVirtualHostEvent;
use TheAentMachine\Aent\Payload\ReverseProxy\ReverseProxyNewVirtualHostPayload;
use TheAentMachine\AentTraefik\Event\Exception\NewVirtualHostEventException;
use TheAentMachine\Service\Service;

final class NewVirtualHostEvent extends AbstractNewVirtualHostEvent
{
    /** @var Context */
    private $context;

    /**
     * @param ReverseProxyNewVirtualHostPayload $payload
     * @return Service
     * @throws NewVirtualHostEventException
     * @throws StringsException
     */
    protected function populateService(ReverseProxyNewVirtualHostPayload $payload): Service
    {
        $this->context = Context::fromMetadata();
        $service = $payload->getService();
        $serviceName = $service->getServiceName();
        $virtualHosts = $service->getVirtualHosts();
        $baseVirtualHost = $payload->getBaseVirtualHost();
        if (empty($virtualHosts)) {
            throw NewVirtualHostEventException::noVirtualHostForService($service);
        }
        foreach ($virtualHosts as $index => $port) {
            $subdomain = $this->prompt->getPromptHelper()->getSubdomain($serviceName, $port, $baseVirtualHost);
            $url = $subdomain . '.' . $baseVirtualHost;
            $this->output->writeln("\n👌 Your service <info>$serviceName</info> will be accessible at <info>$url</info> (using port <info>$port</info>)!");
            $service->addLabel('traefik.s'.$index.'.backend', $serviceName);
            $service->addLabel('traefik.s'.$index.'.frontend.rule', 'Host:' . $url);
            $service->addLabel('traefik.s'.$index.'.port', (string)$port);
        }
        return $service;
    }
}
