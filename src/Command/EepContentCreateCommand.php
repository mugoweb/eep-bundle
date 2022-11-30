<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use MugoWeb\Eep\Bundle\Services\EepLogger;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
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

class EepContentCreateCommand extends Command
{
    public function __construct
    (
        LocationService $locationService,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->locationService = $locationService;
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
<info>Usage</info>
-----
eep:content:create folder 43 '{ "name": "Foobar" }' eng-GB

eep:content:create --from-file folder 43 './foobar.json' eng-GB


<info>Content FieldType => Input format map</info>
-------------------------------------
<info>Text line [ezstring]</info>
A string e.g. 'foobar'

<info>Text block [eztext]</info>
A string e.g. 'foobar'

<info>Rich text [ezrichtext]</info>
A docbook XML string
e.g.
'<section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ezxhtml="http://ez.no/xmlns/ezpublish/docbook/xhtml" xmlns:ezcustom="http://ez.no/xmlns/ezpublish/docbook/custom" version="5.0-variant ezpublish-1.0"><para>Lorem <emphasis role="strong">ipsum</emphasis> dolor sit amet.</para></section>'

<info>Image [ezimage]</info>
An image path as a simple string e.g. 'images/foobar.jpg'

<info>File [ezbinaryfile]</info>
A file path as a simple string e.g. 'images/foobar.txt'

<info>Date and time [ezdatetime]</info>
A timestamp integer or PHP DateTime compatible string
e.g.
1661947200
'Wednesday, 31-Aug-22 12:00:00 GMT+0000'

<info>Date [ezdate]</info>
The date value as a timestamp integer or PHP DateTime compatible string
e.g.
1661904000
'Wednesday, 31-Aug-22 00:00:00 GMT+0000'

<info>Checkbox [ezboolean]</info>
A true/false integer e.g. 0 or 1

<info>Content relation (single) [ezobjectrelation]</info>
A content id integer e.g. 41

<info>Content relations (multiple) [ezobjectrelationlist]</info>
An array of content ids e.g. [1,41]


<info>Field data example (JSON)</info>
-------------------------
eep_test_content content type data with all fields described above in order

{
    "title": "Text line field content",
    "description_simple": "Text block content",
    "description_rich": "<section xmlns=\"http:\/\/docbook.org\/ns\/docbook\" xmlns:xlink=\"http:\/\/www.w3.org\/1999\/xlink\" xmlns:ezxhtml=\"http:\/\/ez.no\/xmlns\/ezpublish\/docbook\/xhtml\" xmlns:ezcustom=\"http:\/\/ez.no\/xmlns\/ezpublish\/docbook\/custom\" version=\"5.0-variant ezpublish-1.0\"><para>Lorem <emphasis role=\"strong\">Rich<\/emphasis> text content.<\/para><\/section>",
    "image": "./eep_test.jpg",
    "file": "./eep_test.txt",
    "date_time": 1661947200,
    "date": 1661904000,
    "checkbox": 1,
    "relation_single": 41,
    "relation_multi": [1,41]
}

EOD;

        $this
            ->setName('eep:content:create')
            ->setAliases(array('eep:co:create'))
            ->setDescription('Create content at location')
            ->addArgument('content-type-identifier', InputArgument::REQUIRED, 'Content type identifier')
            ->addArgument('parent-location-id', InputArgument::REQUIRED, 'Parent location id')
            ->addArgument('field-data', InputArgument::REQUIRED, 'Content field data as JSON string')
            ->addArgument('main-language-code', InputArgument::OPTIONAL, 'Main language code', null)
            ->addOption('from-file', 'f', InputOption::VALUE_NONE, 'Field data should be read from file. Treat field data argument as file path')
            ->addOption('result-format', 'r', InputOption::VALUE_OPTIONAL, 'Result display format. One of: default, table, minimal', 'default')
            ->addOption('no-newline', 'x', InputOption::VALUE_NONE, 'Result display without trailing newline. Only applies when --result-format=minimal')
            ->addOption('no-publish', null, InputOption::VALUE_NONE, 'Create content as draft')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentTypeIdentifier = $input->getArgument('content-type-identifier');
        $inputParentLocationId = $input->getArgument('parent-location-id');
        $inputFieldData = ($input->getOption('from-file'))? file_get_contents($input->getArgument('field-data')) : $input->getArgument('field-data');
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
                    'Are you sure you want to create content%sat "%s"?',
                    ($input->getOption('no-publish'))? ' (draft) ' : ' ',
                    $location->contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputContentTypeIdentifier,
                $inputParentLocationId,
                $input->getArgument('field-data'),
                $inputMainLanguageCode,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $contentType = $this->contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);
                $inputMainLanguageCode = (!$inputMainLanguageCode)? $contentType->mainLanguageCode : $inputMainLanguageCode;
                $contentCreateStruct = $this->contentService->newContentCreateStruct($contentType, $inputMainLanguageCode);
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
                    $contentCreateStruct->setField($fieldIdentifier, $fieldValue);
                }

                // instantiate a location create struct from the parent location
                $locationCreateStruct = $this->locationService->newLocationCreateStruct($inputParentLocationId);

                // create a draft using the content and location create struct and publish it
                $draft = $this->contentService->createContent($contentCreateStruct, array($locationCreateStruct));
                $content = $draft;
                if(!$input->getOption('no-publish'))
                {
                    $content = $this->contentService->publishVersion($draft->versionInfo);
                }

                switch ($input->getOption('result-format'))
                {
                    case 'table':
                    {
                        $rows = array
                        (
                            array
                            (
                                $content->id,
                                $content->contentInfo->mainLocationId
                            )
                        );
                        $headers = array
                        (
                            new TableCell
                            (
                                "{$this->getName()} [$inputContentTypeIdentifier $inputParentLocationId {$input->getArgument('field-data')} $inputMainLanguageCode]",
                                array('colspan' => count($rows[0]))
                            )
                        );

                        $table = new Table($output);
                        $table->setHeaders($headers);
                        $table->setRows($rows);
                        $table->render();
                    }
                    break;

                    case 'minimal':
                    {
                        if ($input->getOption('no-newline'))
                        {
                            $io->write("{$content->id} {$content->contentInfo->mainLocationId}");
                        }
                        else
                        {
                            $io->writeln("{$content->id} {$content->contentInfo->mainLocationId}");
                        }
                    }
                    break;

                    default:
                    {
                        $io->success("Create successful. contentId: {$content->id} mainLocationId: {$content->contentInfo->mainLocationId}");
                    }
                    break;
                }

                $this->logger->info($this->getName() . " successful", array($content->id, $content->contentInfo->mainLocationId));
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
        else
        {
            $io->writeln('Create cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
