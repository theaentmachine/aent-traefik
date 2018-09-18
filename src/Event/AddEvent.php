<?php

namespace TheAentMachine\AentTraefik\Event;

use Safe\Exceptions\StringsException;
use TheAentMachine\Aent\Context\Context;
use TheAentMachine\Aent\Event\ReverseProxy\AbstractReverseProxyAddEvent;
use TheAentMachine\Service\Service;
use function \Safe\sprintf;

final class AddEvent extends AbstractReverseProxyAddEvent
{
    private const IMAGE = 'traefik';

    /** @var Context */
    private $context;

    /**
     * @return void
     * @throws StringsException
     */
    protected function before(): void
    {
        $this->context = Context::fromMetadata();
        $welcomeMessage = sprintf(
            "\n👋 Hello! I'm the aent <info>Traefik</info> and I'll help you setting up a <info>Traefik</info> service for your <info>%s</info> environment <info>%s</info>.",
            $this->context->getType(),
            $this->context->getName()
        );
        $this->output->writeln($welcomeMessage);
    }

    /**
     * @return Service
     */
    protected function process(): Service
    {
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
        //$service = $this->addWebUI($service);
        $service = $this->addHTTPS($service);
        return $service;
    }

    /**
     * @return void
     */
    protected function after(): void
    {
        $this->output->writeln("\n<info>Traefik</info> service setup is finished, see you later!");
    }

    /**
     * @param Service $service
     * @return Service
     */
    /*private function addWebUI(Service $service): Service
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
        $url = 'traefik.' . $this->context->getBaseVirtualHost();
        $service->addCommand('--api');
        $service->addLabel('traefik.enable', 'true');
        $service->addLabel('traefik.backend', 'traefik');
        $service->addLabel('traefik.frontend.rule', 'Host:' . $url);
        $service->addLabel('traefik.port', '8080');
        $this->output->writeln("\n👌 Alright, your <info>web UI</info> will be accessible at <info>$url</info>!");
        return $service;
    }:*

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
