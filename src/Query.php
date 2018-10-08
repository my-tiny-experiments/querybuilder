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
        $subFilters = $model->subFilters;

        $filter = new Filter($filterable);
        $rules = $filter->getAllowedRules($filters);

        foreach($rules['rel'] ?? [] as $key => $value) {
            foreach($value as $subKey => $subValue) {
                if($subValue['data']['nested']?? false == 'true') {
                    $subModel = $subFilters[$key];
                    $subQuery = self::build(new $subModel(), $subValue['value']);
                    $rules['rel'][$key][$subKey]['value'] = $subQuery->get()->pluck('id')->toArray();
                }
            }
        }

        foreach($rules['notRel'] ?? [] as $key => $value) {
            foreach($value as $subKey => $subValue) {
                if($subValue['data']['nested']?? false == 'true') {
                    $subModel = $subFilters[$key];
                    $subQuery = self::build(new $subModel(), $subValue['value']);
                    $rules['notRel'][$key][$subKey]['value'] = $subQuery->get()->pluck('id')->toArray();
                }
            }
        }

        $newQuery = new QueryBuilder($query, $filterable);
        return $newQuery->buildQuery($rules);
    }
}
