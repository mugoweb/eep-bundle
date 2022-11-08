<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\RoleService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use MugoWeb\Eep\Bundle\Services\EepUtilities;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepUserInfoCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        RoleService $roleService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentService = $contentService;
        $this->roleService = $roleService;
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
            ->setName('eep:user:info')
            ->setAliases(array('eep:us:info'))
            ->setDescription('Returns user information')
            ->addArgument('user-id', InputArgument::REQUIRED, 'User id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputArgumentUserId = $input->getArgument('user-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $user = $this->userService->loadUser($inputArgumentUserId);

        $headers = array
        (
            array
            (
                'key',
                'value',
            ),
        );
        $colWidth = count($headers[0]);
        $legendHeaders = array
        (
            new TableCell("# 2nd data section shows custom/composite/lookup values", array('colspan' => $colWidth)),
            // ...
        );
        $legendHeaders = array_reverse($legendHeaders);
        foreach ($legendHeaders as $row)
        {
            array_unshift($headers, array($row));
        }
        $infoHeader = array
        (
            new TableCell("{$this->getName()} [$inputArgumentUserId]", array('colspan' => $colWidth))
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $user->id),
            array('login', $user->login),
            array('email', $user->email),
            array('passwordHash', $user->passwordHash),
            array('hashAlgorithm', $user->hashAlgorithm),
            array('enabled', (integer) $user->enabled),
            array('passwordUpdatedAt', ($user->passwordUpdatedAt)? $user->passwordUpdatedAt->format('c') : ''),
            array('maxLogin', $user->maxLogin),
            new TableSeparator(),
            array('name', $user->getName()),
            array('hashLabel', EepUtilities::getUserHashAlgorithmLabel($user->hashAlgorithm)),
            array('passwordUpdatedAtTimestamp', ($user->passwordUpdatedAt)? $user->passwordUpdatedAt->format('U') : ''),
            array('userGroupIds', implode( ',', array_column($this->userService->loadUserGroupsOfUser($user),'id'))),
            array('roleAssignmentIds', implode( ',', array_column($this->roleService->getRoleAssignmentsForUser($user),'id'))),
        );

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
