<?php
namespace BrsZfSloth\Repository;

use BrsZfSloth\Definition\DefinitionAwareInterface;
use BrsZfSloth\Sql\Where;
use BrsZfSloth\Sql\Expr;

interface RepositoryInterface extends DefinitionAwareInterface
{
    /**
     * Add new record
     *
     * @param   $entity
     * @return  integer|false Id nowo dodanego rekordu lub false w razie niepowodzenia
     */
    public function insert($entity);

    /**
     * @param   $entity
     * @param   string $by Pole po którym ma zostać zrobiona aktualizacja
     * @return  integer Affected rows
     */
    public function update($entity);

    /**
     * @param $entity
     * @return integer Affected rows
     */
    public function delete($entity);

    /**
     * @return integer affected rows
     */
    public function deleteAll();

    /**
     * @return integer
     */
    public function count(Where $where = null);

    public function getDsn();
    public function getByUnique($entity, $uniqueKeyName);
    public function insertOrGet($entity, $uniqueKeyName);

    /**
     * Return similar entity
     * @param $entity
     * @return colection
     */
    public function fetchSimilar($entity);

    /**
     * Check whether entity exists in repository
     * @param $entity
     * @return bool
     */
    public function isNew($entity);

    public function save($entity);

    public function get($where, $val = Expr::UNDEFINED);

    public function fetch($where = null, $val = Expr::UNDEFINED);


    public function factoryEntity(array $data = array());
    public function factoryCollection(array $rows = array());

    // public function get($where);
    // public function getBy($field, $value);
    // public function getMethod($field, $value);
    // public function fetch($where);
    // public function fetchBy($where);
    // public function fetchMethod($where);

    //public function quote($value, $type = null);
}