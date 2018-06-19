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


        /************************ PHP Version **********************/
        $https = false;
        $question = new ChoiceQuestion(
            "Do you want to add HTTPS support using Let's encrypt? [No] ",
            array('Yes', 'No'),
            1
        );
        $answer = $helper->ask($this->input, $this->output, $question);
        if ($answer === 'Yes') {
            $https = true;
            $service->addPort(443, 443);
        }

        $commonEvents = new CommonEvents();
        $commonEvents->dispatchService($service, $helper, $this->input, $this->output);
    }
}
