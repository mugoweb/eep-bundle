<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepUrlAliasCreateCommand extends Command
{
    public function __construct
    (
        private readonly URLAliasService $urlAliasService,
        private readonly LocationService $locationService,
        private readonly PermissionResolver $permissionResolver,
        private readonly UserService $userService,
        private readonly EepLogger $logger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $help = <<<EOD
<info>Usage</info>
-----
eep:urlalias:create 43 '/my-alias' eng-GB

eep:urlalias:create 43 '/my-alias' eng-GB --forwarding --always-available

EOD;

        $this
            ->setName('eep:urlalias:create')
            ->setAliases(array('eep:ua:create'))
            ->setDescription('Creates a custom URL alias for a location')
            ->addArgument('location-id', InputArgument::REQUIRED, 'Location id')
            ->addArgument('path', InputArgument::REQUIRED, 'URL alias path')
            ->addArgument('language-code', InputArgument::REQUIRED, 'Language code')
            ->addOption('forwarding', 'f', InputOption::VALUE_NONE, 'Perform redirect from the alias')
            ->addOption('always-available', 'a', InputOption::VALUE_NONE, 'Make alias available in all languages')
            ->addOption('result-format', 'r', InputOption::VALUE_OPTIONAL, 'Result display format. One of: default, table, minimal', 'default')
            ->addOption('no-newline', 'x', InputOption::VALUE_NONE, 'Result display without trailing newline. Only applies when --result-format=minimal')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for URL alias operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputLocationId = $input->getArgument('location-id');
        $inputPath = $input->getArgument('path');
        $inputLanguageCode = $input->getArgument('language-code');
        $inputForwarding = $input->getOption('forwarding');
        $inputAlwaysAvailable = $input->getOption('always-available');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $location = $this->locationService->loadLocation($inputLocationId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to create URL alias "%s" at "%s"?',
                    $inputPath,
                    $location->contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputLocationId,
                $inputPath,
                $inputLanguageCode,
                (integer) $inputForwarding,
                (integer) $inputAlwaysAvailable,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $urlAlias = $this->urlAliasService->createUrlAlias
                (
                    $location,
                    $inputPath,
                    $inputLanguageCode,
                    $inputForwarding,
                    $inputAlwaysAvailable
                );

                switch ($input->getOption('result-format'))
                {
                    case 'table':
                        {
                            $rows = array
                            (
                                array
                                (
                                    $urlAlias->id,
                                    $urlAlias->type,
                                    $urlAlias->destination,
                                    $urlAlias->path,
                                    implode(',', $urlAlias->languageCodes),
                                    (integer) $urlAlias->alwaysAvailable,
                                    (integer) $urlAlias->isHistory,
                                    (integer) $urlAlias->isCustom,
                                    (integer) $urlAlias->forward,
                                )
                            );
                            $headers = array
                            (
                                array
                                (
                                    'id',
                                    'type',
                                    'destination',
                                    'path',
                                    'languageCodes',
                                    'alwaysAvailable',
                                    'isHistory',
                                    'isCustom',
                                    'forward',
                                )
                            );
                            $infoHeader = array
                            (
                                new TableCell
                                (
                                    "{$this->getName()} [$inputLocationId $inputPath $inputLanguageCode]",
                                    array('colspan' => count($headers[0]))
                                )
                            );
                            array_unshift($headers, $infoHeader);

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
                                $io->write("{$urlAlias->id} {$urlAlias->path}");
                            }
                            else
                            {
                                $io->writeln("{$urlAlias->id} {$urlAlias->path}");
                            }
                        }
                        break;

                    default:
                    {
                        $io->success("Create successful. urlAliasId: {$urlAlias->id} path: {$urlAlias->path}");
                    }
                }

                $this->logger->info($this->getName() . " successful", array($urlAlias->id, $urlAlias->path));
            }
            catch
            (
                Exceptions\InvalidArgumentException |
                Exceptions\UnauthorizedException |
                Exceptions\NotFoundException
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
