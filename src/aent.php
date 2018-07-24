#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use TheAentMachine\AentApplication;
use TheAentMachine\AentTraefik\Command\AddEventCommand;
use TheAentMachine\AentTraefik\Command\NewVirtualHostEventCommand;

$application = new AentApplication();

$application->add(new AddEventCommand());
$application->add(new NewVirtualHostEventCommand());

$application->run();
