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

    public static function stripColumnMarkers($columnIdentifier)
    {
        $s = array( ' *' );
        $r = array( '' );

        return str_replace($s,$r, $columnIdentifier);
    }
}
