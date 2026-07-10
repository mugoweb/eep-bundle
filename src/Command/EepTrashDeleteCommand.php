<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use Ibexa\Contracts\Core\Repository\TrashService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepTrashDeleteCommand extends Command
{
    public function __construct
    (
        private readonly TrashService $trashService,
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
TODO

EOD;

        $this
            ->setName('eep:trash:delete')
            ->setAliases(array('eep:tr:delete'))
            ->setDescription('Deletes a trashed item')
            ->addArgument('trash-item-id', InputArgument::REQUIRED, 'Trash item id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputTrashItemId = $input->getArgument('trash-item-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $trashItem = $this->trashService->loadTrashItem($inputTrashItemId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to delete "%s"?',
                    $trashItem->contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputTrashItemId,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $this->trashService->deleteTrashItem($trashItem);

                $io->success('Delete successful');
                $this->logger->info($this->getName() . " successful");
            }
            catch(\Exception $e)
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Delete cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
