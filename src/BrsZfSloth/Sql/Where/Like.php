<?php

namespace BrsZfSloth\Sql\Where;

class Like extends Character {
    protected
        $startWildcard = '',
        $endWildcard = ''
    ;

    protected function getExpr($field) {
        return sprintf('{%s} LIKE :%s', $field, $field);
    }
    protected function getNegativeExpr($field) {
        return sprintf('{%s} NOT LIKE :%s', $field, $field);
    }
    protected function getExprCaseInsensitive($field) {
        return sprintf('UPPER({%s}) LIKE UPPER(:%s)', $field, $field);
    }
    protected function getNegativeExprCaseInsensitive($field) {
        return sprintf('UPPER({%s}) NOT LIKE UPPER(:%s)', $field, $field);
    }
    protected function getValue() {
        return $this->startWildcard.parent::getValue().$this->endWildcard;
    }
    function startWildcard($wildcard = '%') {
        $this->startWildcard = $wildcard;
        return $this;
    }
    function endWildcard($wildcard = '%') {
        $this->endWildcard = $wildcard;
        return $this;
    }
}