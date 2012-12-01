<?php

namespace BrsZfSloth\Sql\Where;

class DateLike extends SingleField {

    protected function getExpr($field) {
        return sprintf("to_char({%s}, 'YYYY-MM-DD') LIKE :%s", $field, $field);
    }
    protected function getNegativeExpr($field) {
        return sprintf("to_char({%s}, 'YYYY-MM-DD') NOT LIKE :%s", $field, $field);
    }
    protected function getValue() {
        return sprintf('%%%s%%', parent::getValue());
    }
}