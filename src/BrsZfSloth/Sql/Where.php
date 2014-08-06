<?php
namespace BrsZfSloth\Sql;

use Zend\Db\Sql\ExpressionInterface;

use BrsZfSloth\Rule;
use BrsZfSloth\Assert;
use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Repository;
use BrsZfSloth\Definition;

class Where extends Expr
{
    const OP_AND = 'AND';
    const OP_OR = 'OR';
    const OP_XOR = 'XOR';

    static public $op = [
        self::OP_AND,
        self::OP_OR,
        self::OP_XOR
    ];

    protected $rules = array();
    protected $negation = false;
    protected $positiveExpr;
    protected $negativeExpr;

    public function __construct($expr, array $params = [], $defaultDefinition = null)
    {
        $this->positiveExpr = (string) $expr;
        $this->negativeExpr = sprintf('NOT (%s)', $expr);
        parent::__construct($this->positiveExpr, $params, $defaultDefinition);

        // if ($params) {
        //     $this->setParams($params);
        // }
    }

    // decides whether to add brackets during rendering as sub expression
    public function isComplex()
    {
        return ! empty($this->rules) || preg_match('/\s('.implode('|', self::$op).')\s/i', $this->expr);
    }

    public function andRule($rule)
    {
        return $this->addRule(self::OP_AND, $rule);
    }

    public function orRule($rule)
    {
        return $this->addRule(self::OP_OR, $rule);
    }

    public function xorRule($rule)
    {
        return $this->addRule(self::OP_XOR, $rule);
    }

    public function not($flag = true)
    {
        $this->parsed = null; // reset parsed expression cache
        $this->negation = (bool) $flag;

        if ($this->isNot()) {
            if (null === $this->negativeExpr) {
                throw new Exception\NotSetException(sprintf('Negative expression not set for "%s"', $this));
            }
            $this->expr = $this->negativeExpr;
        } else {
            $this->expr = $this->positiveExpr;
        }
        return $this;
    }

    public function isNot()
    {
        return true === $this->negation;
    }

    protected function addRule($op, $rule)
    {
        if (! $rule instanceof self) { // hmm po co ty było?
            $rule = (new self($rule))
                ->setParam($rule->getParam())
                ->addDefinitions($rule->getDefinitions())
            ;
        }
        // Assert::objectClass($rule, array('Sloth\Sql\Expr', 'Sloth\Sql\Where'));
        array_push($this->rules, array($op, $rule));
        return $this;
    }

    public function render(array $params = array())
    {
        $expr = parent::render($params);

        foreach ($this->rules as $rule) {
            if ($rule[1]->isComplex()) {
                $tpl = '%s %s (%s)';
            } else {
                $tpl = '%s %s %s';
            }
            $expr = sprintf(
                $tpl,
                $expr,
                $rule[0],
                $rule[1]
                    ->addDefinitions($this->definitions)
                    ->render($params)
            );
        }
        return new Expr($expr);
    }

    public function getExpressionData()
    {
        $exprData = parent::getExpressionData();

        foreach ($this->rules as $rule) {
            // operator
            $exprData[] = array(
                ' %s ',
                array($rule[0]),
                array(ExpressionInterface::TYPE_LITERAL)
            );

            // add sub rules
            if ($isComplex = $rule[1]->isComplex()) {
                $exprData[] = '(';
            }
            $exprData = array_merge($exprData, $rule[1]
                ->addDefinitions($this->definitions)
                ->getExpressionData()
            );
            if ($isComplex) {
                $exprData[] = ')';
            }
        }
        return $exprData;
    }
}