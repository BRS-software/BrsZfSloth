<?php

namespace BrsZfSloth\Entity\Feature;

interface RepositoryOperationsFeatureInterface
{
    /**
     * @return RepositoryOperationsFeatureInterface
     */
    public function save();

    /**
     * @return RepositoryOperationsFeatureInterface
     */
    public function delete();
}