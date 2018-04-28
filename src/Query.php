<?php
namespace hassanalisalem\querybuilder;

class Query
{
    public static function build($model, $filters, $query = null) {
        $query = $query? $query: $model->newQuery();
        $filterable = $model->filterable;

        $filter = new Filter($filterable);
        $rules = $filter->getAllowedRules($filters);

        $newQuery = new QueryBuilder($query, $filterable);
        return $newQuery->buildQuery($rules);
    }
}
