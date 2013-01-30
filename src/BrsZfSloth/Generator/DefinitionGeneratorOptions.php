<?php
namespace BrsZfSloth\Generator;

use Zend\Stdlib\AbstractOptions;
use Zend\Db\Adapter\Adapter as DbAdapter;

use BrsZfSloth\Sloth;
use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Options as DefaultOptions;
use BrsZfSloth\Generator\Descriptor\AbstractDescriptor;
use BrsZfSloth\Definition\Definition;

class DefinitionGeneratorOptions extends AbstractOptions
{
    protected $defaultOptions;
    protected $dbAdapter;
    protected $descriptor;
    protected $savePath;
    protected $ignoredTables = [];
    protected $allowReplaceExistingConfig = false;
    protected $fullRebuildExistingConfig = false; // false nothing to do (see rebuildFieldsExistingConfig), true replace all config
    protected $rebuildFieldsExistingConfig = false; // false add not existing fields, true replace all

    public function __construct($options = null, DefaultOptions $defaultOptions = null)
    {
        parent::__construct($options);
        if (null !== $defaultOptions) {
            $this->setDefaultOptions($defaultOptions);
        }
    }

    public function setDefaultOptions(DefaultOptions $defaultOptions)
    {
        $this->defaultOptions = $defaultOptions;
        return $this;
    }

    public function getDefaultOptions()
    {
        if (null === $this->defaultOptions) {
            $this->setDefaultOptions(Sloth::getOptions());
        }
        return $this->defaultOptions;
    }

    public function setDbAdapter(DbAdapter $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
        return $this;
    }

    public function getDbAdapter()
    {
        if (null === $this->dbAdapter) {
            $this->setDbAdapter(
                $this->getDefaultOptions()->getDefaultDbAdapter()
            );
            // throw new Exception\RuntimeException(
            //     'db adapter not set'
            // );
        }
        return $this->dbAdapter;
    }

    public function setDescriptor(AbstractDescriptor $descriptor)
    {
        $this->descriptor = $descriptor;
        return $this;
    }

    public function getDescriptor()
    {
        if (null === $this->descriptor) {
            $this->descriptor = AbstractDescriptor::factory($this->getDbAdapter());
        }
        return $this->descriptor;
    }

    public function setSavePath($path)
    {
        $path = realpath($path);

        if (! $path || ! is_dir($path) || ! is_writeable($path)) {
            throw new Exception\InvalidArgumentException(
                ExceptionTools::msg('directory %s not exists or is not writable', $path)
            );
        }
        $this->savePath = $path;
        return $this;
    }

    public function getSavePath()
    {
        if (null === $this->savePath) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('save path not set')
            );
        }
        return $this->savePath;
    }

    public function addIgnoredTable($table)
    {
        if (false === strpos($table, '.')) {
            $table = Definition::DEFAULT_SCHEMA . '.' . $table;
        }
        $this->ignoredTables[] = $table;
        return $this;
    }

    public function setIgnoredTables(array $tables)
    {
        array_walk($tables, function($table) {
            $this->addIgnoredTable($table);
        });
        return $this;
    }

    public function getIgnoredTables()
    {
        return array_unique(array_merge(
            $this->getDefaultOptions()->getDefinitionGeneratorIgnoredDbTables(),
            $this->ignoredTables
        ));
    }

    public function setAllowReplaceExistingConfig($flag)
    {
        $this->allowReplaceExistingConfig = (bool) $flag;
        return $this;
    }

    public function getAllowReplaceExistingConfig()
    {
        return $this->allowReplaceExistingConfig;
    }

    public function setFullRebuildExistingConfig($flag)
    {
        $this->fullRebuildExistingConfig = (bool) $flag;
        return $this;
    }

    public function getFullRebuildExistingConfig()
    {
        return $this->fullRebuildExistingConfig;
    }

    public function setRebuildFieldsExistingConfig($flag)
    {
        $this->rebuildFieldsExistingConfig = (bool) $flag;
        return $this;
    }

    public function getRebuildFieldsExistingConfig()
    {
        return $this->rebuildFieldsExistingConfig;
    }
}