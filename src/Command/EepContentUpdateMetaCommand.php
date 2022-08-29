<?php

namespace MugoWeb\Eep\Bundle\Command;

use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\ContentMetadataUpdateStruct;
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

class EepContentUpdateMetaCommand extends Command
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
<info>Usage</info>
-----
eep:content:updatemeta 43 '{ "publishedDate": "2022-08-29" }'
eep:content:updatemeta 43 '{ "publishedDate": "+3 weeks" }'
eep:content:updatemeta 43 '{ "publishedDate": "1660600234" }'

eep:content:updatemeta --from-file 43 './foobar.json'


<info>Content meta data update struct properties</info>
------------------------------------------
<info>ownerId [integer]</info>

<info>publishedDate [datetime]</info>
timestamp integer or string, or valid strtotime string is supported
e.g.
1660600234
"1660600234"
"+3 weeks"
"2022-08-29"

<info>modificationDate [datetime]</info>
timestamp integer or string, or valid strtotime string is supported
e.g.
1660600234
"1660600234"
"+3 weeks"
"2022-08-29"

<info>mainLanguageCode [string]</info>

<info>alwaysAvailable [boolean]</info>
boolean-like values are supported e.g. 0, 1

<info>remoteId [integer]</info>

<info>mainLocationId [integer]</info>

<info>name [string]</info>


<info>Meta data example (JSON)</info>
------------------------
{
    "ownerId": 14
    "publishedDate": 1060695457
    "modificationDate": 1060695457
    "mainLanguageCode": "eng-GB"
    "alwaysAvailable": 1
    "remoteId": "90c24177247eded85bcdf6687953294e"
    "mainLocationId": 43
    "name": "Media"
}

EOD;

        $this
            ->setName('eep:content:updatemeta')
            ->setAliases(array('eep:co:updatemeta'))
            ->setDescription('Update content meta data')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('meta-data', InputArgument::REQUIRED, 'Content meta data as JSON string')
            ->addOption('from-file', 'f', InputOption::VALUE_NONE, 'Meta data should be read from file. Treat meta data argument as file path')
            ->addOption('new-version', null, InputOption::VALUE_NONE, 'Create new content version')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputMetaData = ($input->getOption('from-file'))? file_get_contents($input->getArgument('meta-data')) : $input->getArgument('meta-data');
        $inputNewVersion = $input->getOption('new-version');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentInfo = $this->contentService->loadContentInfo($inputContentId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to update meta data for content "%s"?',
                    $contentInfo->name
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
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                // instantiate a content meta data update struct and set the properties
                $contentMetadataUpdateStruct = $this->contentService->newContentMetadataUpdateStruct();

                $metaData = json_decode($inputMetaData, true);
                foreach ($metaData as $propertyName => $propertyValue)
                {
                    switch ($propertyName)
                    {
                        case 'ownerId':
                        case 'remoteId':
                        case 'mainLocationId':
                        {
                            $propertyValue = (integer) $propertyValue;
                        }
                        break;

                        case 'publishedDate':
                        case 'modificationDate':
                        {
                            $propertyValue = (is_numeric($propertyValue))? $propertyValue : strtotime($propertyValue);
                            $date = date('c', $propertyValue);
                            $propertyValue = new \DateTime($date);
                        }
                        break;

                        case 'alwaysAvailable':
                        {
                            $propertyValue = (boolean) $propertyValue;
                        }
                        break;

                        default:
                        {
                            $propertyValue = (string) $propertyValue;
                        }
                    }
                    $contentMetadataUpdateStruct->{$propertyName} = $propertyValue;
                }

                if ($inputNewVersion)
                {
                    // update content meta data in new version
                    $draft = $this->contentService->createContentDraft($contentInfo);
                    $content = $this->contentService->publishVersion($draft->versionInfo);
                    $this->contentService->updateContentMetadata($content->contentInfo, $contentMetadataUpdateStruct);
                }
                else
                {
                    // update content meta data in current version
                    $content = $this->contentService->updateContentMetadata($contentInfo, $contentMetadataUpdateStruct);
                }

                $io->success('Update successful');
                $this->logger->info($this->getName() . " successful");
            }
            catch
            (
                InvalidArgumentException |
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
