<?php
namespace BrsZfSloth\Repository;

interface RepositoryAwareInterface
{
    public function setRepository(Repository $repository);
}