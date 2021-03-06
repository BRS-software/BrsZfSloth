<?php
namespace BrsZfSloth\Event;

use Zend\EventManager\Event;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Repository;
use BrsZfSloth\Entity\Entity;
use BrsZfSloth\Entity\EntityTools;
use BrsZfSloth\Entity\Feature\GetChangesFeatureInterface;

class EntityOperation extends Event
{
    protected $changes = [];

    public function __construct($target, $entity, $params = null)
    {
        parent::__construct(null, $target, $params);
        $this->setParam('entity', $entity);
    }

    public function setChanges(array $changes)
    {
        $this->changes = $changes;
        return $this;
    }

    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @return array
     */
    // public function getChanges()
    // {
    //     $entity = $this->getParam('entity');
    //     if ($entity instanceof GetChangesFeatureInterface) {
    //         return $entity->getChanges();
    //     } else {
    //         throw new Exception\UnsupportedException(
    //             ExceptionTools::msg('entity %s does not supported GetChangesFeature', $entity)
    //         );
    //     }
    // }

    /**
     * Run passed function when fieldName is changed.
     * @param string $fieldName
     * @param \Closure $fn
     */
    public function onChange($fieldName, \Closure $fn)
    {
        if (in_array($this->getName(), ['pre.insert', 'post.insert'])) {
            $fn(EntityTools::getValue($fieldName, $this->getParam('entity')), null);
        } else {
            $ch = $this->getChanges();
            if (array_key_exists($fieldName, $ch)) {
                $fn($ch[$fieldName]['new'], $ch[$fieldName]['old']);
            }
        }
    }
}