<?php
namespace BrsZfSloth\Repository;

interface CacheableResultInterface
{
    public function getCacheId();
    public function getExceptionQuery();
    public function __invoke();
}