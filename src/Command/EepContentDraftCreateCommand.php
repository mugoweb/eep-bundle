<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentDraftCreateCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
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
            ->setName('eep:content:draftcreate')
            ->setAliases(array('eep:co:draftcreate'))
            ->setDescription('Create draft from content')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('version-number', InputArgument::OPTIONAL, 'Version number', null)
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputVersionNumber = $input->getArgument('version-number');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        if ($input->getArgument('version-number'))
        {
            $versionInfo = $this->contentService->loadVersionInfoById($inputContentId, $inputVersionNumber);
            $contentInfo = $versionInfo->getContentInfo();
        }
        else
        {
            $contentInfo = $this->contentService->loadContentInfo($inputContentId);
        }

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to create a draft of content "%s"?',
                    $contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputContentId,
                $inputVersionNumber,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $contentDraft = $this->contentService->createContentDraft($contentInfo);
                $draftVersionInfo = $contentDraft->getVersionInfo();

                $io->success("Draft create successful. id: {$draftVersionInfo->id} versionNo: {$draftVersionInfo->versionNo} contentId: {$contentInfo->id}");
                $this->logger->info($this->getName() . " successful", array($draftVersionInfo->id, $draftVersionInfo->versionNo, $contentInfo->id));
            }
            catch
            (
            UnauthorizedException
            $e
            )
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Draft create cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
