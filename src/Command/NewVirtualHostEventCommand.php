<?php

namespace TheAentMachine\AentTraefik\Command;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use TheAentMachine\AentTraefik\EventEnum;
use TheAentMachine\CommonEvents;
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
        return 'NEW_VIRTUAL_HOST';
    }

    protected function executeJsonEvent(array $payload): void
    {
        $serviceName = $payload['service'];
        $virtualHost = $payload['virtualHost'] ?? null;
        $virtualPort = $payload['virtualPort'];

        $helper = $this->getHelper('question');
        $service = new Service();

        if ($virtualHost === null) {
            $this->output->writeln('You are about to <info>configure the domain name</info> of this service in the reverse proxy (Traefik).');
            $question = new Question('What is the domain name of this service? : ', '');
            $question->setValidator(function (string $value) {
                $value = trim($value);
                if (!\preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?$/im', $value)) {
                    throw new \InvalidArgumentException('Invalid domain name "'.$value.'". Note: the domain name must not start with "http(s)://"');
                }

                return $value;
            });

            $virtualHost = $helper->ask($this->input, $this->output, $question);
        }

        $this->output->writeln("<info>Adding host redirection from '$virtualHost' to service '$serviceName' on port '$virtualPort'</info>");

        $service->setServiceName($serviceName);
        $service->addLabel('traefik.enable', 'true');
        $service->addLabel('traefik.backend', $serviceName);
        $service->addLabel('traefik.frontend.rule', 'Host:'.$virtualHost);
        $service->addLabel('traefik.port', (string) $virtualPort);

        $commonEvents = new CommonEvents();
        $commonEvents->dispatchService($service, $helper, $this->input, $this->output);
    }
}
