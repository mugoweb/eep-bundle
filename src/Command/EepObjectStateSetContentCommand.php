<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use Ibexa\Contracts\Core\Repository\ObjectStateService;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepObjectStateSetContentCommand extends Command
{
    public function __construct
    (
        ObjectStateService $objectStateService,
        ContentService $contentService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->objectStateService = $objectStateService;
        $this->contentService = $contentService;
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
            ->setName('eep:objectstate:setcontent')
            ->setAliases(array('eep:os:setcontent'))
            ->setDescription('Set object state for content')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('group-id', InputArgument::REQUIRED, 'Object state group id')
            ->addArgument('state-id', InputArgument::REQUIRED, 'Object state id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputGroupId = $input->getArgument('group-id');
        $inputStateId = $input->getArgument('state-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to set object state id %d (group id %d) on content id %d?',
                    $inputStateId,
                    $inputGroupId,
                    $inputContentId
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputContentId,
                $inputGroupId,
                $inputStateId,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $contentInfo = $this->contentService->loadContentInfo($inputContentId);
                $objectStateGroup = $this->objectStateService->loadObjectStateGroup($inputGroupId);
                $objectState = $this->objectStateService->loadObjectState($inputStateId);

                $this->objectStateService->setContentState($contentInfo, $objectStateGroup, $objectState);

                $io->success('Object state set successfully');
                $this->logger->info($this->getName() . " successful");
            }
            catch
            (
                Exceptions\NotFoundException |
                Exceptions\UnauthorizedException |
                Exceptions\InvalidArgumentException
                $e
            )
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Object state assignment cancelled by user action');
        }

        return Command::SUCCESS;
    }
}