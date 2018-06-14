<?php

namespace TheAentMachine\AentTraefik;

class EventEnum
{
    public const ADD = 'ADD';
    public const NEW_DOCKER_SERVICE_INFO = 'NEW_DOCKER_SERVICE_INFO';
    public const NEW_VIRTUAL_HOST = 'NEW_VIRTUAL_HOST';

    /**
     * @return string[]
     */
    public static function getHandledEvents(): array
    {
        return array(
            self::ADD,
            self::NEW_VIRTUAL_HOST,
        );
    }
}
