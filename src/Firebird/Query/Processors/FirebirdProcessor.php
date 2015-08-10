<?php namespace Firebird\Query\Processors;

use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Builder;

class FirebirdProcessor extends Processor {
  /**
   * Process an "insert get ID" query.
   *
   * @param  \Illuminate\Database\Query\Builder  $query
   * @param  string  $sql
   * @param  array   $values
   * @param  string  $sequence
   * @return int
   */
  public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
  {
    $results = $query->getConnection()->selectFromWriteConnection($sql, $values);

    $sequence = $sequence ?: 'id';

    $result = (array) $results[0];

    $id = $result[$sequence];

    return is_numeric($id) ? (int) $id : $id;
  }

}