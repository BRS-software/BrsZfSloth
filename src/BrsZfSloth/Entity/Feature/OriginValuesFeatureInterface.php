<?php
namespace BrsZfSloth\Entity\Feature;

interface OriginValuesFeatureInterface
{
    /**
     * @param array $originValues For performance repository apply originValues directly from db (i.e. without use internal toArray() method in BrsZfSloth\Entity\Entity)
     *                            Remember, keys must be entity fields, not db fields: ['id' => 1, 'firstName' => 'tom']
     * @return OriginValuesInterface
     */
    public function markAsOrigin(array $originValues = null);

    /**
     * @return array
     */
    public function getOriginValues();
}