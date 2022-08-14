<?php

namespace MugoWeb\Eep\Bundle\Command;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentLocationCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        PermissionResolver $permissionResolver,
        UserService $userService
    )
    {
        $this->contentService = $contentService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;

        parent::__construct();
    }

    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:content:location')
            ->setAliases(array('eep:co:lo', 'eep:co:location'))
            ->setDescription('Returns main location id by content id')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('no-newline', 'x', null, 'Output without newline')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

	    $content = $this->contentService->loadContent($inputContentId);

        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('no-newline'))
        {
            $io->write($content->contentInfo->mainLocationId);
        }
        else
        {
            $io->writeln($content->contentInfo->mainLocationId);
        }

        return Command::SUCCESS;
    }
}
