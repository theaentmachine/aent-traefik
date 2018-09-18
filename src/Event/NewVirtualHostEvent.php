<?php

namespace TheAentMachine\AentTraefik\Event;

use Safe\Exceptions\StringsException;
use TheAentMachine\Aent\Context\Context;
use TheAentMachine\Aent\Event\ReverseProxy\AbstractNewVirtualHostEvent;
use TheAentMachine\AentTraefik\Event\Exception\NewVirtualHostEventException;
use TheAentMachine\Service\Service;
use function \Safe\sprintf;

final class NewVirtualHostEvent extends AbstractNewVirtualHostEvent
{
    /** @var Context */
    private $context;

    /**
     * @param Service $service
     * @return void
     * @throws StringsException
     */
    protected function before(Service $service): void
    {
        $this->context = Context::fromMetadata();
        $welcomeMessage = sprintf(
            "\n👋 Hello! I'm the aent <info>Traefik</info> and I'm going to configure the virtual host of your service <info>%s</info> on your <info>%s</info> environment <info>%s</info>.",
            $service->getServiceName(),
            $this->context->getType(),
            $this->context->getName()
        );
        $this->output->writeln($welcomeMessage);
    }

    /**
     * @param Service $service
     * @return Service
     * @throws NewVirtualHostEventException
     */
    protected function process(Service $service): Service
    {
        $service = $this->getURL($service);
        return $service;
    }

    /**
     * @param Service $service
     * @return Service
     * @throws NewVirtualHostEventException
     */
    private function getURL(Service $service): Service
    {
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
                $url = $virtualHost['hostPrefix'] . '.' . $this->context->getBaseVirtualHost();
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

    /**
     * @param Service $service
     * @return void
     * @throws StringsException
     */
    protected function after(Service $service): void
    {
        $afterMessage = sprintf(
            "\nI've finished the setup of your service <info>%s</info> on your <info>%s</info> environment <info>%s</info>.",
            $service->getServiceName(),
            $this->context->getType(),
            $this->context->getName()
        );
        $this->output->writeln($afterMessage);
    }
}
