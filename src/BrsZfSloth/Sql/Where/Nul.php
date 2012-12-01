<?php

namespace BrsZfSloth\Sql\Where;

class Nul extends SingleField {

    protected function getExpr($field) {
        return sprintf('{%s} IS NULL', $field);
    }
    protected function getNegativeExpr($field) {
        return sprintf('{%s} IS NOT NULL', $field);
    }
}