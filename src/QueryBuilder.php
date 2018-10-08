<?php
namespace hassanalisalem\querybuilder;

class QueryBuilder
{

    /**
     * a dictionary of operators, from jQueryQueryBuilder 
     * @param Array
     */
    private $operators = [
        'equal' => '=', 'in' => 'in', 'not_in' => 'not_in', 'not_equal' => '!=',
        'less' => '<', 'less_or_equal' => '<=', 'greater' => '>',
        'greater_or_equal' => '>=', 'between' => 'between',
        'not_between' => 'not_between', 'is_null' => 'is_null',
        'is_not_null' => 'is_not_null', 'contains' => 'like',
    ];

    /**
     * create a new QueryBuilder instance
     *
     * @param Illuminate\Database\Query $query
     * @param Array
     */
    function __construct(&$query, $filterable)
    {
        $this->query = $query;
        $this->filterable = $filterable;
    }

    /**
     * parse a key, to get the table name and the column name
     *
     * @param String $key
     * @return Array
     */
    private function parseKey($key)
    {
        $parts = explode('.', $key);
        $col = array_pop($parts);
        $table = implode('.', $parts);

        if(strpos($col, ' as ')) {
            $colParts = explode(' as ', $col);
            $col = $colParts[1] . '.' . $colParts[0];
        }

        return [
            'table' => $table,
            'col' => $col,
        ];
    }

    /**
     * get the column name
     *
     * @param String $key
     * @return String
     */
    private function getColName($key)
    {
        return $this->parseKey($key)['col'];
    }

    /**
     * get the table name
     *
     * @param String $key
     * @return String
     */
    private function getTableName($key) {
        return $this->parseKey($key)['table'];
    }

    /**
     * change a given text to orText or andText
     *
     * @param String $text
     * @param String $condition ['or', 'and']
     * @return String
     */
    private function toOrAnd($text, $condition = null)
    {
        if(!$condition || strtolower($condition) == 'and') return $text;
        $result = strtolower($condition) . ucfirst($text);
        return $result;
    }

    /**
     * get where type as whereIn, whereNull...
     *
     * @param String $Operator
     * @param String $condition ['or', 'and']
     * @return String "the where statement"
     */
    private function getWhereType($operator, $condition) {
        $operatorWhere = [
            'in' => 'whereIn', 'not_in' => 'whereNotIn', 'between' => 'whereBetween',
            'not_between' => 'whereNotBetween', 'is_null' => 'whereNull',
            'is_not_null' => 'whereNotNull',
        ];

        $result = $this->toOrAnd($operatorWhere[$operator]?? 'where', $condition);
        return $result;
    }

    /**
     * building the whereQuery
     *
     * @param Illuminate\Database\Query $query
     * @param String $condition ['or', 'and']
     * @param String $col "column name"
     * @param String $operator
     * @param mixed value
     */
    private function whereQuery(&$query, $condition, $col, $operator, $value)
    {
        $operators = $this->operators;

        $operator = $operators[$operator];
        $where = $this->getWhereType($operator, $condition);

        if(in_array($operator, ['=', '>', '<', '>=', '<='])) {
            $query->{$where}($col, $operator, $value);
        } elseif(in_array($operator, ['in', 'not_in'])) {
            $query->{$where}($col, $value);
        } elseif(in_array($operator, ['between', 'not_between'])) {
            $query->where(function ($q) use ($col, $value, $where) {
                $q->{$where}($col, $value);
            });
        } elseif(in_array($operator, ['like'])) {
            $query->{$where}($col, 'like',  '%' . $value . '%');
        } elseif(in_array($operator, ['is_null', 'is_not_null'])) {
            $query->{$where}($col);
        }
    }

    /**
     * build relation query
     *
     * @param Illuminate\Database\Query $query
     * @param Array $rules
     * @param String $condition
     */
    private function relQuery(&$query, $rules, $condition)
    {
        $where = $this->toOrAnd('where', $condition). 'Has';
        foreach($rules as $key => $rule) {
            $tableName = $this->getTableName($this->filterable[$key]);
            $query->{$where}($tableName, function ($q) use ($rules, $condition, $key){
                $this->notRelQuery($q, [$key => $rules[$key]], $condition);
            });
        }
    }

    /**
     * build non relation query
     *
     * @param Illuminate\Database\Query $query
     * @param Array $rules
     * @param String $condition ['or', 'and']
     */
    private function notRelQuery(&$query, $rules, $condition)
    {
        foreach($rules as $key => $rule) {
            $col = $this->getColName($this->filterable[$key]);
            $first = true;
            if(count($rule) == 1) $first = false;
            foreach($rule as $subRule) {
                $this->whereQuery($query, $first? 'AND': $condition, $col, $subRule['operator'], $subRule['value']);
                $first = false;
            }
        }
    }

    /**
     * building the query 
     * 
     * @param Illuminate\Database\Query $query
     * @param Array $rules
     */
    private function query(&$query, $rules) {
        $this->relQuery($query, $rules['rel'], $rules['condition']);
        $this->notRelQuery($query, $rules['notRel'], $rules['condition']);

        if(!empty($rules['nested'])) {
            $where = $this->toOrAnd('where', $rules['condition']);
            foreach($rules['nested'] as $nestedRule) {
                $query->{$where}(function ($q) use($nestedRule) {
                    $this->query($q, $nestedRule);
                });
            }
        }
    }

    /**
     * interface to query function, that get the initial query
     * then build it with the query function then return it
     *
     * @param Array $rules
     * @return Illuminate\Database\Query
     */
    public function buildQuery($rules) {
        if(empty($rules)) return $this->query;
        $this->query($this->query, $rules);
        return $this->query;
    }

}
