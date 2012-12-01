<?php

namespace BrsZfSloth\Entity\Feature;

interface PopulateFeatureInterface
{
    /**
     * @param array $values
     * @return PopulateInterface
     */
    public function populate(array $values);
}