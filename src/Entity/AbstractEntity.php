<?php
namespace HerokuHC\Entity;

/**
 * Abstract Entity class
 */
abstract class AbstractEntity
{
    /**
     * To convert array of object variables
     * @return array
     */
    public function toArray() : array
    {
        return get_object_vars($this);
    }
}
