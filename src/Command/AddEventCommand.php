<?php

namespace TheAentMachine\AentTraefik\Command;

use TheAentMachine\Aenthill\Manifest;
use TheAentMachine\Aenthill\Metadata;
use TheAentMachine\Command\JsonEventCommand;
use TheAentMachine\Service\Service;

class AddEventCommand extends JsonEventCommand
{
    protected function getEventName(): string
    {
        return 'ADD';
    }

    /**
     * @param mixed[] $payload
     * @return mixed[]|null
     * @throws \TheAentMachine\Exception\ManifestException
     * @throws \TheAentMachine\Exception\MissingEnvironmentVariableException
     * @throws \TheAentMachine\Service\Exception\ServiceException
     */
    protected function executeJsonEvent(array $payload): ?array
    {
        $service = new Service();
        $aentHelper = $this->getAentHelper();

        $this->log->notice('Installing traefik reverse proxy');

        $service->setServiceName('traefik');
        $service->setImage('traefik:1.6');
        $service->addPort(80, 80);
        // Use default docker parameters
        $service->addCommand('--docker');
        // Do not expose services by default (otherwise it's insecure!)
        $service->addCommand('--docker.exposedbydefault=false');

        $service->addBindVolume('/var/run/docker.sock', '/var/run/docker.sock');

        $answer = $aentHelper->question('Do you want to enable Traefik UI?')
            ->yesNoQuestion()
            ->setDefault('y')
            ->setHelpText('The Traefik UI can be useful in development environments.')
            ->ask();

        if ($answer) {
            // Let's enable the UI
            $service->addCommand('--api');

            $url = $aentHelper->question('Traefik UI domain name')
                ->setHelpText('This is the domain name you will use to access the Traefik UI.')
                ->compulsory()
                ->setValidator(function (string $value) {
                    $value = trim($value);
                    if (!\preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?$/im', $value)) {
                        throw new \InvalidArgumentException('Invalid domain name "' . $value . '". Note: the domain name must not start with "http(s)://"');
                    }

                    return $value;
                })
                ->ask();

            $service->addLabel('traefik.enable', 'true');
            $service->addLabel('traefik.backend', 'traefik');
            $service->addLabel('traefik.frontend.rule', 'Host:' . $url);
            $service->addLabel('traefik.port', '8080');
        }

        /************************ HTTPS **********************/
        $envType = Manifest::getMetadata(Metadata::ENV_TYPE_KEY);
        $doAddHttps = $envType === Metadata::ENV_TYPE_PROD;
        if ($envType === Metadata::ENV_TYPE_TEST) {
            $doAddHttps = $aentHelper->question('Do you want to add HTTPS support using Let\'s encrypt?')
                ->yesNoQuestion()
                ->setDefault('y')
                ->ask();
        }
        if ($doAddHttps) {
            $service->addPort(443, 443);
            $this->output->writeln('HTTPS has been enabled');
        }

        // $commonEvents = new CommonEvents($this->getAentHelper(), $this->output);
        // $commonEvents->dispatchService($service);

        return $service->jsonSerialize();
    }
}
