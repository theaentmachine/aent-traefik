<?php

namespace TheAentMachine\AentTraefik\Event;

use TheAentMachine\Aent\Context\Context;
use TheAentMachine\Aent\Event\ReverseProxy\AbstractReverseProxyAddEvent;
use TheAentMachine\Aent\Payload\ReverseProxy\ReverseProxyAddPayload;
use TheAentMachine\Service\Service;

final class AddEvent extends AbstractReverseProxyAddEvent
{
    private const IMAGE = 'traefik';

    /** @var Context */
    private $context;

    /**
     * @param ReverseProxyAddPayload $payload
     * @return Service
     */
    protected function createService(ReverseProxyAddPayload $payload): Service
    {
        $this->context = Context::fromMetadata();
        $version = $this->prompt->getPromptHelper()->getVersion(self::IMAGE);
        $image = self::IMAGE . ':' . $version;
        $service = new Service();
        $service->setServiceName('traefik');
        $service->setImage($image);
        $service->addPort(80, 80);
        // Use default docker parameters
        $service->addCommand('--docker');
        // Do not expose services by default (otherwise it's insecure!)
        $service->addCommand('--docker.exposedbydefault=false');
        $service->addBindVolume('/var/run/docker.sock', '/var/run/docker.sock');
        //$service = $this->addWebUI($service, $payload->getBaseVirtualHost());
        $service = $this->addHTTPS($service);
        return $service;
    }

    /**
     * @param Service $service
     * @param string $baseVirtualHost
     * @return Service
     */
    /*private function addWebUI(Service $service, string $baseVirtualHost): Service
    {
        if ($this->context->isProduction()) {
            return $service;
        }
        $text = "\nDo you want to enable <info>web UI</info>?";
        $helpText = "The <info>web UI</info> shows configured frontend, backends, servers, options etc.";
        $response = $this->prompt->confirm($text, $helpText, true);
        if (!$response) {
            $this->output->writeln("\n👌 Alright, I'm not going to configure the <info>web UI</info>!");
            return $service;
        }
        $url = 'traefik.' . $baseVirtualHost;
        $service->addCommand('--api');
        $service->addLabel('traefik.enable', 'true');
        $service->addLabel('traefik.backend', 'traefik');
        $service->addLabel('traefik.frontend.rule', 'Host:' . $url);
        $service->addLabel('traefik.port', '8080');
        $this->output->writeln("\n👌 Alright, your <info>web UI</info> will be accessible at <info>$url</info>!");
        return $service;
    }*/

    /**
     * @param Service $service
     * @return Service
     */
    private function addHTTPS(Service $service): Service
    {
        if ($this->context->isDevelopment()) {
            return $service;
        }
        if ($this->context->isTest()) {
            $text = "\nDo you want to add <info>HTTPS</info> support using <info>Let's encrypt</info>?";
            $response = $this->prompt->confirm($text, null, true);
            if (!$response) {
                $this->output->writeln("\n👌 Alright, I've not configured the <info>HTTPS</info> support!");
                return $service;
            }
            $this->output->writeln("\n👌 Alright, I've configured the <info>HTTPS</info> support!");
        }
        if ($this->context->isProduction()) {
            $this->output->writeln("\nAs your on a production environment, I've configured the <info>HTTPS</info> support!");
        }
        // TODO
        $service->addPort(443, 443);
        return $service;
    }
}
