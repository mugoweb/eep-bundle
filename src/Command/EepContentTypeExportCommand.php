<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentTypeExportCommand extends Command
{
    private const CONTENT_TYPE_PROPERTIES = array
    (
        'remoteId',
        'urlAliasSchema',
        'nameSchema',
        'isContainer',
        'defaultSortField',
        'defaultSortOrder',
        'defaultAlwaysAvailable',
    );

    private const FIELD_DEFINITION_PROPERTIES = array
    (
        'fieldGroup',
        'position',
        'isTranslatable',
        'isRequired',
        'isInfoCollector',
        'validatorConfiguration',
        'fieldSettings',
        'isSearchable',
    );

    public function __construct
    (
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
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
eep:contenttype:export article

eep:contenttype:export article --to-file ./article.json


Exports a content type's definition as JSON, in the same format accepted by eep:contenttype:create.

EOD;

        $this
            ->setName('eep:contenttype:export')
            ->setAliases(array('eep:ct:export'))
            ->setDescription('Export a content type definition to JSON')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Content type identifier')
            ->addOption('to-file', null, InputOption::VALUE_OPTIONAL, 'Write JSON to file instead of stdout')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputIdentifier = $input->getArgument('identifier');
        $inputUserId = $input->getOption('user-id');
        $inputToFile = $input->getOption('to-file');

        $io = new SymfonyStyle($input, $output);

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        try
        {
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier($inputIdentifier);

            $definition = $this->exportContentType($contentType);

            $json = json_encode($definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($inputToFile)
            {
                file_put_contents($inputToFile, $json . "\n");
                $io->success("Exported content type \"{$inputIdentifier}\" to {$inputToFile}");
            }
            else
            {
                $output->writeln($json);
            }

            $this->logger->info($this->getName() . " successful", array($inputIdentifier));
        }
        catch (\Exception $e)
        {
            $io->error($e->getMessage());
            $this->logger->error($this->getName() . " error", array($e->getMessage()));
        }
    }

    private function exportContentType(ContentType $contentType): array
    {
        $definition = array
        (
            'mainLanguageCode' => $contentType->mainLanguageCode,
            'names' => $this->filterTranslations($contentType->getNames()),
        );

        $descriptions = $this->filterTranslations($contentType->getDescriptions());
        if (!empty($descriptions))
        {
            $definition['descriptions'] = $descriptions;
        }

        $this->exportProperties($contentType, $definition, self::CONTENT_TYPE_PROPERTIES);

        $definition['fields'] = array();
        foreach ($contentType->getFieldDefinitions() as $fieldDefinition)
        {
            $definition['fields'][] = $this->exportFieldDefinition($fieldDefinition);
        }

        return $definition;
    }

    private function exportFieldDefinition(FieldDefinition $fieldDefinition): array
    {
        $field = array
        (
            'identifier' => $fieldDefinition->identifier,
            'fieldTypeIdentifier' => $fieldDefinition->fieldTypeIdentifier,
            'names' => $this->filterTranslations($fieldDefinition->getNames()),
        );

        $descriptions = $this->filterTranslations($fieldDefinition->getDescriptions());
        if (!empty($descriptions))
        {
            $field['descriptions'] = $descriptions;
        }

        $this->exportProperties($fieldDefinition, $field, self::FIELD_DEFINITION_PROPERTIES);

        if ($fieldDefinition->defaultValue !== null && (is_scalar($fieldDefinition->defaultValue) || is_array($fieldDefinition->defaultValue)))
        {
            $field['defaultValue'] = $fieldDefinition->defaultValue;
        }

        return $field;
    }

    /**
     * Removes language entries with a null value from a translated hash (e.g. "names",
     * "descriptions"), since the API rejects null where a string is expected on import.
     */
    private function filterTranslations(array $translations): array
    {
        return array_filter($translations, function ($value)
        {
            return $value !== null;
        });
    }

    /**
     * Copies the given property names from $object onto $target where the value is not empty,
     * skipping anything that isn't JSON-safe (scalar, array, or null).
     */
    private function exportProperties($object, array &$target, array $properties)
    {
        foreach ($properties as $property)
        {
            $value = $object->$property;

            if ($value === null || $value === array())
            {
                continue;
            }

            if (!is_scalar($value) && !is_array($value))
            {
                continue;
            }

            $target[$property] = $value;
        }
    }
}