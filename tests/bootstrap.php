<?php
chdir(__DIR__);

// try use composer autload file
$findUpMaxDepth = 2;
$path = __DIR__;
while ($findUpMaxDepth--) {
    $path .= '/..';
    $autoloadFile = $path . '/vendor/autoload.php';
    if (file_exists($autoloadFile)) {
        include $autoloadFile;
        break;
    }
}
// check if ZF loaded
if (! class_exists('Zend\Version\Version')) {
    throw new RuntimeException('Zend Framework 2 not found. Run first ./composer.phar install');
}

// (new Zend\Loader\StandardAutoloader(
//     array(
//         Zend\Loader\StandardAutoloader::LOAD_NS => array(
//             'Brs\Zend'     => __DIR__ . '/../src/Brs/Zend',
//             'BrsTest\Zend' => __DIR__ . '/BrsTest/Zend',
//         ),
//     )
// ))->register();


// simple php debuger
function mpr($val, $isXml = false, $_traceRewind = 0) {
    if($isXml) {
        header("content-type: text/xml");
        die($val);
    }
    if(!headers_sent()) {
        header("content-type: text/plain");
    }
    if (is_array($val) || is_object($val)) {
        print_r($val);

        if(is_array($val))
            reset($val);
    } else {
        var_dump($val);
    }
    $trace = debug_backtrace();
    echo sprintf("^--Who called me: %s line %s\n\n", $trace[$_traceRewind]['file'], $trace[$_traceRewind]['line']);
}
function mprd($val, $isXml = false) {
    mpr($val, $isXml, 1);
    die("die!\n\n");
}