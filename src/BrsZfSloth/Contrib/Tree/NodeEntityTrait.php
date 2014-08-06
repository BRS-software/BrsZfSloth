<?php

/**
 * (c) BRS software - Tomasz Borys <t.borys@brs-software.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrsZfSloth\Contrib\Tree;

use BrsZfSloth\Exception;

/**
 * @author Tomasz Borys <t.borys@brs-software.pl>
 * @version 1.0 2014-07-04
 */
trait NodeEntityTrait
{
    public function getNLevel()
    {
        return count(explode('.', $this->getPath()));
    }

    public function isRoot()
    {
        return 1 === $this->getNLevel();
    }

    public function getParentPath()
    {
        $path = $this->getPath();
        return substr($path, 0, strrpos($path, '.'));
    }
}