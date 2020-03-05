<?php namespace Firebird\Query\Processors;

use Illuminate\Database\Query\Processors\Processor;

class FirebirdProcessor extends Processor {

    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results) {
        array_walk($results, function(&$object) {
            $array = (array) $object;
            $object = trim($array['RDB$FIELD_NAME']);
        });
        return $results;
    }

}