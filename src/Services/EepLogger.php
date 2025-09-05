<?php

namespace MugoWeb\Eep\Bundle\Services;

use Symfony\Component\HttpKernel\KernelInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class EepLogger extends Logger
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct('eep', [new StreamHandler($kernel->getLogDir() . "/eep.log")]);
    }
}