<?php

namespace BrsZfSloth\Sql\Where;

class LessThanOrEqual extends SingleField {

    protected function getExpr($field) {
        return sprintf('{%s}<=:%s', $field, $field);
    }
    protected function getNegativeExpr($field) {
        return 'NOT '.$this->getExpr($field);
    }
}