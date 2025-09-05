<?php

namespace MugoWeb\Eep\Bundle\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentRepublishCommand extends Command
{
    public function __construct
    (
        private readonly ContentService $contentService,
        private readonly PermissionResolver $permissionResolver,
        private readonly UserService $userService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:content:republish')
            ->setAliases(array('eep:co:republish'))
            ->setDescription('Re-publishes content by id')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentInfo = $this->contentService->loadContentInfo($inputContentId);
        $contentDraft = $this->contentService->createContentDraft($contentInfo);

        $published = $this->contentService->publishVersion($contentDraft->versionInfo);

        $report = ($published)? "Republished {$inputContentId}" : "Failed to republish {$inputContentId}";

        $io = new SymfonyStyle($input, $output);
        $io->writeln($report);

        return Command::SUCCESS;
    }
}
