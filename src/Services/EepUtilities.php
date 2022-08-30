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
}