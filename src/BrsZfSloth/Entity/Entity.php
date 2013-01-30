<?php

namespace BrsZfSloth\Entity;

use Serializable;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use BrsZfSloth\Repository\RepositoryAwareInterface;
use BrsZfSloth\Definition\DefinitionAwareInterface;
use BrsZfSloth\Entity\Feature\OriginValuesFeatureInterface;
use BrsZfSloth\Entity\Feature\GetChangesFeatureInterface;
use BrsZfSloth\Entity\Feature\PopulateFeatureInterface;
use BrsZfSloth\Entity\Feature\RepositoryOperationsFeatureInterface;

class Entity implements
    EntityTraitInterface,
    RepositoryAwareInterface,
    DefinitionAwareInterface,
    OriginValuesFeatureInterface,
    GetChangesFeatureInterface,
    PopulateFeatureInterface,
    RepositoryOperationsFeatureInterface,
    Serializable,
    ServiceManagerAwareInterface
{
    use EntityTrait;
}