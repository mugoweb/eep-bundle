<?php

namespace MugoWeb\Eep\Bundle\Services;

class EepUtilities
{
    public function __construct(){}

    public static function getContentRelationTypeLabel($relationTypeId)
    {
        $labels = array
        (
            \Ibexa\Contracts\Core\Repository\Values\Content\RelationType::COMMON->value => 'FIELD',
            \Ibexa\Contracts\Core\Repository\Values\Content\RelationType::EMBED->value => 'FIELD',
            \Ibexa\Contracts\Core\Repository\Values\Content\RelationType::LINK->value => 'FIELD',
            \Ibexa\Contracts\Core\Repository\Values\Content\RelationType::FIELD->value => 'FIELD',
            \Ibexa\Contracts\Core\Repository\Values\Content\RelationType::ASSET->value => 'ASSET',
        );

        return (isset($labels[$relationTypeId]))? $labels[$relationTypeId] : 'N/A';
    }

    public static function getContentVersionStatusLabel($statusId)
    {
        $labels = array
        (
            \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo::STATUS_DRAFT => 'DRAFT',
            \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo::STATUS_PUBLISHED => 'PUBLISHED',
            \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo::STATUS_ARCHIVED => 'ARCHIVED',
        );

        return (isset($labels[$statusId]))? $labels[$statusId] : 'N/A';
    }

    public static function getUserHashAlgorithmLabel($hashAlgorithmId)
    {
        $labels = array
        (
            \Ibexa\Contracts\Core\Repository\Values\User\User::PASSWORD_HASH_BCRYPT => 'BCRYPT',
            \Ibexa\Contracts\Core\Repository\Values\User\User::PASSWORD_HASH_PHP_DEFAULT => 'PHP_DEFAULT',
            \Ibexa\Contracts\Core\Repository\Values\User\User::PASSWORD_HASH_INVALID => 'INVALID',
        );

        return (isset($labels[$hashAlgorithmId]))? $labels[$hashAlgorithmId] : 'N/A';
    }

    public static function getLocationSortFieldLabel($sortFieldId)
    {
        $labels = array
        (
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_PATH => 'PATH',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_PUBLISHED => 'PUBLISHED',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_MODIFIED => 'MODIFIED',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_SECTION => 'SECTION',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_DEPTH => 'DEPTH',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_CLASS_IDENTIFIER => 'CLASS_IDENTIFIER',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_CLASS_NAME => 'CLASS_NAME',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_PRIORITY => 'PRIORITY',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_NAME => 'NAME',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_NODE_ID => 'NODE_ID',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_FIELD_CONTENTOBJECT_ID => 'CONTENTOBJECT_ID',
        );

        return (isset($labels[$sortFieldId]))? $labels[$sortFieldId] : 'N/A';
    }

    public static function getLocationSortOrderLabel($sortOrderId)
    {
        $labels = array
        (
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_ORDER_DESC => 'DESC',
            \Ibexa\Contracts\Core\Repository\Values\Content\Location::SORT_ORDER_ASC => 'ASC',
        );

        return (isset($labels[$sortOrderId]))? $labels[$sortOrderId] : 'N/A';
    }

    public static function stripColumnMarkers($columnIdentifier)
    {
        $s = array( ' *' );
        $r = array( '' );

        return str_replace($s,$r, $columnIdentifier);
    }
}
