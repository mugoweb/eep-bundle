<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\URLAliasService;
use eZ\Publish\API\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepUrlAliasRemoveCommand extends Command
{
    public function __construct
    (
        URLAliasService $urlAliasService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->urlAliasService = $urlAliasService;
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
            ->setName('eep:urlalias:remove')
            ->setAliases(array('eep:ua:remove'))
            ->setDescription('Removes URL aliases')
            ->addArgument('url-alias-ids', InputArgument::REQUIRED, 'URL alias id or CSV of URL alias ids')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for URL alias operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputUrlAliasIds = $input->getArgument('url-alias-ids');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $urlAliasIds = array_filter(array_map('trim', explode(',', $inputUrlAliasIds)));

        $urlAliases = array();
        foreach ($urlAliasIds as $urlAliasId)
        {
            $urlAliases[] = $this->urlAliasService->load($urlAliasId);
        }

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to remove %d URL alias(es)?',
                    count($urlAliases)
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputUrlAliasIds,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $this->urlAliasService->removeAliases($urlAliases);

                $io->success(sprintf('Remove successful. urlAliasIds: %s', implode(',', $urlAliasIds)));
                $this->logger->info($this->getName() . " successful", array($inputUrlAliasIds));
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
            $io->writeln('Remove cancelled by user action');
        }
    }
}