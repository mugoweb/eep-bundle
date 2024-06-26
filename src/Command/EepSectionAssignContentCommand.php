<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\SectionId;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\SectionService;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepSectionAssignContentCommand extends Command
{
    public function __construct
    (
        SectionService $sectionService,
        ContentService $contentService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->sectionService = $sectionService;
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
            ->setName('eep:section:assigncontent')
            ->setAliases(array('eep:se:assigncontent'))
            ->setDescription('Assign content to section')
            ->addArgument('section-identifier', InputArgument::REQUIRED, 'Section identifier')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputSectionIdentifier = $input->getArgument('section-identifier');
        $inputContentId = $input->getArgument( 'content-id' );
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to assign section "%s" to content id %d?',
                    $inputSectionIdentifier,
                    $inputContentId
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputSectionIdentifier,
                $inputContentId,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $section = $this->sectionService->loadSectionByIdentifier($inputSectionIdentifier);
                $contentInfo = $this->contentService->loadContentInfo($inputContentId);

                $this->sectionService->assignSection($contentInfo, $section);

                $io->success('Assignment successful');
                $this->logger->info($this->getName() . " successful");
            }
            catch (UnauthorizedException $e)
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Assignment cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
