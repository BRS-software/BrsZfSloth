<?php

namespace BrsZfSloth\Sql\Where;

class In extends SingleField {

    protected function getExpr($field) {
        return sprintf('{%s} IN (:%s)', $field, $field);
    }
    protected function getNegativeExpr($field) {
        return sprintf('{%s} NOT IN (:%s)', $field, $field);
    }
}