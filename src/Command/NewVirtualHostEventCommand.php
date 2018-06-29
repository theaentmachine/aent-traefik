<?php

namespace TheAentMachine\AentTraefik\Command;

use Symfony\Component\Console\Question\Question;
use TheAentMachine\CommonEvents;
use TheAentMachine\JsonEventCommand;
use TheAentMachine\Service\Service;

class NewVirtualHostEventCommand extends JsonEventCommand
{
    protected function getEventName(): string
    {
        return 'NEW_VIRTUAL_HOST';
    }

    /**
     * @throws \TheAentMachine\Exception\CannotHandleEventException
     */
    protected function executeJsonEvent(array $payload): ?array
    {
        $serviceName = $payload['service'];
        $virtualHost = $payload['virtualHost'] ?? null;
        $virtualPort = $payload['virtualPort'];

        $helper = $this->getHelper('question');
        $service = new Service();

        if ($virtualHost === null) {
            $this->output->writeln("You are about to <info>configure the domain name</info> of the service <info>$serviceName</info> in the reverse proxy (Traefik).");
            $question = new Question('What is the domain name of this service? : ', '');
            $question->setValidator(function (string $value) {
                $value = trim($value);
                if (!\preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?$/im', $value)) {
                    throw new \InvalidArgumentException('Invalid domain name "' . $value . '". Note: the domain name must not start with "http(s)://"');
                }

                return $value;
            });

            $virtualHost = $helper->ask($this->input, $this->output, $question);
        }

        $this->output->writeln("<info>Adding host redirection from '$virtualHost' to service '$serviceName' on port '$virtualPort'</info>");

        $service->setServiceName($serviceName);
        $service->addLabel('traefik.enable', 'true');
        $service->addLabel('traefik.backend', $serviceName);
        $service->addLabel('traefik.frontend.rule', 'Host:' . $virtualHost);
        $service->addLabel('traefik.port', (string)$virtualPort);

        $commonEvents = new CommonEvents($this->getAentHelper(), $this->output);
        $commonEvents->dispatchService($service);

        return [
            'virtualHost' => $virtualHost
        ];
    }
}
