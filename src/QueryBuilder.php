
<?php
namespace hassanalisalem\querybuilder;

class QueryBuilder
{

    function __construct(&$query, $filterable) {
        $this->query = $query;
        $this->filterable = $filterable;
    }

    private function parseKey($key) {
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

    private function getColName($key) {
        return $this->parseKey($key)['col'];
    }

    private function getTableName($key) {
        return $this->parseKey($key)['table'];
    }

    private function toOrAnd($text, $condition = null) {
        if(!$condition || strtolower($condition) == 'and') return $text;
        $result = strtolower($condition) . ucfirst($text);
        return $result;
    }

    private function getWhereType($operator, $condition) {
        $operatorWhere = [
            'in' => 'whereIn', 'not_in' => 'whereNotIn', 'between' => 'whereBetween',
            'not_between' => 'whereNotBetween', 'is_null' => 'whereNull',
            'is_not_null' => 'whereNotNull',
        ];

        $result = $this->toOrAnd($operatorWhere[$operator]?? 'where', $condition);
        return $result;
    }

    private function whereQuery(&$query, $condition, $col, $operator, $value) {
        $operators = [
            'equal' => '=', 'in' => 'in', 'not_in' => 'not_in', 'not_equal' => '!=',
            'less' => '<', 'less_or_equal' => '<=', 'greater' => '>',
            'greater_or_equal' => '>=', 'between' => 'between',
            'not_between' => 'not_between', 'is_null' => 'is_null',
            'is_not_null' => 'is_not_null', 'contains' => 'like',
        ];

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


    private function relQuery(&$query, $rules, $condition) {
        $where = $this->toOrAnd('where', $condition). 'Has';
        foreach($rules as $key => $rule) {
            $tableName = $this->getTableName($this->filterable[$key]);
            $query->{$where}($tableName, function ($q) use ($rules, $condition, $key){
                $this->notRelQuery($q, [$key => $rules[$key]], $condition);
            });
        }
    }

    private function notRelQuery(&$query, $rules, $condition) {
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

    public function buildQuery($rules) {
        if(empty($rules)) return $this->query;
        $this->query($this->query, $rules);
        return $this->query;
    }

}
