<?php

namespace Eep\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentUpdateCommand extends ContainerAwareCommand
{
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
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputFieldData = $input->getArgument('field-data');
        $inputInitialLanguageCode = $input->getArgument('initial-language-code');
        $inputUserId = $input->getOption('user-id');

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($inputUserId));
        $contentService = $repository->getContentService();

        $contentInfo = $contentService->loadContentInfo($inputContentId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to update content "%s"?',
                    $contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            try
            {
                // create a content draft from the current published version
                $contentDraft = $contentService->createContentDraft($contentInfo);

                // instantiate a content update struct and set the new fields
                $contentUpdateStruct = $contentService->newContentUpdateStruct();
                $contentUpdateStruct->initialLanguageCode = $inputInitialLanguageCode;

                // TODO: only basic field handling
                // { "name": "Foo", "description": "Bar" }
                $fieldData = json_decode($inputFieldData, true);
                foreach($fieldData as $fieldIdentifier => $fieldValue)
                {
                    $contentUpdateStruct->setField($fieldIdentifier, $fieldValue);
                }

                // update and publish draft
                $contentDraft = $contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                $content = $contentService->publishVersion($contentDraft->versionInfo);
            }
            catch (\eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException $e)
            {
                $io->error($e->getMessage());
            }
            catch (\eZ\Publish\API\Repository\Exceptions\ContentValidationException $e)
            {
                $io->error($e->getMessage());
            }
            catch(\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e)
            {
                $io->error($e->getMessage());
            }

            $io->success('Update successful');
        }
        else
        {
            $io->writeln('Update cancelled by user action');
        }
    }
}
