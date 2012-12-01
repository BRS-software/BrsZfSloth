<?php
namespace BrsZfSloth\Sql;

use BrsZfSloth\Assert;
use BrsZfSloth\Exception;
use BrsZfSloth\Repository\Repository;
use BrsZfSloth\Definition\Definition;

class Order extends Expr {
    protected $direct = SORT_ASC;
    protected $rules = array();
    protected $singleField;

    public function __construct($field, $direct = null)
    {
        if (is_array($field)) {
            // first element will be this object
            if (current($field) instanceof Expr) {
                parent::__construct(current($field));
            } else {
                $this->setField(key($field));
                $this->setDirect(current($field));
            }
            // next elements will be added as another rules
            array_shift($field);
            if (!empty($field)) {
                $this->add(new self($field));
            }

        } elseif (is_string($field)) {
            $this->setField($field);
            if (null !== $direct) {
                $this->setDirect($direct);
            }
        } else {
            parent::__construct($field);
        }
    }

    public function setField($field)
    {
        $this->singleField = $field;
        $this->expr = sprintf('{%s} :direct', $field);
        return $this;
    }

    public function getField()
    {
        if (null === $this->singleField) {
            throw new Exception\RuntimeException(
                'field not set in order rule'
            );
        }
        return $this->singleField;
    }

    public function setDirect($direct)
    {
        if (null === $this->singleField)
            throw new Exception\RuntimeException(sprintf('Order rule "%s" is not single Field', $this));

        if (is_string($direct)) {
            switch (strtoupper(trim($direct))) {
                case 'ASC': $direct = SORT_ASC; break;
                case 'DESC': $direct = SORT_DESC; break;
            }
        }
        if ($direct !== SORT_ASC && $direct !== SORT_DESC)
            throw new Exception\InvalidArgumentException(sprintf('Invalid sort direct "%s"', $direct));

        $this->direct = $direct;
        return $this;
    }

    public function getDirect()
    {
        return $this->direct;
    }

    public function add($rule)
    {
        if (! $rule instanceof self) {
            $rule = new self($rule);
        }
        // Assert::objectClass($order, array('Sloth\Sql\Expr', 'Sloth\Sql\Order'));
        $this->rules[] = $rule;
        return $this;
    }

    public function render(array $params = array())
    {
        $coll[] = parent::render(array_merge($params, array(
            'direct' => new Expr(SORT_ASC===$this->getDirect() ? 'ASC' : 'DESC')
        )));

        foreach ($this->rules as $v) {
            $coll[] = $v->render($params);
        }

        return new Expr(implode(',', $coll));
    }
}