<?php

namespace BrsZfSloth\Sql\Where;

class Fals extends SingleField {

    protected function getExpr($field) {
        return sprintf('NOT {%s}', $field);
    }
    protected function getNegativeExpr($field) {
        return sprintf('{%s}', $field);
    }
}