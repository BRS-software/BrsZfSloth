<?php

/**
 * (c) BRS software - Tomasz Borys <t.borys@brs-software.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrsZfSloth\Contrib\Tree;

use Closure;

/**
 * @author Tomasz Borys <t.borys@brs-software.pl>
 * @version 1.0 2014-07-09
 */
trait NodeCollectionTrait
{
    public function toArrayTree(Closure $dataFn, $currDepth = 1, $key = 'children', &$__lastItem = 0)
    {
        $tree = [];
        for ($i = &$__lastItem; $i < count($this); $i++) {
            $nl = $this[$i]->getNLevel();
            $item = $dataFn($this[$i]);
            $item[$key] = [];
            if ($nl === $currDepth + 1) {
                $tree[] = $item;
            } elseif ($nl === $currDepth + 2) {
                $tree[max(array_keys($tree))][$key] = $this->toArrayTree($dataFn, $currDepth + 1, $key, $i);
            } elseif ($nl > $currDepth + 2) {
                throw new \LogicException('invalid tree struct for item ');
            } else {
                $i--;
                break;
            }
        }
        return $tree;
    }
}