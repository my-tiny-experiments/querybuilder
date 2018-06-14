<?php

namespace hassanalisalem\querybuilder;

class Filter
{

    /**
     * create a new Filter instance
     */
    function __construct($filterable)
    {
        $this->filterable = $filterable;
    }

    /**
     * Parse json to array or return it if it is an array
     *
     * @param $result [array|json object]
     * @return Array
     */
    public function parseJsonResult($result)
    {
        if(gettype($result) == 'array') {
            return $result;
        }
        return json_decode($result, true);
    }

    /*
     * check if a rule belongs to a relation or not
     *
     * @param Array $rule
     * @return Boolean
     */
    private function isRelation($rule)
    {
        $id = $rule['id'];
        $filterableValue = $this->filterable[$id];
        return strpos($filterableValue, 'this.') !== 0;
    }

    /**
     * check if the rule is filterable
     *
     * @param $id [rule id: the field name]
     * @return Boolean
     */
    private function isFilterable($id)
    {
        return isset($this->filterable[$id]);
    }

    /**
     * seperate rules into relation, not relation rules
     * also adding the nested rules if exists to the
     * 
     * @param Array $filters [the filters sent by jqueryquerybuilder]
     * @return Array
     */
    private function separateRules($filters)
    {
        $filters = $this->parseJsonResult($filters);
        if(empty($filters)) return [];
        $rules = $filters['rules']??[];
        $condition = $filters['condition']?? '';
        $relationRules = [];
        $notRelationRules = [];
        $nestedRules = [];
        foreach($rules as $rule) {
            if(!isset($rule['rules'])) {
                if(!$this->isFilterable($rule['id'])) {
                    continue;
                }

                if($this->isRelation($rule)) {
                    $relationRules[$rule['id']][] = $rule;
                } else {
                    $notRelationRules[$rule['id']][] = $rule;
                }
            } else {
                $nestedRules[] = $this->separateRules($rule);
            }
        }
        return [
            'rel' => $relationRules,
            'notRel' => $notRelationRules,
            'nested' => $nestedRules,
            'condition' => $condition,
        ];
    }

    /**
     * get allowed rules
     *
     * @param Array $filters
     * @return Array
     */
    public function getAllowedRules($filters)
    {
        return $this->separateRules($filters);
    }

}
