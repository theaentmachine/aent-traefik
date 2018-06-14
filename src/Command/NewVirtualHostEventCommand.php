<?php

namespace TheAentMachine\AentTraefik\Command;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use TheAentMachine\AentTraefik\EventEnum;
use TheAentMachine\EventCommand;
use TheAentMachine\Hercule;
use TheAentMachine\Hermes;
use TheAentMachine\JsonEventCommand;
use TheAentMachine\Pheromone;
use TheAentMachine\Service\Service;

class NewVirtualHostEventCommand extends JsonEventCommand
{
    protected function getEventName(): string
    {
        return EventEnum::NEW_VIRTUAL_HOST;
    }

    protected function executeJsonEvent(array $payload): void
    {
        $serviceName = $payload['service'];
        $virtualHost = $payload['virtualHost'];
        $virtualPort = $payload['virtualPort'];

        $service = new Service();

        $this->output->writeln("<info>Adding host redirection from '$virtualHost' to service '$serviceName' on port '$virtualPort'</info>");

        $service->setServiceName($serviceName);
        $service->addLabel('traefik.backend', $serviceName);
        $service->addLabel('traefik.frontend.rule', 'Host:'.$virtualHost);
        $service->addLabel('traefik.port', (string) $virtualPort);

        Hermes::dispatchJson(EventEnum::NEW_DOCKER_SERVICE_INFO, $service->jsonSerialize());
    }
}
