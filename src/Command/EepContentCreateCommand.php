<?php

namespace Eep\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentCreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:content:create')
            ->setAliases(array('eep:co:create'))
            ->setDescription('(experimental!) Create content at location')
            ->addArgument('content-type-identifier', InputArgument::REQUIRED, 'Content type identifier')
            ->addArgument('parent-location-id', InputArgument::REQUIRED, 'Parent location id')
            ->addArgument('field-data', InputArgument::REQUIRED, 'Content field data as JSON string')
            ->addArgument('main-language-code', InputArgument::REQUIRED, 'Main language code')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentTypeIdentifier = $input->getArgument('content-type-identifier');
        $inputParentLocationId = $input->getArgument('parent-location-id');
        $inputFieldData = $input->getArgument('field-data');
        $inputMainLanguageCode = $input->getArgument('main-language-code');
        $inputUserId = $input->getOption('user-id');

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($inputUserId));
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $location = $locationService->loadLocation($inputParentLocationId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to create content at "%s"?',
                    $location->contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            try
            {
                $contentType = $contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);
                $contentCreateStruct = $contentService->newContentCreateStruct($contentType, $inputMainLanguageCode);

                // TODO: only basic field handling
                // { "name": "Foo", "description": "Bar" }
                $fieldData = json_decode($inputFieldData, true);
                foreach($fieldData as $fieldIdentifier => $fieldValue)
                {
                    $contentCreateStruct->setField($fieldIdentifier, $fieldValue);
                }

                // instantiate a location create struct from the parent location
                $locationCreateStruct = $locationService->newLocationCreateStruct($inputParentLocationId);

                // create a draft using the content and location create struct and publish it
                $draft = $contentService->createContent($contentCreateStruct, array($locationCreateStruct));
                $content = $contentService->publishVersion($draft->versionInfo);
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

            $io->success('Create successful');
        }
        else
        {
            $io->writeln('Create cancelled by user action');
        }
    }
}
