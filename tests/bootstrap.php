<?php
chdir(__DIR__);
require '../../brs-stdlib/src/Brs/Stdlib/TestSuite/BootstrapHelper.php';
Brs\Stdlib\TestSuite\BootstrapHelper::findComposerAutoloader(__DIR__);