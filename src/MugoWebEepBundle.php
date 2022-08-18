<?php

namespace MugoWeb\Eep\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MugoWebEepBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
