<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions\ContentTypeFieldDefinitionValidationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentTypeCreateCommand extends Command
{
    private const CONTENT_TYPE_PROPERTY_CASTS = array
    (
        'remoteId' => null,
        'urlAliasSchema' => null,
        'nameSchema' => null,
        'isContainer' => 'boolval',
        'defaultSortField' => 'intval',
        'defaultSortOrder' => 'intval',
        'defaultAlwaysAvailable' => 'boolval',
    );

    private const FIELD_DEFINITION_PROPERTY_CASTS = array
    (
        'fieldGroup' => null,
        'position' => 'intval',
        'isTranslatable' => 'boolval',
        'isRequired' => 'boolval',
        'isThumbnail' => 'boolval',
        'isInfoCollector' => 'boolval',
        'fieldSettings' => null,
        'validatorConfiguration' => null,
        'isSearchable' => 'boolval',
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
eep:contenttype:create 1 article '{ "mainLanguageCode": "eng-GB", "names": {"eng-GB": "Article"}, "fields": [...] }'

eep:contenttype:create --from-file 1 article './article.json'


<info>Definition format (JSON)</info>
-------------------------
{
    "mainLanguageCode": "eng-GB",
    "names": {
        "eng-GB": "Example content type"
    },
    "descriptions": {
        "eng-GB": "Example content type covering some core field types"
    },
    "nameSchema": "<title>",
    "urlAliasSchema": "<title>",
    "isContainer": true,
    "defaultAlwaysAvailable": true,
    "defaultSortField": 2,
    "defaultSortOrder": 0,
    "fields": [
        {
            "identifier": "title",
            "fieldTypeIdentifier": "ezstring",
            "names": {
                "eng-GB": "Title"
            },
            "descriptions": {
                "eng-GB": "Short text line"
            },
            "fieldGroup": "content",
            "position": 10,
            "isTranslatable": true,
            "isRequired": true,
            "isSearchable": true,
            "validatorConfiguration": {
                "StringLengthValidator": {
                    "minStringLength": 0,
                    "maxStringLength": 255
                }
            }
        },
        {
            "identifier": "summary",
            "fieldTypeIdentifier": "eztext",
            "names": {
                "eng-GB": "Summary"
            },
            "descriptions": {
                "eng-GB": "Plain text block"
            },
            "fieldGroup": "content",
            "position": 20,
            "isTranslatable": true,
            "isRequired": false,
            "isSearchable": true,
            "fieldSettings": {
                "textRows": 10
            }
        },
        {
            "identifier": "body",
            "fieldTypeIdentifier": "ezrichtext",
            "names": {
                "eng-GB": "Body"
            },
            "descriptions": {
                "eng-GB": "Rich text content"
            },
            "fieldGroup": "content",
            "position": 30,
            "isTranslatable": true,
            "isRequired": false,
            "isSearchable": true
        },
        {
            "identifier": "related_item",
            "fieldTypeIdentifier": "ezobjectrelation",
            "names": {
                "eng-GB": "Related item"
            },
            "descriptions": {
                "eng-GB": "Single content relation"
            },
            "fieldGroup": "content",
            "position": 40,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "fieldSettings": {
                "selectionMethod": 0,
                "selectionRoot": null,
                "selectionContentTypes": []
            }
        },
        {
            "identifier": "related_items",
            "fieldTypeIdentifier": "ezobjectrelationlist",
            "names": {
                "eng-GB": "Related items"
            },
            "descriptions": {
                "eng-GB": "Multiple content relations"
            },
            "fieldGroup": "content",
            "position": 50,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "fieldSettings": {
                "selectionMethod": 0,
                "selectionDefaultLocation": null,
                "selectionContentTypes": []
            },
            "validatorConfiguration": {
                "RelationListValueValidator": {
                    "selectionLimit": 0
                }
            }
        },
        {
            "identifier": "category",
            "fieldTypeIdentifier": "ezselection",
            "names": {
                "eng-GB": "Category"
            },
            "descriptions": {
                "eng-GB": "Single/multiple choice selection"
            },
            "fieldGroup": "content",
            "position": 60,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "fieldSettings": {
                "isMultiple": false,
                "options": {
                    "0": "Option 1",
                    "1": "Option 2",
                    "2": "Option 3"
                }
            }
        },
        {
            "identifier": "is_featured",
            "fieldTypeIdentifier": "ezboolean",
            "names": {
                "eng-GB": "Is featured"
            },
            "descriptions": {
                "eng-GB": "Checkbox flag"
            },
            "fieldGroup": "content",
            "position": 70,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "defaultValue": false
        },
        {
            "identifier": "rating",
            "fieldTypeIdentifier": "ezfloat",
            "names": {
                "eng-GB": "Rating"
            },
            "descriptions": {
                "eng-GB": "Decimal number"
            },
            "fieldGroup": "content",
            "position": 80,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "validatorConfiguration": {
                "FloatValueValidator": {
                    "minFloatValue": 0,
                    "maxFloatValue": 5
                }
            }
        },
        {
            "identifier": "quantity",
            "fieldTypeIdentifier": "ezinteger",
            "names": {
                "eng-GB": "Quantity"
            },
            "descriptions": {
                "eng-GB": "Whole number"
            },
            "fieldGroup": "content",
            "position": 90,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "validatorConfiguration": {
                "IntegerValueValidator": {
                    "minIntegerValue": 0,
                    "maxIntegerValue": null
                }
            }
        },
        {
            "identifier": "image",
            "fieldTypeIdentifier": "ezimage",
            "names": {
                "eng-GB": "Image"
            },
            "descriptions": {
                "eng-GB": "Image upload"
            },
            "fieldGroup": "content",
            "position": 100,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": false,
            "validatorConfiguration": {
                "FileSizeValidator": {
                    "maxFileSize": 5
                }
            }
        },
        {
            "identifier": "attachment",
            "fieldTypeIdentifier": "ezbinaryfile",
            "names": {
                "eng-GB": "Attachment"
            },
            "descriptions": {
                "eng-GB": "File upload"
            },
            "fieldGroup": "content",
            "position": 110,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": false,
            "validatorConfiguration": {
                "FileSizeValidator": {
                    "maxFileSize": 10
                }
            }
        },
        {
            "identifier": "published_at",
            "fieldTypeIdentifier": "ezdatetime",
            "names": {
                "eng-GB": "Published at"
            },
            "descriptions": {
                "eng-GB": "Date and time"
            },
            "fieldGroup": "content",
            "position": 120,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "fieldSettings": {
                "useSeconds": false,
                "defaultType": 0
            }
        },
        {
            "identifier": "event_date",
            "fieldTypeIdentifier": "ezdate",
            "names": {
                "eng-GB": "Event date"
            },
            "descriptions": {
                "eng-GB": "Date only"
            },
            "fieldGroup": "content",
            "position": 130,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "fieldSettings": {
                "defaultType": 0
            }
        },
        {
            "identifier": "opening_time",
            "fieldTypeIdentifier": "eztime",
            "names": {
                "eng-GB": "Opening time"
            },
            "descriptions": {
                "eng-GB": "Time only"
            },
            "fieldGroup": "content",
            "position": 140,
            "isTranslatable": false,
            "isRequired": false,
            "isSearchable": true,
            "fieldSettings": {
                "useSeconds": false,
                "defaultType": 0
            }
        }
    ]
}

Only "mainLanguageCode", "names" and "fields" are required. Each field entry
requires "identifier" and "fieldTypeIdentifier"; all other field keys are
optional and fall back to the API defaults if omitted.

"defaultSortField" / "defaultSortOrder" take the raw integer constant values
from eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_* / SORT_ORDER_*.

<info>Field type reference [fieldTypeIdentifier] => fieldSettings / validatorConfiguration</info>
-----------------------------------------------------------------------------------
Only keys relevant to a given type need to be set; anything omitted falls back
to the field type's own defaults. "-" means the type has no settings of that
kind.

<info>Text line [ezstring]</info>
validatorConfiguration: {"StringLengthValidator": {"minStringLength": 0, "maxStringLength": 0}}

<info>Text block [eztext]</info>
fieldSettings: {"textRows": 10}

<info>Rich text [ezrichtext]</info>
fieldSettings / validatorConfiguration: -

<info>Image [ezimage]</info>
fieldSettings: {"mimeTypes": ["image/jpeg", "image/png"]}
validatorConfiguration: {"FileSizeValidator": {"maxFileSize": 5}, "AlternativeTextValidator": {"required": false}}

<info>File [ezbinaryfile]</info>
validatorConfiguration: {"FileSizeValidator": {"maxFileSize": 10}}

<info>Date and time [ezdatetime]</info>
fieldSettings: {"useSeconds": false, "defaultType": 0}
(defaultType: 0 = empty, 1 = current date, 2 = current date adjusted by "dateInterval")

<info>Date [ezdate]</info>
fieldSettings: {"defaultType": 0}
(defaultType: 0 = empty, 1 = current date)

<info>Time [eztime]</info>
fieldSettings: {"useSeconds": false, "defaultType": 0}
(defaultType: 0 = empty, 1 = current time)

<info>Checkbox [ezboolean]</info>
fieldSettings / validatorConfiguration: -
defaultValue: false

<info>Integer [ezinteger]</info>
validatorConfiguration: {"IntegerValueValidator": {"minIntegerValue": null, "maxIntegerValue": null}}

<info>Float [ezfloat]</info>
validatorConfiguration: {"FloatValueValidator": {"minFloatValue": null, "maxFloatValue": null}}

<info>Keyword [ezkeyword]</info>
fieldSettings / validatorConfiguration: -
defaultValue: ["keyword1", "keyword2"]

<info>Selection [ezselection]</info>
fieldSettings: {"isMultiple": false, "options": {"0": "Option 1", "1": "Option 2"}, "multilingualOptions": { "fre-CA": {"0": "Option 1", "1": "Option 2"}}}

<info>Matrix [ezmatrix]</info>
fieldSettings: {"minimum_rows": 1, "columns": [{"identifier": "col1", "name": "Column 1"}]}

<info>Content relation (single) [ezobjectrelation]</info>
fieldSettings: {"selectionMethod": 0, "selectionRoot": null, "rootDefaultLocation": false, "selectionContentTypes": []}
(selectionMethod: 0 = browse, 1 = dropdown, 2 = list with radio buttons)

<info>Content relations (multiple) [ezobjectrelationlist]</info>
fieldSettings: {"selectionMethod": 0, "selectionDefaultLocation": null, "rootDefaultLocation": false, "selectionContentTypes": []}
validatorConfiguration: {"RelationListValueValidator": {"selectionLimit": 0}}

<info>User [ezuser]</info>
fieldSettings: {"PasswordTTL": 30, "PasswordTTLWarning": 7, "RequireUniqueEmail": true, "UsernamePattern": "^[a-zA-Z0-9_-]+$"  }
validatorConfiguration: {"PasswordValueValidator": {"requireAtLeastOneUpperCaseCharacter": 1, "requireAtLeastOneLowerCaseCharacter": 1, "requireAtLeastOneNumericCharacter": 1, "requireAtLeastOneNonAlphanumericCharacter": null, "requireNewPassword": 1, "requireNotCompromisedPassword", false, "minLength": 10}}
Only one ezuser field is allowed per content type.

For all other types check the field type's \$settingsSchema/\$validatorConfigurationSchema in vendor/ezsystems/ezplatform-kernel/eZ/Publish/Core/FieldType before using it.
Address [ezaddress], Form [ezform] and Measurement [ezmeasurement] are available only if the corresponding bundle is installed; 

EOD;

        $this
            ->setName('eep:contenttype:create')
            ->setAliases(array('eep:ct:create'))
            ->setDescription('Create a content type in a content type group')
            ->addArgument('group-id', InputArgument::REQUIRED, 'Content type group id')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Content type identifier')
            ->addArgument('definition', InputArgument::REQUIRED, 'Content type definition as JSON string')
            ->addOption('from-file', 'f', InputOption::VALUE_NONE, 'Definition should be read from file. Treat definition argument as file path')
            ->addOption('main-language-code', null, InputOption::VALUE_OPTIONAL, 'Main language code, used if not present in the definition', 'eng-GB')
            ->addOption('result-format', 'r', InputOption::VALUE_OPTIONAL, 'Result display format. One of: default, table, minimal', 'default')
            ->addOption('no-newline', 'x', InputOption::VALUE_NONE, 'Result display without trailing newline. Only applies when --result-format=minimal')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputGroupId = $input->getArgument('group-id');
        $inputIdentifier = $input->getArgument('identifier');
        $inputDefinition = ($input->getOption('from-file'))? file_get_contents($input->getArgument('definition')) : $input->getArgument('definition');
        $inputMainLanguageCode = $input->getOption('main-language-code');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentTypeGroup = $this->contentTypeService->loadContentTypeGroup($inputGroupId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to create content type "%s" in group "%s"?',
                    $inputIdentifier,
                    $contentTypeGroup->identifier
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputGroupId,
                $inputIdentifier,
                $inputMainLanguageCode,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $definition = json_decode($inputDefinition, true);

                $contentTypeCreateStruct = $this->contentTypeService->newContentTypeCreateStruct($inputIdentifier);
                $contentTypeCreateStruct->mainLanguageCode = $definition['mainLanguageCode'] ?? $inputMainLanguageCode;

                $this->setTranslatedProperties($contentTypeCreateStruct, $definition, array('names', 'descriptions'));
                $this->setProperties($contentTypeCreateStruct, $definition, self::CONTENT_TYPE_PROPERTY_CASTS);

                foreach ($definition['fields'] as $field)
                {
                    $fieldDefinitionCreateStruct = $this->contentTypeService->newFieldDefinitionCreateStruct($field['identifier'], $field['fieldTypeIdentifier']);

                    $this->setTranslatedProperties($fieldDefinitionCreateStruct, $field, array('names', 'descriptions'));
                    $this->setProperties($fieldDefinitionCreateStruct, $field, self::FIELD_DEFINITION_PROPERTY_CASTS);

                    if (array_key_exists('defaultValue', $field))
                    {
                        $fieldDefinitionCreateStruct->defaultValue = $field['defaultValue'];
                    }

                    $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);
                }

                $contentTypeDraft = $this->contentTypeService->createContentType($contentTypeCreateStruct, array($contentTypeGroup));
                $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

                switch ($input->getOption('result-format'))
                {
                    case 'table':
                        {
                            $rows = array
                            (
                                array
                                (
                                    $contentTypeDraft->id,
                                    $contentTypeDraft->identifier
                                )
                            );
                            $headers = array
                            (
                                array
                                (
                                    'contentTypeId',
                                    'identifier'
                                )
                            );
                            $infoHeader = array
                            (
                                new TableCell
                                (
                                    "{$this->getName()} [$inputGroupId $inputIdentifier]",
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
                                $io->write("{$contentTypeDraft->id} {$contentTypeDraft->identifier}");
                            }
                            else
                            {
                                $io->writeln("{$contentTypeDraft->id} {$contentTypeDraft->identifier}");
                            }
                        }
                        break;

                    default:
                    {
                        $io->success("Create successful. contentTypeId: {$contentTypeDraft->id} identifier: {$contentTypeDraft->identifier}");
                    }
                }

                $this->logger->info($this->getName() . " successful", array($contentTypeDraft->id, $contentTypeDraft->identifier));
            }
            catch (ContentTypeFieldDefinitionValidationException $e)
            {
                $io->error($e->getMessage());
                foreach ($e->getFieldErrors() as $fieldIdentifier => $validationErrors)
                {
                    foreach ((array) $validationErrors as $validationError)
                    {
                        $io->writeln(" - [$fieldIdentifier] " . (string) $validationError->getTranslatableMessage());
                    }
                }

                $this->logger->error($this->getName() . " error", array($e->getMessage(), $e->getFieldErrors()));
            }
            catch (\Exception $e)
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

    /**
     * Merges language-hash values (e.g. "names", "descriptions") from $data onto matching
     * properties of $struct, where present.
     */
    private function setTranslatedProperties($struct, array $data, array $properties)
    {
        foreach ($properties as $property)
        {
            if (!isset($data[$property]))
            {
                continue;
            }

            foreach ($data[$property] as $languageCode => $value)
            {
                $struct->$property[$languageCode] = $value;
            }
        }
    }

    /**
     * Assigns scalar/array properties from $data onto $struct, where present, applying the
     * given cast callable (if any) from $propertyCasts as [propertyName => callable|null].
     */
    private function setProperties($struct, array $data, array $propertyCasts)
    {
        foreach ($propertyCasts as $property => $cast)
        {
            if (!isset($data[$property]))
            {
                continue;
            }

            $struct->$property = ($cast)? call_user_func($cast, $data[$property]) : $data[$property];
        }
    }
}