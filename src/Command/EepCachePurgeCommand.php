<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use EzSystems\PlatformHttpCacheBundle\PurgeClient\RepositoryPrefixDecorator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepCachePurgeCommand extends Command
{
    public function __construct
    (
        RepositoryPrefixDecorator $purgeClient,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->purgeClient = $purgeClient;
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
            ->setName('eep:cache:purge')
            ->setAliases(array('eep:ca:purge'))
            ->setDescription('Purge cache by tag(s)')
            ->addArgument('tags', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Tag(s) (see usage)')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputTags = $input->getArgument('tags');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $tagString = implode(' ', $inputTags);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to purge cache for tags "%s"?',
                    $tagString
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputTags,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $this->purgeClient->purge($inputTags);
                $io->success('Purged: ' . $tagString);
                $this->logger->info($this->getName() . " successful");
            }
            catch (Exception $e)
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", $e->getMessage());
            }
        }
        else
        {
            $io->writeln('Purge cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
