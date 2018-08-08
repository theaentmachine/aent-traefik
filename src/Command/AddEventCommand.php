<?php

namespace TheAentMachine\AentTraefik\Command;

use TheAentMachine\Aenthill\CommonEvents;
use TheAentMachine\Aenthill\Manifest;
use TheAentMachine\Aenthill\CommonMetadata;
use TheAentMachine\Command\AbstractJsonEventCommand;
use TheAentMachine\Question\CommonValidators;
use TheAentMachine\Service\Service;

class AddEventCommand extends AbstractJsonEventCommand
{
    protected function getEventName(): string
    {
        return CommonEvents::ADD_EVENT;
    }

    /**
     * @param mixed[] $payload
     * @return mixed[]|null
     * @throws \TheAentMachine\Exception\ManifestException
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

        $baseDomainName = $aentHelper->question('What is the base domain name of your environment?')
            ->setDefault('.test.localhost')
            ->setHelpText('By default, all virtualhosts will be created using the base domain name as a starting point.')
            ->compulsory()
            ->setValidator(function (string $value) {
                $value = trim($value);
                if (!\preg_match('/^\.(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?$/im', $value)) {
                    throw new \InvalidArgumentException('Invalid value "' . $value .
                        '". Hint: the base domain name must start with a dot (.). For instance: ".foobar.com" is a valid base domain name.'.
                        "\nAdvice: on development environment, you should end your base domain name with \".localhost\". On Linux environment, \"*.localhost\" resolves to your host machine so you don't have to edit your /etc/hosts file for these domains (Linux only!).");
                }
                return $value;
            })
            ->ask();

        Manifest::addMetadata('BASE_DOMAIN_NAME', $baseDomainName);

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
                ->setDefault('traefik'.$baseDomainName)
                ->compulsory()
                ->setValidator(CommonValidators::getDomainNameValidator())
                ->ask();

            $service->addLabel('traefik.enable', 'true');
            $service->addLabel('traefik.backend', 'traefik');
            $service->addLabel('traefik.frontend.rule', 'Host:' . $url);
            $service->addLabel('traefik.port', '8080');
        }

        /************************ HTTPS **********************/
        $envType = Manifest::mustGetMetadata(CommonMetadata::ENV_TYPE_KEY);
        $doAddHttps = $envType === CommonMetadata::ENV_TYPE_PROD;
        if ($envType === CommonMetadata::ENV_TYPE_TEST) {
            $doAddHttps = $aentHelper->question('Do you want to add HTTPS support using Let\'s encrypt?')
                ->yesNoQuestion()
                ->setDefault('y')
                ->ask();
        }
        if ($doAddHttps) {
            $service->addPort(443, 443);
            $this->output->writeln('<info>HTTPS has been enabled</info>');
            $this->getAentHelper()->spacer();
        }

        return $service->jsonSerialize();
    }
}
