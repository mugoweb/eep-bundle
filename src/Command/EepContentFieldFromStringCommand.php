<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\FieldTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentFieldFromStringCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        FieldTypeService $fieldTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentService = $contentService;
        $this->fieldTypeService = $fieldTypeService;
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
            ->setName('eep:contentfield:fromstring')
            ->setAliases(array('eep:cf:fromstring'))
            ->setDescription('Set content field value from JSON string')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('content-field-identifier', InputArgument::REQUIRED, 'Content field identifier')
            ->addArgument('field-data', InputArgument::REQUIRED, 'Content field value data as JSON string')
            ->addOption('from-file', 'f', InputOption::VALUE_NONE, 'Field data should be read from file. Treat field data argument as file path')
            ->addOption('no-publish', null, InputOption::VALUE_NONE, 'Update as draft')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputContentFieldIdentifier = $input->getArgument('content-field-identifier');
        $inputFieldData = ($input->getOption('from-file'))? file_get_contents($input->getArgument('field-data')) : $input->getArgument('field-data');
        $inputFieldData = json_decode($inputFieldData)->$inputContentFieldIdentifier;
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);
        $field = $content->getField($inputContentFieldIdentifier);
        $fieldType = $this->fieldTypeService->getFieldType($field->fieldTypeIdentifier);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to update content field %s on "%s"?',
                    $inputContentFieldIdentifier,
                    $content->contentInfo->name
                ),
                false
            );
        }

        $loggerContext = array
        (
            $inputContentId,
            $inputContentFieldIdentifier,
            $input->getArgument('field-data'),
            $inputUserId
        );
        $this->logger->info($this->getName() . " confirmed", $loggerContext);

        try
        {
            // create a content draft from the current published version
            $contentDraft = $this->contentService->createContentDraft($content->contentInfo);

            switch ($field->fieldTypeIdentifier)
            {
                case 'ezboolean':
                {
                    // need to cast; fromString not implemented to support boolean like values
                    $inputFieldData = (boolean) $inputFieldData;
                }
                break;
            }

            // instantiate a content update struct and set the new fields
            $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
            $contentUpdateStruct->setField($inputContentFieldIdentifier, $inputFieldData);

            // update and publish draft
            $contentDraft = $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
            if(!$input->getOption('no-publish'))
            {
                $content = $this->contentService->publishVersion($contentDraft->versionInfo);
            }

            $io->success("Update successful. contentId: {$content->id} contentFieldIdentifier: {$inputContentFieldIdentifier}");

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
            $this->logger->error($this->getName() . " error", array($e->getMessage()));
        }
    }
}
