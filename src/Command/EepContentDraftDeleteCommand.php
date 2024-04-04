<?php

namespace MugoWeb\Eep\Bundle\Command;

use eZ\Publish\Core\Base\Exceptions\BadStateException;
use MugoWeb\Eep\Bundle\Services\EepLogger;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentDraftDeleteCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentService = $contentService;
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
            ->setName('eep:content:draftdelete')
            ->setAliases(array('eep:co:draftdelete'))
            ->setDescription('Deletes content draft version')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('version-number', InputArgument::REQUIRED, 'Version number')
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

        $contentInfo = $this->contentService->loadContentInfo($inputContentId);
        $versionInfo = $this->contentService->loadVersionInfoById($inputContentId, $inputVersionNumber);

        $io = new SymfonyStyle($input, $output);
        $isDraftVersion = ($versionInfo->status === \eZ\Publish\API\Repository\Values\Content\VersionInfo::STATUS_DRAFT)? true : false;
        if (!$isDraftVersion)
        {
            $isNotDraftError = sprintf(
                'Version %d of content "%s" is not a draft version. Aborting.',
                $inputVersionNumber,
                $contentInfo->name,
            ); 
            $io->error($isNotDraftError);
            $this->logger->error($this->getName() . " error", array($isNotDraftError));
        }

        $confirm = $input->getOption('no-interaction');
        if ($isDraftVersion && !$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to delete draft version %d of content "%s"?',
                    $inputVersionNumber,
                    $contentInfo->name,
                ),
                false
            );
        }

        if ($isDraftVersion && $confirm)
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
                $this->contentService->deleteVersion($this->contentService->loadVersionInfoById($inputContentId, $inputVersionNumber));

                $io->success('Delete successful');
                $this->logger->info($this->getName() . " successful");
            }
            catch
            (
                UnauthorizedException |
                BadStateException $e
            )
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        elseif ($isDraftVersion && !$confirm)
        {
            $io->writeln('Delete cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
