<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentUpdateCommand extends Command
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
            ->setName('eep:content:update')
            ->setAliases(array('eep:co:update'))
            ->setDescription('(experimental!) Update content')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('field-data', InputArgument::REQUIRED, 'Content field data as JSON string')
            ->addArgument('initial-language-code', InputArgument::REQUIRED, 'Initial language code for new version')
            ->addOption('from-file', 'f', InputOption::VALUE_NONE, 'Field data should be read from file. Treat field data argument as file path')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputFieldData = ($input->getOption('from-file'))? file_get_contents($input->getArgument('field-data')) : $input->getArgument('field-data');
        $inputInitialLanguageCode = $input->getArgument('initial-language-code');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);
        $contentType = $content->getContentType();

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to update content "%s"?',
                    $content->contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputContentId,
                '--',
                $inputInitialLanguageCode,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                // create a content draft from the current published version
                $contentDraft = $this->contentService->createContentDraft($content->contentInfo);

                // instantiate a content update struct and set the new fields
                $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
                $contentUpdateStruct->initialLanguageCode = $inputInitialLanguageCode;

                $fieldData = json_decode($inputFieldData, true);
                foreach ($fieldData as $fieldIdentifier => $fieldValue)
                {
                    switch ($contentType->getFieldDefinition($fieldIdentifier)->fieldTypeIdentifier)
                    {
                        case 'ezboolean':
                        {
                            // need to cast; fromString not implemented to support boolean like values
                            $fieldValue = (boolean) $fieldValue;
                        }
                        break;
                    }
                    $contentUpdateStruct->setField($fieldIdentifier, $fieldValue);
                }

                // update and publish draft
                $contentDraft = $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                $content = $this->contentService->publishVersion($contentDraft->versionInfo);

                $io->success('Update successful');
                $this->logger->info($this->getName() . " successful");
            }
            catch
            (
                ContentFieldValidationException |
                ContentValidationException |
                UnauthorizedException
                $e
            )
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", $e->getMessage());
            }
        }
        else
        {
            $io->writeln('Update cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
