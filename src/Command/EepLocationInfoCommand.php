<?php

namespace Eep\Bundle\Command;

use Eep\Bundle\Component\Console\Helper\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


use eZ\Publish\Core\MVC\Symfony\Routing\RouteReference;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EepLocationInfoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:location:info')
            ->setAliases(array('eep:lo:info'))
            ->setDescription('Returns location information')
            ->addArgument('location-id', InputArgument::REQUIRED, 'Location id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('with-content-info', 'wci', InputOption::VALUE_NONE, 'Display location\'s content info')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputLocationId = $input->getArgument('location-id');
        $inputUserId = $input->getOption('user-id');
        $inputWithContentInfo = $input->getOption('with-content-info');

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($inputUserId));
        $locationService = $repository->getLocationService();
        $urlAliasService = $repository->getURLAliasService();
        if ($inputWithContentInfo)
        {
            $contentService = $repository->getContentService();
            $contentTypeService = $repository->getContentTypeService();
        }

        $location = $locationService->loadLocation($inputLocationId);

        if ($inputWithContentInfo)
        {
            $content = $contentService->loadContent($location->getContentInfo()->id);
        }

        $headers = array
        (
            array
            (
                'key',
                'value',
            ),
        );
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [{$location->id}]",
                array('colspan' => count($headers[0]))
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            /*
                eZ\Publish\Core\Repository\Values\Content\Location Object
                (
                    [contentInfo:protected] => eZ\Publish\API\Repository\Values\Content\ContentInfo Object
                    [path:protected] =>
                    [id:protected] => 43
                    [status] => 1
                    [priority:protected] => 0
                    [hidden:protected] =>
                    [invisible:protected] =>
                    [remoteId:protected] => 75c715a51699d2d309a924eca6a95145
                    [parentLocationId:protected] => 1
                    [pathString:protected] => /1/43/
                    [depth:protected] => 1
                    [sortField:protected] => 8
                    [sortOrder:protected] => 1
                    [content:protected] => eZ\Publish\Core\Repository\Values\Content\ContentProxy Object
                )
            */
            // location details
            array('id', $location->id),
            array('status', $location->status),
            array('priority', $location->priority),
            array('hidden', (integer) $location->hidden),
            array('invisible', (integer) $location->invisible),
            array('remoteId', $location->remoteId),
            array('parentLocationId', $location->parentLocationId),
            array('pathString', $location->pathString),
            array('depth', $location->depth),
            array('sortField', $location->sortField),
            array('sortOrder', $location->sortOrder),
            new TableSeparator(),
            array('childCount', $locationService->getLocationChildCount($location)),
        );
        if ($inputWithContentInfo)
        {
            $rows = array_merge
            (
                $rows,
                array
                (
                    new TableSeparator(),

                    /*
                        eZ\Publish\API\Repository\Values\Content\ContentInfo Object
                        (
                            [id:protected] => 626
                            [contentTypeId:protected] => 38
                            [name:protected] => Media
                            [sectionId:protected] => 3
                            [currentVersionNo:protected] => 1
                            [published:protected] => 1
                            [ownerId:protected] => 59
                            [modificationDate:protected] => DateTime Object
                                (
                                    [date] => 2018-11-08 02:16:23.000000
                                    [timezone_type] => 3
                                    [timezone] => America/Edmonton
                                )

                            [publishedDate:protected] => DateTime Object
                                (
                                    [date] => 2018-11-08 02:16:23.000000
                                    [timezone_type] => 3
                                    [timezone] => America/Edmonton
                                )

                            [alwaysAvailable:protected] => 0
                            [remoteId:protected] => 8faf6ac73303080f7c264d6e3af5ca66
                            [mainLanguageCode:protected] => eng-CA
                            [mainLocationId:protected] => 43
                            [status:protected] => 1
                        )
                    */
                    // location contentInfo details
                    array('contentId', $location->getContentInfo()->id),
                    array('contentTypeId', $location->getContentInfo()->contentTypeId),
                    array('contentTypeIdentifier', $contentTypeService->loadContentType($location->getContentInfo()->contentTypeId)->identifier),
                    array('contentName', $location->getContentInfo()->name),
                    array('contentSectionId', $location->getContentInfo()->sectionId),
                    array('contentCurrentVersionNo', $location->getContentInfo()->currentVersionNo),
                    array('contentPublished', $location->getContentInfo()->published),
                    array('contentOwnerId', $location->getContentInfo()->ownerId),
                    array('contentModificationDate', $location->getContentInfo()->modificationDate->format('c')),
                    array('contentModificationDateTimestamp', $location->getContentInfo()->modificationDate->format('U')),
                    array('contentPublishedDate', $location->getContentInfo()->publishedDate->format('c')),
                    array('contentPublishedDateTimestamp', $location->getContentInfo()->publishedDate->format('U')),
                    array('contentAlwaysAvailable', $location->getContentInfo()->alwaysAvailable),
                    array('contentRemoteId', $location->getContentInfo()->remoteId),
                    array('contentMainLanguageCode', $location->getContentInfo()->mainLanguageCode),
                    array('contentMainLocationId', $location->getContentInfo()->mainLocationId),
                    array('contentStatus', $location->getContentInfo()->status),
                    // reverse related count?
                )
            );
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
