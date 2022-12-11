<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentListFieldsCommand extends Command
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
            ->setName('eep:content:listfields')
            ->setAliases(array('eep:co:listfields'))
            ->setDescription('Returns content field list')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('full-value', 'f', InputOption::VALUE_NONE, 'Show full field value')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);
        $fields = $content->getFields();
        $truncateLength = 80;

        $headers = array
        (
            array
            (
               'id',
               'fieldDefIdentifier',
               'languageCode',
               'fieldTypeIdentifier',
                'value',
            )
        );

        $hideColumns = ($input->getOption('hide-columns'))? explode(',', $input->getOption('hide-columns')) : array();
        $headerKeys = array_map(array('MugoWeb\Eep\Bundle\Services\EepUtilities', 'stripColumnMarkers'), $headers[0]);
        foreach($hideColumns as $columnKey)
        {
            $searchResultKey = array_search($columnKey, $headerKeys);
            if($searchResultKey !== false)
            {
                unset($headers[0][$searchResultKey]);
            }
        }

        $colWidth = count($headers[0]);
        if (!$input->getOption('full-value'))
        {
            $legendHeaders = array
            (
                new TableCell("m = modified output (EOL chars stripped, truncated - showing first/last " . $truncateLength/2 . ")", array('colspan' => $colWidth))
            );
            $legendHeaders = array_reverse($legendHeaders);
            foreach ($legendHeaders as $row)
            {
                array_unshift($headers, array($row));
            }
        }
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentId]",
                array('colspan' => ($colWidth == 1)? 1 : $colWidth)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($fields as $field)
        {
            $fieldValue = $field->value;
            if (!$input->getOption('full-value'))
            {
                $fieldValue = str_replace(PHP_EOL, '', $field->value);
                // styles, see Symfony\Component\Console\Formatter\OutputFormatterStyle
                $fieldValue = (strlen($fieldValue) > $truncateLength)? "<fg=black;bg=yellow>[m]</> "  . substr($fieldValue, 0, $truncateLength/2) . ' ... ' . substr($fieldValue, -($truncateLength/2), $truncateLength/2) : $fieldValue;
            }

            $row = array();
            if(!in_array('id', $hideColumns)) { $row[] = $field->id; }
            if(!in_array('fieldDefIdentifier', $hideColumns)) { $row[] = $field->fieldDefIdentifier; }
            if(!in_array('languageCode', $hideColumns)) { $row[] = $field->languageCode; }
            if(!in_array('fieldTypeIdentifier', $hideColumns)) { $row[] = $field->fieldTypeIdentifier; }
            if(!in_array('value', $hideColumns)) { $row[] = $fieldValue; }

            $rows[] = $row;
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();

        return Command::SUCCESS;
    }
}
