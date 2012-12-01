<?php

namespace BrsZfSloth\Sql\Where;

class Bool extends SingleField {

    protected function getExpr($field) {
        return sprintf('{%s}=:%s', $field, $field);
    }
    protected function getNegativeExpr($field) {
        return 'NOT '.$this->getExpr($field);
    }
    protected function getValue() {
        return parent::getValue() ? 'true' : 'false';
    }
}