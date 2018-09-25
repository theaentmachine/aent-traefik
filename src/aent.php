#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use \TheAentMachine\Aent\ReverseProxyAent;
use \TheAentMachine\AentTraefik\Event\AddEvent;
use \TheAentMachine\AentTraefik\Event\NewVirtualHostEvent;

$application = new ReverseProxyAent('Traefik', new AddEvent(), new NewVirtualHostEvent());
$application->run();
