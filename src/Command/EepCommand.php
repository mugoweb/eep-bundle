<?php

namespace MugoWeb\Eep\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EepCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep')
            ->setDescription('eep - Ease eZ Platform/Ibexa DXP development')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: maybe show some examples here with eep, awk, xargs
        // or get rid of the command
    }

}
