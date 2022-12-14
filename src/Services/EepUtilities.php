<?php

namespace MugoWeb\Eep\Bundle\Services;

class EepUtilities
{
    public function __construct(){}

    public static function getContentRelationTypeLabel($relationTypeId)
    {
        $labels = array
        (
            \eZ\Publish\API\Repository\Values\Content\Relation::COMMON => 'COMMON',
            \eZ\Publish\API\Repository\Values\Content\Relation::EMBED => 'EMBED',
            \eZ\Publish\API\Repository\Values\Content\Relation::LINK => 'LINK',
            \eZ\Publish\API\Repository\Values\Content\Relation::FIELD => 'FIELD',
            \eZ\Publish\API\Repository\Values\Content\Relation::ASSET => 'ASSET',
        );

        return (isset($labels[$relationTypeId]))? $labels[$relationTypeId] : 'N/A';
    }

    public static function getContentVersionStatusLabel($statusId)
    {
        $labels = array
        (
            \eZ\Publish\API\Repository\Values\Content\VersionInfo::STATUS_DRAFT => 'DRAFT',
            \eZ\Publish\API\Repository\Values\Content\VersionInfo::STATUS_PUBLISHED => 'PUBLISHED',
            \eZ\Publish\API\Repository\Values\Content\VersionInfo::STATUS_ARCHIVED => 'ARCHIVED',
        );

        return (isset($labels[$statusId]))? $labels[$statusId] : 'N/A';
    }

    public static function getUserHashAlgorithmLabel($hashAlgorithmId)
    {
        $labels = array
        (
            \eZ\Publish\API\Repository\Values\User\User::PASSWORD_HASH_BCRYPT => 'BCRYPT',
            \eZ\Publish\API\Repository\Values\User\User::PASSWORD_HASH_PHP_DEFAULT => 'PHP_DEFAULT',
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
