<?php

namespace BrsZfSlothTest\Entity;

use BrsZfSloth\Sloth;
use BrsZfSloth\Definition\Definition;

/**
 * @group BrsZfSloth
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Sloth::reset();
        Definition::reset();
    }

    // TODO tests:
    // toarray

    /*
     * @expectedException BrsZfSloth\Exception\DefinitionConfigNotFoundException
     * @expectedExceptionMessage definition config not found for name "givenDefinitionName" (paths: )
     */
    // public function _testDefinitionPrior()
    // {
    //     EntityTools::getDefinition(
    //         $this->getMock('BrsZfSloth\Definition\DefinitionAwareInterface'),
    //         'givenDefinitionName'
    //     );
    // }

    public function testMarkAsOrigin()
    {
        $definition = new Definition([
            'name' => 'test',
            'table' => 'test',
            'hydratorClass' => 'BrsZfSloth\Hydrator\Hydrator',
            'entityClass' => 'BrsZfSloth\Entity\Entity',
            'fields' => [
                'id' => 'integer',
                'name' => 'text',
            ]
        ]);

        $entity = (new TestAsset\TestEntitySloth)
            ->setDefinition($definition)
            ->populate([
                'id' => 1,
                // 'name' => null,
            ])
            // ->markAsOrigin()
        ;

        $this->assertEquals(
            [
                'id' => ['new' => 1],
                'name' => ['new' => null]
            ],
            $entity->getChanges()
        );

        $this->assertEquals(
            [],
            $entity->markAsOrigin()->getChanges()
        );

        $this->assertEquals(
            [
                'name' => ['new' => 'y', 'old' => null]
            ],
            $entity->setName('y')->getChanges()
        );

        $this->assertEquals(
            [
                'name' => ['new' => null, 'old' => 'y']
            ],
            $entity->markAsOrigin()->setName(null)->getChanges()
        );
    }

    public function testSerializeWithDefinition()
    {
        $definition = new Definition([
            'name' => 'test',
            'table' => 'test',
            'hydratorClass' => 'BrsZfSloth\Hydrator\Hydrator',
            'entityClass' => 'BrsZfSloth\Entity\Entity',
            'fields' => [
                'id' => 'integer',
                'name' => 'text',
            ]
        ]);

        $entity = (new TestAsset\TestEntitySloth)
            ->setDefinition($definition)
            ->populate([
                'id' => 1,
                // 'name' => null,
            ])
            // ->markAsOrigin()
        ;

        $serialEntity = serialize($entity);
        // must be serialize with whole object definition
        $this->assertTrue(is_int(strpos($serialEntity, 'BrsZfSloth\Definition\Definition')));

        $unserialEntity = unserialize($serialEntity);
        $this->assertEquals(1, $unserialEntity->getId());
        $this->assertInstanceOf(get_class($definition), $entity->getDefinition());

    }

    public function testSerializeWithDefinitionComesFromFile()
    {
        Sloth::getOptions()->addDefinitionsPath(__DIR__ . '/TestAsset');
        $definition = Definition::getCachedInstance('testDefinition');

        $entity = (new TestAsset\TestEntitySloth)
            ->setDefinition($definition)
            ->populate([
                'id' => 1
            ])
            // ->markAsOrigin()
        ;

        $serialEntity = serialize($entity);

        // must be serialize only name of definition
        $this->assertTrue(is_int(strpos($serialEntity, 'testDefinition')));

        $unserialEntity = unserialize($serialEntity);
        $this->assertEquals(1, $unserialEntity->getId());
        $this->assertInstanceOf(get_class($definition), $entity->getDefinition());
    }

    // public function testGetChanges()
    // {
    //     $definition = new Definition([
    //         'name' => 'test',
    //         'table' => 'test',
    //         'hydratorClass' => 'BrsZfSloth\Hydrator\Hydrator',
    //         'entityClass' => 'BrsZfSloth\Entity\Entity',
    //         'fields' => [
    //             'id' => 'integer',
    //             'firstName' => 'text',
    //             'lastName' => 'text',
    //         ]
    //     ]);

    //     $entity = (new TestAsset\TestEntitySloth)
    //         ->setDefinition($definition)
    //         ->populate([
    //             'id' => 1,
    //             'firstName' => 'x',
    //             'lastName' => 'y',
    //         ])
    //         ->markAsOrigin()
    //     ;
    //     $entity->setLastName(null);
    //     // mprd($entity->toArray());
    //     // mprd($entity->getChanges());
    //     mpr($entity->setFirstName('y')->getChanges());
    //     mprd($entity->getChanges());
    //     $this->assertEmpty($entity->getChanges());
    //     $this->assertEquals(['firstName' => ['new' => 'y', 'old' => 'x']], $entity->setFirstName('y')->getChanges());
    //     $this->assertEmpty($entity->markAsOrigin()->getChanges());
    //     $this->assertEquals(['lastName' => ['new' => 'y']], $entity->setLastName('y')->getChanges());
    // }
}