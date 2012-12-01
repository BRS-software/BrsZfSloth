<?php

namespace BrsZfSloth\Sql\Where;

class DateEqual extends SingleField {

    protected function getExpr($field) {
        return sprintf('DATE({%s})=:%s', $field, $field);
    }
    protected function getNegativeExpr($field) {
        return 'NOT '.$this->getExpr($field);
    }
}