<?php

namespace MugoWeb\Eep\Bundle\Command;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentCreateCommand extends Command
{
    public function __construct
    (
        LocationService $locationService,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService
    )
    {
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;

        parent::__construct();
    }

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
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
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

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $location = $this->locationService->loadLocation($inputParentLocationId);

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
                $contentType = $this->contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);
                $contentCreateStruct = $this->contentService->newContentCreateStruct($contentType, $inputMainLanguageCode);

                // TODO: only basic field handling
                // { "name": "Foo", "description": "Bar" }
                $fieldData = json_decode($inputFieldData, true);
                foreach($fieldData as $fieldIdentifier => $fieldValue)
                {
                    $contentCreateStruct->setField($fieldIdentifier, $fieldValue);
                }

                // instantiate a location create struct from the parent location
                $locationCreateStruct = $this->locationService->newLocationCreateStruct($inputParentLocationId);

                // create a draft using the content and location create struct and publish it
                $draft = $this->contentService->createContent($contentCreateStruct, array($locationCreateStruct));
                $content = $this->contentService->publishVersion($draft->versionInfo);
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
