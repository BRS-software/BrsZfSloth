<?php

/**
 * (c) BRS software - Tomasz Borys <t.borys@brs-software.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrsZfSloth\Contrib\Tree;

/**
 * @author Tomasz Borys <t.borys@brs-software.pl>
 * @version 1.0 2014-07-04
 */
interface NodeInterface
{
    public function getPath();
    public function getNLevel();
    public function isRoot();
    public function getParent();
    public function addChild(NodeInterface $child, $at = null);
    public function fetchChildren(\Closure $selectFn = null, array $options = []);
    public function removeChildren(\Closure $deleteFn = null);
    public function fetchSubItems($depthLevel = null, \Closure $selectFn = null, array $options = []);
}