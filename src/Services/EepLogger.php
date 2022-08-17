<?php

namespace MugoWeb\Eep\Bundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class EepLogger extends Logger
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct('eep', [new StreamHandler($container->get('kernel')->getLogDir() . "/eep.log")]);
    }
}