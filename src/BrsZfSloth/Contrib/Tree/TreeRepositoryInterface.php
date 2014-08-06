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
 * @version 1.0 2014-07-04
 */
interface TreeRepositoryInterface
{
    public function getPathFieldName();
    public function fetchChildren($node, $depthLevel = null, Closure $selectFn = null, array $options = []);
    public function getLastChild($node, Closure $selectFn = null, array $options = []);
    public function getNextChildPath($node, Closure $selectFn = null, array $options = []);
}