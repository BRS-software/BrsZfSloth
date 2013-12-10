<?php
namespace BrsZfSloth\Sql;

use Zend\Db\Sql\Select as ZfSelect;
use Zend\Db\Sql\Predicate;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\DefinitionAwareInterface;

class Select extends ZfSelect implements DefinitionAwareInterface
{
    protected $definition;

    /**
     * Create where clause
     *
     * @param  Where|\Closure|string|array $predicate
     * @param  string $combination One of the OP_* constants from Predicate\PredicateSet
     * @return Select
     */
    public function where($predicate, $combination = Predicate\PredicateSet::OP_AND)
    {
        if ($predicate instanceof DefinitionAwareInterface) {
            $predicate->setDefinition($this->getDefinition());
        }
        // apply default definition alias
        if ($predicate instanceof Expr) {
            $predicate->setDefaultDefinition($this);
        }

        // HACK, because when an object is passed, nothing happens
        // Looks like a bug...
        if ($predicate instanceof Expr) {
            $predicate = [$predicate];
        }
        return parent::where($predicate, $combination);
    }

    /**
     * @param string|array $order
     * @return Select
     */
    public function order($order)
    {
        // apply default definition alias
        if ($order instanceof Expr) {
            $order->setDefaultDefinition($this);
        }
        return parent::order((string) $order->render());
    }

    public function getDefinition()
    {
        if (null === $this->definition) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('definition not set in select %s', get_class($this))
            );
        }
        return $this->definition;
    }

    public function setDefinition(Definition $definition)
    {
        $this->definition = $definition;
        return $this;
    }

    public function configureFromDefinition()
    {
        $definition = $this->getDefinition();
        // $this->from($definition->getTableIdentifier());
        $this->from($definition->getTable());
        $this->columns($definition->getMapping());
        // dbgd($definition->getDefaultOrder());
        $this->order($definition->getDefaultOrder());
        return $this;
    }
 }