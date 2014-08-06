<?php

/**
 * (c) BRS software - Tomasz Borys <t.borys@brs-software.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TreeItem;

/**
 * @author Tomasz Borys <t.borys@brs-software.pl>
 * @version 1.0 2014-06-03
 */
trait TreeItemTrait
{
    public function getNLevel()
    {
        return count(explode($this->getPath()));
    }
}