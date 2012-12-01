<?php

namespace BrsZfSloth\Sql\Where;

class Equal extends SingleField {

    protected function getExpr($field) {
        return sprintf('{%s}=:%s', $field, $field);
    }
    protected function getNegativeExpr($field) {
        return 'NOT '.$this->getExpr($field);
    }
    function render(array $params = array()) {
        // replace rule when value is null
        if (null === $this->getValue()) {
            $null = new Nul($this->getField());
            return $null->not($this->negation)->render($repo, $params);
        } else {
            return parent::render($params);
        }
    }
}