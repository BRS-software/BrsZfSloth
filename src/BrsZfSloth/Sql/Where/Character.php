<?php

namespace BrsZfSloth\Sql\Where;

use
    Sloth\Sql\Where,
    Sloth\Sql\Expr;

abstract class Character extends SingleField {
    protected
        $caseSensitive = true,

        $positivExprCS,
        $negativeExprCS
    ;
    abstract protected function getExprCaseInsensitive($field);
    abstract protected function getNegativeExprCaseInsensitive($field);

    function __construct($field, $value = self::UNDEFINED) {
        parent::__construct($field, $value);
        $this->positivExprCS = $this->positiveExpr;
        $this->negativeExprCS = $this->negativeExpr;
    }
    function caseInsensitive() {
        $this->caseSensitive = false;
        $this->positiveExpr = $this->getExprCaseInsensitive($this->getField());
        $this->negativeExpr = $this->getNegativeExprCaseInsensitive($this->getField());
        $this->not($this->negation); // that refresh $this->expr
        return $this;
    }
    function caseSensitive() {
        // restore to oryginal state
        $this->caseSensitive = true;
        $this->positiveExpr = $this->positivExprCS;
        $this->negativeExpr = $this->negativeExprCS;
        $this->not($this->negation); // that refresh $this->expr
        return $this;
   }
   function isCaseSensitive() {
        return true === $this->caseSensitive;
   }
}