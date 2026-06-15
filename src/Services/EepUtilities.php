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
            \eZ\Publish\API\Repository\Values\User\User::PASSWORD_HASH_MD5_PASSWORD => 'MD5_PASSWORD',
            \eZ\Publish\API\Repository\Values\User\User::PASSWORD_HASH_MD5_USER => 'MD5_USER',
            \eZ\Publish\API\Repository\Values\User\User::PASSWORD_HASH_MD5_SITE => 'MD5_SITE',
            \eZ\Publish\API\Repository\Values\User\User::PASSWORD_HASH_PLAINTEXT => 'PLAINTEXT',
            \eZ\Publish\API\Repository\Values\User\User::PASSWORD_HASH_BCRYPT => 'BCRYPT',
            \eZ\Publish\API\Repository\Values\User\User::PASSWORD_HASH_PHP_DEFAULT => 'PHP_DEFAULT',
        );

        return (isset($labels[$hashAlgorithmId]))? $labels[$hashAlgorithmId] : 'N/A';
    }

    public static function getLocationSortFieldLabel($sortFieldId)
    {
        $labels = array
        (
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_PATH => 'PATH',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_PUBLISHED => 'PUBLISHED',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_MODIFIED => 'MODIFIED',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_SECTION => 'SECTION',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_DEPTH => 'DEPTH',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_CLASS_IDENTIFIER => 'CLASS_IDENTIFIER',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_CLASS_NAME => 'CLASS_NAME',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_PRIORITY => 'PRIORITY',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_NAME => 'NAME',

            /**
             * @deprecated
             */
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_MODIFIED_SUBNODE => 'MODIFIED_SUBNODE',

            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_NODE_ID => 'NODE_ID',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_FIELD_CONTENTOBJECT_ID => 'CONTENTOBJECT_ID',
        );

        return (isset($labels[$sortFieldId]))? $labels[$sortFieldId] : 'N/A';
    }

    public static function getLocationSortOrderLabel($sortOrderId)
    {
        $labels = array
        (
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_ORDER_DESC => 'DESC',
            \eZ\Publish\API\Repository\Values\Content\Location::SORT_ORDER_ASC => 'ASC',
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
