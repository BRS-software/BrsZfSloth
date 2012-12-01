<?php

namespace BrsZfSloth\Sql\Where;

class Tru extends SingleField {

    protected function getExpr($field) {
        return sprintf('{%s}', $field);
    }
    protected function getNegativeExpr($field) {
        return 'NOT '.$this->getExpr($field);
    }
}