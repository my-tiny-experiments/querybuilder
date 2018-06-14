<?php
namespace hassanalisalem\querybuilder;

class Query
{

    /**
     * build the query from jQueryQueryBuilder filters
     *
     * @param Illuminate\Database\Eloquent\Model $model
     * @param Array $filters
     * @param Illuminate\Database\Query $query
     * @return Illuminate\Database\Query $query
     */
    public static function build($model, $filters, $query = null)
    {
        $query = $query? $query: $model->newQuery();
        $filterable = $model->filterable;

        $filter = new Filter($filterable);
        $rules = $filter->getAllowedRules($filters);

        $newQuery = new QueryBuilder($query, $filterable);
        return $newQuery->buildQuery($rules);
    }
}
