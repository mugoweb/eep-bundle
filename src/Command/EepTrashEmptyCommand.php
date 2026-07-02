<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\TrashService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepTrashEmptyCommand extends Command
{
    public function __construct
    (
        TrashService $trashService,
        PermissionResolver $permissionResolver,
        ContentTypeService $contentTypeService,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->trashService = $trashService;
        $this->contentTypeService = $contentTypeService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:trash:empty')
            ->setAliases(array('eep:tr:empty'))
            ->setDescription('Empties the trash')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                'Are you sure you want to empty the trash?',
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $this->trashService->emptyTrash();

                $io->success('Empty successful');
                $this->logger->info($this->getName() . " successful");
            }
            catch(Exceptions\UnauthorizedException $e)
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Empty cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
