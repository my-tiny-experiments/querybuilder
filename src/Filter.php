<?php

namespace hassanalisalem\querybuilder;

class Filter
{

    function __construct($filterable)
    {
        $this->filterable = $filterable;
    }

    public function parseJsonResult($result)
    {
        if(gettype($result) == 'array') {
            return $result;
        }
        return json_decode($result, true);
    }

    private function isRelation($rule)
    {
        $id = $rule['id'];
        $filterableValue = $this->filterable[$id];
        return strpos($filterableValue, 'this.') !== 0;
    }

    private function isFilterable($id)
    {
        return isset($this->filterable[$id]);
    }

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

    public function getAllowedRules($filters)
    {
        return $this->separateRules($filters);
    }

}
