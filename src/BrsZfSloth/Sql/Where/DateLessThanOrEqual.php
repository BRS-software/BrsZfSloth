<?php

namespace BrsZfSloth\Sql\Where;

class DateLessThanOrEqual extends SingleField {

    protected function getExpr($field) {
        return sprintf('DATE({%s})<=:%s', $field, $field);
    }
    protected function getNegativeExpr($field) {
        return 'NOT '.$this->getExpr($field);
    }
}