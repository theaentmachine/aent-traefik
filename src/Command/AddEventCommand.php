<?php

namespace TheAentMachine\AentTraefik\Command;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use TheAentMachine\AentTraefik\EventEnum;
use TheAentMachine\CommonEvents;
use TheAentMachine\EventCommand;
use TheAentMachine\Hercule;
use TheAentMachine\Hermes;
use TheAentMachine\Pheromone;
use TheAentMachine\Service\Service;

class AddEventCommand extends EventCommand
{
    protected function getEventName(): string
    {
        return 'ADD';
    }

    protected function executeEvent(?string $payload): void
    {
        $helper = $this->getHelper('question');

        $service = new Service();

        $this->log->notice("Installing traefik reverse proxy");

        $service->setServiceName('traefik');
        $service->setImage('traefik:1.6');
        $service->addPort(80, 80);
        // Use default docker parameters
        $service->addCommand('--docker');
        // Do not expose services by default (otherwise it's insecure!)
        $service->addCommand('--docker.exposedbydefault=false');

        $service->addBindVolume('/var/run/docker.sock', '/var/run/docker.sock');

        $question = new ChoiceQuestion(
            "Do you want to enable Traefik UI? (useful in development environments) [No] ",
            array('Yes', 'No'),
            1
        );
        $answer = $helper->ask($this->input, $this->output, $question);
        if ($answer === 'Yes') {
            // Let's enable the UI
            $service->addCommand('--api');

            $question = new Question('What will be the domain name of Traefik? : ', '');
            $question->setValidator(function (string $value) {
                $value = trim($value);
                if (!\preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?$/im', $value)) {
                    throw new \InvalidArgumentException('Invalid domain name "'.$value.'". Note: the domain name must not start with "http(s)://"');
                }

                return $value;
            });

            $url = $helper->ask($this->input, $this->output, $question);

            $service->addLabel('traefik.enable', 'true');
            $service->addLabel('traefik.backend', 'traefik');
            $service->addLabel('traefik.frontend.rule', 'Host:'.$url);
            $service->addLabel('traefik.port', '8080');
        }

        /************************ HTTPS **********************/
        /*$https = false;
        $question = new ChoiceQuestion(
            "Do you want to add HTTPS support using Let's encrypt? [No] ",
            array('Yes', 'No'),
            1
        );
        $answer = $helper->ask($this->input, $this->output, $question);
        if ($answer === 'Yes') {
            $https = true;
            $service->addPort(443, 443);
        }*/

        $commonEvents = new CommonEvents();
        $commonEvents->dispatchService($service, $helper, $this->input, $this->output);
    }
}
