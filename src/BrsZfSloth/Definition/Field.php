<?php
namespace BrsZfSloth\Definition;

use Zend\Stdlib\AbstractOptions;
use Zend\Stdlib\Hydrator\HydratorInterface;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Assert;
use BrsZfSloth\Entity\Entity;

class Field extends AbstractOptions
{
    // types
    const TYPE_INTEGER = 'integer';
    const TYPE_SMALLINT = 'smallint';
    const TYPE_TEXT = 'text';
    const TYPE_CHARACTER_VARYING = 'character varying';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_ARRAY = 'array';

    public static $types = [
        self::TYPE_INTEGER => [
            'assert' => 'int',
            'hydratorStrategyClass' => 'BrsZfSloth\Hydrator\Strategy\Integer',
        ],
        self::TYPE_SMALLINT => [
            'assert' => 'smallint',
        ],
        self::TYPE_TEXT => [
            'assert' => 'string',
        ],
        self::TYPE_CHARACTER_VARYING => [
            'assert' => 'characterVarying'
        ],
        self::TYPE_BOOLEAN => [
            'assert' => 'bool',
            'hydratorStrategyClass' => 'BrsZfSloth\Hydrator\Strategy\Boolean',
        ],
        self::TYPE_DATE => [
            'assert' => 'date',
            'assertParams' => ['Y-m-d']
        ],
        self::TYPE_DATETIME => [
            'assert' => 'date',
            'assertParams' => ['Y-m-d H:i:s.u'],
            // 'hydratorStrategyClass' => 'BrsZfSloth\Hydrator\Hydrator\Strategy\Datetime',
        ],
        self::TYPE_TIMESTAMP => [
            'assert' => 'date',
            'assertParams' => ['Y-m-d H:i:s'],
        ],
        self::TYPE_ARRAY => [
            'assert' => 'arra',
            'hydratorStrategyClass' => 'BrsZfSloth\Hydrator\Strategy\Arra',
        ],
    ];

    protected $name;
    protected $type = self::TYPE_TEXT;
    protected $assert;
    protected $assertParams = array();
    protected $hydratorStrategyClass;
    protected $default;
    protected $mapping;
    protected $primary = false;
    protected $sequence; // db sequence name
    protected $notNull = false;
    // private will not be exported in toArray() method


    public function __construct($name, $config = null)
    {
        $this->setName($name);

        if (is_string($config)) {
            $this->setType($config);
        } elseif (is_array($config)) {
            $this->setFromArray($config);
        } elseif (null !== $config) {
            throw new Exception\InvalidArgumentException(
                'argument must be type string or config array'
            );
        }
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function export()
    {
        $export = [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'default' => $this->getDefault(),
            'notNull' => $this->getNotNull(),
            'mapping' => $this->getMapping(),
        ];

        if ($this->getAssertParams()) {
            $export['type'] = sprintf('%s(%s)', $this->getType(), join(',', $this->getAssertParams()));
        }
        if ($this->isPrimary()) {
            $export['primary'] = true;
        }
        if ($this->getSequence()) {
            $export['sequence'] = $this->getSequence();
        }

        return $export;
    }

    // public function toArray()
    // {
    //     $array = array();
    //     $transform = function($letters) {
    //         $letter = array_shift($letters);
    //         return '_' . strtolower($letter);
    //     };
    //     foreach ($this as $key => $value) {
    //         if ($key === '__strictMode__') continue;
    //         $normalizedKey = preg_replace_callback('/([A-Z])/', $transform, $key);
    //         $array[$normalizedKey] = $value;
    //     }
    //     return $array;
    // }

    protected function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        preg_match('/([^(]+)(\((.*)\))?/', $type, $m); // TODO maybe in the future will be possible to pass type i.e.: boolean not null default false
        $type = trim($m[1]);

        if (! array_key_exists($type, self::$types)) {
            throw new Exception\InvalidArgumentException(
                sprintf('invalid field type "%s", available types: %s', $type, join(',', array_keys(self::$types)))
            );
        }

        $typeConfig = self::$types[$type];

        $this->type = $type;
        $this->assert = $typeConfig['assert']; // it should not be configured in user definitions

        // if parsed params exists i.e. character varying(10)
        if (isset($m[3])) {
            $this->setAssertParams(explode(',', $m[3]));
        } elseif (isset($typeConfig['assertParams'])) {
            $this->setAssertParams($typeConfig['assertParams']);
        }
        // hydaratorStrategyClass
        if (isset($typeConfig['hydratorStrategyClass'])) {
            $this->setHydratorStrategyClass($typeConfig['hydratorStrategyClass']);
        }

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getAssert()
    {
        return $this->assert;
    }

    public function setAssertParams(array $params)
    {
        return $this->assertParams = $params;
    }

    public function getAssertParams()
    {
        return $this->assertParams;
    }

    public function setNotNull($flag)
    {
        $this->notNull = (bool) $flag;
        return $this;
    }

    public function getNotNull()
    {
        return $this->notNull;
    }

    public function isNotNull()
    {
        return $this->getNotNull();
    }

    public function setDefault($value)
    {
        $this->default = $value;
        return $this;
    }

    public function getDefault()
    {
        // default value must be validated
        // better to do it here, because if it has changed assertParams, the validation would be incorrect
        // return $this->assertValue($this->default);
        return $this->default;
    }

    // public function hasDefault()
    // {
    //     return null !== $this->default;
    // }

    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
        return $this;
    }

    public function getMapping()
    {
        if (null === $this->mapping) {
            $this->setMapping(
                strtolower(preg_replace('/\B([A-Z])/', '_${1}', $this->getName()))
            );
        }
        return $this->mapping;
    }

    public function setPrimary($flag)
    {
        $this->primary = (bool) $flag;
        return $this;
    }

    public function getPrimary()
    {
        return $this->primary;
    }

    public function isPrimary()
    {
        return $this->getPrimary();
    }

    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function getSequence()
    {
        return $this->sequence;
    }

    public function assertValue($value)
    {
        $args = $this->getAssertParams();
        array_unshift($args, $value);
        forward_static_call_array(array('BrsZfSloth\Assert', $this->getAssert()), $args); // must throw InvalidArgumentExceptions when fault
        return $value;
    }

    public function setHydratorStrategyClass($class)
    {
        if (! class_exists($class) || ! in_array('Zend\Stdlib\Hydrator\Strategy\StrategyInterface', class_implements($class))) {
            throw new Exception\InvalidArgumentException(
                ExceptionTools::msg('argument must be valid class and implements Zend\Stdlib\Hydrator\Strategy\StrategyInterface interface, given %s', $class)
            );
        }
        $this->hydratorStrategyClass = $class;
        return $this;
    }

    public function getHydratorStrategyClass()
    {
        if (null === $this->hydratorStrategyClass) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('hydratorStrategyClass not set in %s', $this)
            );
        }
        return $this->hydratorStrategyClass;
    }

    public function hasHydratorStrategyClass()
    {
        return null !== $this->hydratorStrategyClass;
    }

    public function getHydratorStrategy()
    {
        $class = $this->getHydratorStrategyClass();
        return new $class;
    }

    public function addHydratorStrategy(HydratorInterface $hydrator)
    {
        if ($this->hasHydratorStrategyClass()) {
            $hydrator->addStrategy($this->getName(), $this->getHydratorStrategy());
        }
    }

    public function removeHydratorStrategy(HydratorInterface $hydrator)
    {
        if ($hydrator->hasStrategy($this->getName())) {
            $hydrator->removeStrategy($this->getName());
        }
    }
}
