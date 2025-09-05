<?php

namespace MugoWeb\Eep\Bundle\Query\Solr\CriterionVisitor;

use MugoWeb\Eep\Bundle\Query\Solr\Criterion as EepSolrCriterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Solr\Query\CriterionVisitor;

/**
 * Visits the Raw criterion.
 */
class Raw extends CriterionVisitor
{
    /**
     * Check if visitor is applicable to current criterion.
     *
     * @return bool
     */
    public function canVisit(CriterionInterface $criterion): bool
    {
        return $criterion instanceof EepSolrCriterion\Raw;
    }

    /**
     * Expects Solr syntax and passes the criterion value through as is.
     *
     * @param CriterionVisitor $subVisitor
     *
     * @return string
     */
    public function visit(CriterionInterface $criterion, CriterionVisitor $subVisitor = null): string
    {
        return $criterion->value[0];
    }
}
