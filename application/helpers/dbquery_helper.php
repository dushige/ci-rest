<?php

define('WHERE_OPERATOR_EQUAL', '=');
define('WHERE_OPERATOR_GT', '>');
define('WHERE_OPERATOR_GE', '>=');
define('WHERE_OPERATOR_LT', '<');
define('WHERE_OPERATOR_LE', '<=');
define('WHERE_OPERATOR_NOT_EQUAL', '<>');
define('WHERE_OPERATOR_LIKE', 'LIKE ');
define('WHERE_OPERATOR_NOT_LIKE', 'NOT LIKE ');

define('WHERE_ORDER_ASC', 'asc');
define('WHERE_ORDER_DESC', 'desc');

class MixedQueryCondition {

    private $ar_where = [];
    private $ar_or_where = [];
    private $ar_wherein = [];
    private $order_by = [];
    private $ar_where_notin = [];

    public function where($field, $operator, $value) {
        $this->ar_where[] = [$field, $operator, $value];
        return $this;
    }

    public function or_where($field, $operator, $value) {
        $this->ar_or_where[] = [$field, $operator, $value];
        return $this;
    }

    public function where_in($field, array $values) {
        $this->ar_wherein[] = [$field, $values];
        return $this;
    }

    public function where_not_in($field, array $values) {
        $this->ar_where_notin[] = [$field, $values];
        return $this;
    }

    public function order_by($field, $sort) {
        $this->order_by[] = [$field, $sort];
        return $this;
    }

    public function get_where() {
        return $this->ar_where;
    }

    public function get_or_where() {
        return $this->ar_or_where;
    }

    public function get_wherein() {
        return $this->ar_wherein;
    }

    public function get_order_by() {
        return $this->order_by;
    }

    public function get_where_notin() {
        return $this->ar_where_notin;
    }

}

function createMixedQueryCondition() {
    return new MixedQueryCondition();
}
