<?php
namespace BrsZfSloth\Controller;

use __;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Prompt\Line;
use Zend\Console\Prompt\Confirm;
use Zend\Console\Prompt\Select;

use BrsZfSloth\Sloth;
use BrsZfSloth\Exception;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Generator\DefinitionGenerator;

class GeneratorController extends AbstractActionController
{
    protected $skipExisting = false;

    public function initdbAction()
    {
        $this->skipExisting = $this->params('skip-existing') || $this->params('s');

        $dbAdapter = Sloth::getOptions()->getDefaultDbAdapter();

        if (! (new Confirm(sprintf(
            'Do you want generate definitions for dsn %s? [y|n]',
            $dbAdapter->getDriver()->getConnection()->getConnectionParameters()['dsn']
            )))->show()) {

            return "Nothing generated!\n";
        }

        $generator = $this->getDefinitionGenerator();

        while (true) {
            $schema = (new Line(
                sprintf('Enter schema name to init sloth definitions (if blank then %s): ', Definition::DEFAULT_SCHEMA),
                true
            ))->show();

            $tables = $generator->getOptions()->getDescriptor()->describeDatabase($schema ?: Definition::DEFAULT_SCHEMA);

            if (empty($tables)) {
                if (! (new Confirm('Not found any table in schema. You want to try again? [y|n]'))->show()) {
                    return "Nothing generated!\n";
                }
            } else {
                break;
            }
        }

        print "\ntable found in the schema:\n";
        array_walk($tables, function($v) use ($generator) {
            if (! $generator->isIgnoredTable($v['schema'], $v['table'])) {
                printf("%s.%s\n", $v['schema'], $v['table']);
            }
        });
        print "\n";

        if (! (new Confirm('Do you want generate definitions for above tables? [y|n]'))->show()) {
            return "Nothing generated!\n";
        }

        array_walk($tables, function($v) use ($generator) {
            if (! $generator->isIgnoredTable($v['schema'], $v['table'])) {
                print $this->generateDefinition($generator, $v['schema'], $v['table']);
                print "\n";
            }
        });
    }

    public function generateTableDefinitionAction()
    {

        if(is_string($this->params('table'))) {
            $table = $this->params('table');
            if (false === strpos($table, '.')) {
                $table = sprintf('%s.%s', Definition::DEFAULT_SCHEMA, $table);
            }

            // check if table belong to the module and if true then use save path from module
            $module = __::find(Sloth::getOptions()->getModules(), function($module) use ($table) {
                return in_array($table, $module->getDbTables());
            });
            if ($module) {
                $generatorOptions = [
                    'save_path' => $module->getDefinitionsPath()
                ];
            }

            $generator = $this->getDefinitionGenerator(isset($generatorOptions) ? $generatorOptions : []);

        } else {
            $generator = $this->getDefinitionGenerator();
            $schema = (new Line(
                sprintf('Enter schema name to show tables (if blank then %s): ', Definition::DEFAULT_SCHEMA),
                true
            ))->show();

            $tables = [];
            array_walk(
                $generator->getOptions()->getDescriptor()->describeDatabase($schema ?: Definition::DEFAULT_SCHEMA),
                function($v) use (&$tables) {
                    $tables[] = sprintf('%s.%s', $v['schema'], $v['table']);
                }
            );
            $table = $tables[(new Select('Select table: ', $tables))->show()];
        }

        list($schema, $table) = explode('.', $table);
        return $this->generateDefinition($generator, $schema, $table);

    }

    protected function generateDefinition(DefinitionGenerator $generator, $schema, $table)
    {
        $generator = clone $generator;

        printf("\ngenerating definition for table %s.%s\n", $schema, $table);
        $definition = $generator->generateDefinition($table, $schema);
        printf("definition generated: %s\n", $definition);

        while (true) {
            try {
                $fileName = $generator->getFileName($definition);
                $generator->saveDefinition($definition);
                return sprintf("definition saved in %s\n", $fileName);

            } catch (Exception\OperationNotPermittedException $e) {
                if ($this->skipExisting) {
                    return sprintf("definitnion exists in %s - saving canceled", $fileName);
                }

                printf("ATTENTION!!! Definition exists in %s\n", $fileName);
                $chose = new Select("What you want to do with existing definition config?", [
                    'a' => 'Add new fields',
                    'e' => 'rEbuild existing fields',
                    'r' => 'Replace all config',
                    'c' => 'Cancel operation',
                ]);


                switch ($chose->show()) {
                    case 'c':
                        return "saving canceled!\n";
                    case 'a':
                        // nothing to do, this is default action
                        break;
                    case 'r':
                        $generator->getOptions()->setFullRebuildExistingConfig(true);
                        break;
                    case 'e':
                        $generator->getOptions()->setRebuildFieldsExistingConfig(true);
                        break;
                }
                // allow replace existing config
                $generator->getOptions()->setAllowReplaceExistingConfig(true);
                // try again
                continue;
            }
            break;
        };
    }

    protected function getDefinitionGenerator(array $options = array())
    {
        $generator = new DefinitionGenerator(array_merge([
            'allowReplaceExistingConfig' => false,
            'fullRebuildExistingConfig' => false,
            'rebuildFieldsExistingConfig' => false,
        ], $options));

        try {
            $generator->getOptions()->getSavePath();
        } catch (Exception\RuntimeException $e) {
            $savePath = Sloth::getOptions()->getDefinitionsPaths();
            if (1 === count($savePath)) {
                $savePath = $savePath[0];
            } else {
                $savePath = $savePath[(new Select('Select path to save definitions:', $savePath))->show()];
            }

            if (! file_exists($savePath)) {
                if (! (new Confirm(sprintf('Directory %s/%s not exists. Do you want create it? [y|n]', getcwd(), $savePath)))->show()) {
                    return "Nothing generated!\n";
                }
                $umask = umask(0);
                mkdir($savePath, 0755, true);
                umask($umask);
            }

            $generator->getOptions()->setSavePath($savePath);
        }
        return $generator;
    }
}