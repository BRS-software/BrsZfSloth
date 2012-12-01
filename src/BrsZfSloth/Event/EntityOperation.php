<?php
namespace BrsZfSloth\Event;

use Zend\EventManager\Event;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Repository;
use BrsZfSloth\Entity\Entity;
use BrsZfSloth\Entity\Feature\GetChangesFeatureInterface;

class EntityOperation extends Event
{
    public function __construct($target, $entity, $params = null)
    {
        parent::__construct(null, $target, $params);
        $this->setParam('entity', $entity);
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        $entity = $this->getParam('entity');
        if ($entity instanceof GetChangesFeatureInterface) {
            return $entity->getChanges();
        } else {
            throw new Exception\UnsupportedException(
                ExceptionTools::msg('entity %s does not supported GetChangesFeature', $entity)
            );
        }
    }

    /**
     * Run passed function when fieldName is changed.
     * @param string $fieldName
     * @param \Closure $fn
     */
    public function onChange($fieldName, \Closure $fn)
    {
        $ch = $this->getChanges();
        if (array_key_exists($fieldName, $ch)) {
            $fn($ch[$fieldName]['new'], $ch[$fieldName]['old']);
        }
    }
}