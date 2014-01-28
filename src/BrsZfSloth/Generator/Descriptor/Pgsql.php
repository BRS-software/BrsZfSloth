<?php
namespace BrsZfSloth\Generator\Descriptor;

// use BrsZfSloth;
use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Definition\Field;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\DefinitionTools;

class Pgsql extends AbstractDescriptor
{
    protected static $typesMap = [
        'timestamp without time zone' => Field::TYPE_TIMESTAMP,
        'timestamp with time zone' => Field::TYPE_TIMESTAMP,
        'date' => Field::TYPE_DATE,
        'integer' => Field::TYPE_INTEGER,
        'character varying' => Field::TYPE_CHARACTER_VARYING,
        'character' => Field::TYPE_CHARACTER_VARYING,
        'boolean' => Field::TYPE_BOOLEAN,
        'text' => Field::TYPE_TEXT,
        'array' => Field::TYPE_ARRAY,
        'smallint' => Field::TYPE_SMALLINT,
        'numeric' => Field::TYPE_NUMERIC,
        'user-defined' => Field::TYPE_CHARACTER_VARYING,
        'double precision' => Field::TYPE_DOUBLE_PRECISION,
    ];

    public function describeDatabase($schema = Definition::DEFAULT_SCHEMA)
    {
        $query = $this->adapter->query(
            sprintf(
                "SELECT * FROM pg_tables where schemaname='%s' order by tablename",
                $schema
            )
        )->execute();

        $tables = [];
        foreach ($query as $v) {
            $tables[] = [
                'schema' => $v['schemaname'],
                'table' => $v['tablename'],
            ];
        }
        return $tables;
    }

    public function describeTable($tableName, $schema = Definition::DEFAULT_SCHEMA)
    {
        $query = $this->adapter->query(
            sprintf(
                "SELECT * from information_schema.columns where table_catalog='%s' AND table_schema='%s' AND table_name = '%s' order by ordinal_position",
                $this->getDbName(),
                $schema,
                $tableName
            )
        )->execute();

        if (0 === count($query)) {
            throw new Exception\NotFoundException(
                ExceptionTools::msg('table %s.%s not found in %s', $schema, $tableName, $this->getDsn())
            );
        }

        $tableData = [];

        /*
        $field = new Field('test', [
            // 'type' => 'int'
            // 'type' => 'character varying',
            'type' => 'character varying(16)',
            'default' => 'x',
            'mapping' => 'test_mapping',
            'notNull' => true,
            'primary' => true,
        ]);
        */
        foreach ($query as $f) {
            $f['data_type'] = strtolower($f['data_type']);

            if (! isset(self::$typesMap[$f['data_type']])) {
                throw new Exception\UnmappedException(
                    ExceptionTools::msg('unmapped data type "%s" in %s', $f['data_type'], $this)
                );
            }

            $name = DefinitionTools::transformUnderscoreToCamelCase($f['column_name']);
            $config = [
                'type' => self::$typesMap[$f['data_type']],
                'default' => $f['column_default'],
                'mapping' => $f['column_name'],
                'notNull' => $f['is_nullable'] === 'NO',
                'primary' => false,
            ];

            // XXX how detect primary?
            if (preg_match('/^nextval/', $f['column_default'])) {
                $name = 'id';
                $config['default'] = null;
                $config['primary'] = true;
                $config['sequence'] = $f['column_default'];
            }

            // assert params
            if (Field::TYPE_CHARACTER_VARYING === $config['type']) {
                $config['assertParams'] = [$f['character_maximum_length']];
            }

            $tableData['fields'][] = new Field($name, $config);
        }
        // mprd($fields);
        return $tableData;
    }
}