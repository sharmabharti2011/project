<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Measurements
 *
 * @Entity @Table(name="measurements")
 * 
 */
class Measurements
{
    /**
     * @var integer
     *
     * @Id @GeneratedValue @Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="measurement", type="string", length=128, nullable=true)
     */
    private $measurement;


    public function toArray(){
        $array = get_object_vars($this);
        unset($array['_parent'], $array['_index']);
        array_walk_recursive($array, function(&$property, $key){
            if(is_object($property)
            && method_exists($property, 'toArray')){
                $property = $property->toArray();
            }
        });
        return $array;
    }
    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set measurement
     *
     * @param string $measurement
     * @return Measurements
     */
    public function setMeasurement($measurement)
    {
        $this->measurement = $measurement;

        return $this;
    }

    /**
     * Get measurement
     *
     * @return string 
     */
    public function getMeasurement()
    {
        return $this->measurement;
    }
}
