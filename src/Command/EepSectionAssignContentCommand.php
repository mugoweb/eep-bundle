<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\SectionId;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\SectionService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
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
        try
        {
            $section = $this->sectionService->loadSectionByIdentifier($inputSectionIdentifier);
            $contentInfo = $this->contentService->loadContentInfo($inputContentId);

            $this->sectionService->assignSection($contentInfo, $section);
        }
        catch(\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e)
        {
            $io->error($e->getMessage());
        }

        $io->success('Assignment successful');
    }
}
