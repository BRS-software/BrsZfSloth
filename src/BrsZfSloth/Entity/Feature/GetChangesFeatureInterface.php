<?php

namespace BrsZfSloth\Entity\Feature;

interface GetChangesFeatureInterface
{
    /**
     * @return array('changedFieldName' => array(new' => 'new_value'[, 'old' => 'old_value']))
     */
    public function getChanges();
}