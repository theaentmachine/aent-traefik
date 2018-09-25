<?php

namespace TheAentMachine\AentTraefik\Event;

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
     */
    protected function populateService(ReverseProxyNewVirtualHostPayload $payload): Service
    {
        $this->context = Context::fromMetadata();
        $service = $payload->getService();
        $serviceName = $service->getServiceName();
        $virtualHosts = $service->getVirtualHosts();
        $defaultURL = $serviceName . '.' . $this->context->getBaseVirtualHost();
        if (empty($virtualHosts)) {
            throw NewVirtualHostEventException::noVirtualHostForService($service);
        }
        foreach ($virtualHosts as $key => $virtualHost) {
            $virtualPort = (string)$virtualHost['port'];
            $comment = $virtualHost['comment'] ?? '';
            if (isset($virtualHost['host'])) {
                $url = $virtualHost['host'];
            } elseif (isset($virtualHost['hostPrefix'])) {
                $url = $virtualHost['hostPrefix'] . '.' . $payload->getBaseVirtualHost();
            } else {
                $url = $defaultURL;
                $this->output->writeln("\nNo virtual host found for <info>$serviceName</info>, using <info>$url</info>.");
            }
            $this->output->writeln("\n👌 Your service <info>$serviceName</info> will be accessible at <info>$url</info>!");
            $service->addLabel('traefik.s'.$key.'.backend', $serviceName);
            $service->addLabel('traefik.s'.$key.'.frontend.rule', 'Host:' . $url, (string)$comment);
            $service->addLabel('traefik.s'.$key.'.port', $virtualPort);
        }
        return $service;
    }
}
