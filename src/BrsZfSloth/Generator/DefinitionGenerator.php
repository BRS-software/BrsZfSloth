<?php
namespace BrsZfSloth\Generator;

use Closure;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\DefinitionAwareInterface;
use BrsZfSloth\Definition\DefinitionTools;
use BrsZfSloth\Definition\DefinitionOptions;
use BrsZfSloth\Definition\Field;
// use BrsZfSloth\Entity\Entity;
// use BrsZfSloth\Entity\Feature\GetChangesFeatureInterface;

class DefinitionGenerator
{
    protected $options;

    public function __construct($options = null)
    {
        $this->options = new DefinitionGeneratorOptions($options);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function generateDefinitionConfig($tableName, $tableSchema = Definition::DEFAULT_SCHEMA)
    {
        // $def = new Definition([
        //     'name' => 'testname',
        //     'schema' => 'public',
        //     'table' => 'testtable',
        //     'entityClass' => get_class($entityClass),
        //     'collectionClass' => get_class($collectionClass),
        //     'hydratorClass' => 'Zend\Stdlib\Hydrator\ClassMethods',
        //     'defaultOrder' => ['name' => SORT_DESC],
        //     'fields' => [
        //         'id' => 'integer',
        //         'firstName' => 'text',
        //     ]
        // ]);

        $fieldsConfig = $this->getOptions()->getDescriptor()->describeTable($tableName, $tableSchema);
        // foreach ($this->getDescriptor()->describeTable($tableName, $tableSchema) as $field) {
        //     $fieldsConfig[] = $field;
        // }

        $definitionConfig = [
            'name' => $this->askViaConsole(
                $this->getDefinitionName($tableSchema, $tableName),
                function($console) use ($tableName) {}
            ),
            'schema' => $tableSchema,
            'table' => $tableName,
            'entityClass' => $this->askViaConsole(
                'BrsZfSloth\Entity\Entity',
                function($console) {}
            ),
            'collectionClass' => $this->askViaConsole(
                'BrsZfSloth\Collection\Collection',
                function($console) {}
            ),
            'hydratorClass' => $this->askViaConsole(
                'BrsZfSloth\Hydrator\Hydrator',
                function($console) {}
            ),
            'defaultOrder' => $this->askViaConsole(
                ['id' => SORT_ASC],
                function($console) use ($fieldsConfig) {}
            ),
            'fields' => $fieldsConfig,
        ];

        return $definitionConfig;
    }

    public function generateDefinition($tableName, $tableSchema = Definition::DEFAULT_SCHEMA)
    {
        return new Definition(
            $this->generateDefinitionConfig($tableName, $tableSchema)
        );
    }

    public function saveDefinition($definition)
    {
        $newDefinition = $this->getDefinitionIncludingOptions(
            DefinitionTools::getDefinitionFromParam($definition)
        );

        $fileName = sprintf('%s/%s.json', $this->getOptions()->getSavePath(), $newDefinition->getName());
        $result = file_put_contents(
            $fileName,
            json_encode($newDefinition->export(), JSON_PRETTY_PRINT)
        );
        return $fileName;
    }

    protected function getDefinitionIncludingOptions(Definition $newDefinition)
    {
        try {
            $oldDefinition = new Definition(
                Definition::discoverConfig(
                    $newDefinition->getName(),
                    $this->getOptions()->getSavePath()
                )
            );

            if (! $this->getOptions()->getAllowReplaceExistingConfig()) {
                throw new Exception\OperationNotPermittedException(
                    ExceptionTools::msg('replace existing definition config %s is not permitted, see option DefinitionGeneratorOptions::allowReplaceExistingConfig', $oldDefinition->getOriginFile())
                );
            }

        } catch (Exception\DefinitionConfigNotFoundException $e) {
            return $newDefinition;
        }

        if ($this->getOptions()->getFullRebuildExistingConfig()) {
            return $newDefinition;
        }

        // rebuild all fields definition
        if ($this->getOptions()->getRebuildFieldsExistingConfig()) {
            $oldDefinition->resetFields();
            foreach ($newDefinition as $newField) {
                $oldDefinition->addField($newField);
            }

        // if not force rebuild fileds, it will only add new fields to existing config
        } else {
            foreach ($newDefinition as $newField) {
                if (! $oldDefinition->hasField($newField->getName())) {
                    $oldDefinition->addField($newField);
                }
            }
        }
        return $oldDefinition;
    }

    protected function askViaConsole($defaultValue, Closure $fn)
    {
        return $defaultValue;
    }

    protected function getDefinitionName($schema, $table)
    {
        if (Definition::DEFAULT_SCHEMA !== $schema) {
            $name = sprintf('%s.%s', $schema, $table);
        } else {
            $name = $table;
        }
        return DefinitionTools::transformUnderscoreToCamelCase($name);
    }
}