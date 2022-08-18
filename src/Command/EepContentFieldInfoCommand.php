<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentFieldInfoCommand extends Command
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
            ->setName('eep:contentfield:info')
            ->setAliases(array('eep:cf:info'))
            ->setDescription('Returns content field information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('content-field-identifier', InputArgument::REQUIRED, 'Content field identifier')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputContentFieldIdentifier = $input->getArgument('content-field-identifier');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentField = $this->contentService->loadContent($inputContentId)->getField($inputContentFieldIdentifier);

        $headers = array
        (
            array
            (
                'key/innerKey(s)',
                'value',
            )
        );
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentId,$inputContentFieldIdentifier]",
                array('colspan' => count($headers[0]))
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $contentField->id),
            array('fieldDefIdentifier', $contentField->fieldDefIdentifier),
            array('languageCode', $contentField->languageCode),
            array('fieldTypeIdentifier', $contentField->fieldTypeIdentifier),
            new TableSeparator(),
        );
        foreach ($contentField->value as $key => $value)
        {
            $key = "value/{$key}";
            if (is_array($value) || is_object($value))
            {
                if (!$value)
                {
                    $rows[] = array($key, '');
                    continue;
                }

                foreach ((array)$value as $keyInner => $valueInner)
                {
                    $rows[] = array("{$key}/{$keyInner}", $valueInner);
                }
            }
            else
            {
                $rows[] = array($key, $value);
            }
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
