<?php

namespace TheAentMachine\AentTraefik\Command;

use TheAentMachine\Aenthill\CommonEvents;
use TheAentMachine\Command\AbstractJsonEventCommand;
use TheAentMachine\Question\CommonValidators;
use TheAentMachine\Service\Exception\ServiceException;
use TheAentMachine\Service\Service;

class NewVirtualHostEventCommand extends AbstractJsonEventCommand
{
    protected function getEventName(): string
    {
        return CommonEvents::NEW_VIRTUAL_HOST_EVENT;
    }

    /**
     * @param mixed[] $payload
     * @return array|null
     * @throws ServiceException
     */
    protected function executeJsonEvent(array $payload): ?array
    {
        $service = Service::parsePayload($payload);
        $serviceName = $service->getServiceName();
        $ports = $service->getPorts();

        if (!empty($ports) && isset($ports[0])) {
            $virtualPort = $ports[0];
        } else {
            $virtualPort = "80";
        }

        $this->output->writeln("You are about to <info>configure the domain name</info> of the service <info>$serviceName</info> in the reverse proxy (Traefik).");

        $virtualHost = $this->getAentHelper()->question('What is the domain name of this service?')
            ->compulsory()
            ->setValidator(CommonValidators::getDomainNameValidator())
            ->ask();

        $this->output->writeln("<info>Adding host redirection from '$virtualHost' to service '$serviceName' on port '$virtualPort'</info>");

        $service->setServiceName($serviceName);
        $service->addLabel('traefik.enable', 'true');
        $service->addLabel('traefik.backend', $serviceName);
        $service->addLabel('traefik.frontend.rule', 'Host:' . $virtualHost);
        $service->addLabel('traefik.port', $virtualPort);

        return $service->jsonSerialize();
    }
}
