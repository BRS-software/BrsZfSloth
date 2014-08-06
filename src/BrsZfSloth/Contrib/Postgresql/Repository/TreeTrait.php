<?php

/**
 * (c) BRS software - Tomasz Borys <t.borys@brs-software.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrsZfSloth\Contrib\Postgresql\Repository;

use Closure;
use Zend\Db\Sql\Sql;
use BrsZfSloth\Sql\Where;
use BrsZfSloth\Exception;

/**
 * @author Tomasz Borys <t.borys@brs-software.pl>
 * @version 1.0 2014-06-03
 */
trait TreeTrait
{
    public function getPathFieldName()
    {
        return 'path';
    }

    public function getPathIndexMaxLength()
    {
        return 4;
    }

    public function fetchChildren($node, $depthLevel = null, Closure $selectFn = null, array $options = [])
    {
        $options = array_merge([
            // 'onTheSameLevel' => false, // if you want only children
            // 'depthLevel' => false, // set integer when you want define depth level
            // 'defaultOrder' => true, // when you want to use own sorting set this value on false and set own sorting in $selectFn closure
        ], $options);

        return $this->fetch(function ($select, $c) use ($node, $depthLevel, $selectFn)
        {
            $select
                // ->where(new Where('{' . $this->getPathFieldName() . '} <@ :? AND {' . $this->getPathFieldName() . '}<>:?', [$node, $node]))
                ->where(new Where('{' . $this->getPathFieldName() . '} <@ :?', [$node]))
                ->where(new Where('{' . $this->getPathFieldName() . '} <> :?', [$node])) // exclude parent node from result
                ->reset('order')
                ->order($c('{' . $this->getPathFieldName() . '} ASC'))
            ;
            if (is_numeric($depthLevel)) {
                $select
                    // ->where(new Where("nlevel({path}) = (nlevel(:?)+1)", [$path]))
                    ->where(new Where("nlevel({path}) = (nlevel(:?)+:?)", [$node, $depthLevel]));
            }
            if ($selectFn) {
                $selectFn($select, $c);
            }
            // dbgd($select->reset('columns')->getSqlString($this->getAdapter()->getPlatform()));
        });
    }

    public function getLastChild($node, Closure $selectFn = null, array $options = [])
    {
        $options = array_merge([
            'throwException' => true,
        ], $options);

        $coll = $this->fetchChildren($this->buildPath($node), 1, function ($select, $c) use ($selectFn) {
            $select
                ->reset('order')
                ->order($c('{' . $this->getPathFieldName() . '} desc'))
                ->limit(1)
            ;
            if ($selectFn) {
                $selectFn($select, $c);
            }
        }, $options);

        if ($coll->isNotEmpty()) {
            return $coll->getFirst();
        }
        if ($options['throwException']) {
            throw new Exception\NotFoundException(
                sprintf('Node %s not exists or doesn\'t have any childeren', $node)
            );
        }
        return null;
    }

    public function removeChildren($node, Closure $deleteFn = null)
    {
        $delete = (new \Zend\Db\Sql\Delete())
            ->where(new Where('{' . $this->getPathFieldName() . '} <@ :?', [$node], $this))
            ->where(new Where('{' . $this->getPathFieldName() . '} <> :?', [$node], $this))
        ;

        if (null !== $deleteFn) {
            $deleteFn($delete, $this->getConventer());
        }

        return $this->prepareStatement($delete)->execute();
    }


    public function getNextChildPath($node, Closure $selectFn = null, array $options = [])
    {
        // $path = $this->buildPath($node);
        try {
            $path = $this->getLastChild($node, $selectFn, $options)->get($this->getPathFieldName());
            return $this->getPathWithoutIndex($path) . '.' . $this->buildIndex((int) $this->getIndexFromPath($path) + 1);
        } catch (Exception\NotFoundException $e) {
            return $this->buildPath($node) . '.' . $this->buildIndex(1);
        }
    }

    public function buildIndex($index)
    {
        return sprintf('%0' . $this->getPathIndexMaxLength() . 'd', (int) $index);
    }

    public function buildPath($path)
    {
        $result = [];
        foreach (explode('.', $path) as $v) {
            $result[] = is_numeric($v) ? $this->buildIndex($v) : $v;
        }
        return implode('.', $result);
    }

    public function getPathWithoutIndex($path)
    {
        return substr($path, 0, strrpos($path, '.'));
    }

    public function getIndexFromPath($path)
    {
        return substr($path, strrpos($path, '.') + 1);
    }
}