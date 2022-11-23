<?php

namespace MugoWeb\Eep\Bundle\Query\Solr\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;

/**
 * A criterion that does not manipluate the value passed in.
 * Allows for Solr syntax for query/filter to be passed through as is.
 */
class Raw extends Criterion
{
    /**
     * Creates a new Raw criterion.
     *
     * @param string $value Raw Solr syntax string
     */
    public function __construct($value)
    {
        parent::__construct(null, null, $value);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_STRING
            )
        ];
    }
}
