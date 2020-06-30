<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="supplyCategories")
 */
class Supplycategories
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
     * @Column(name="supplyCategory", type="string", length=128, nullable=true)
     */
    private $supplyCategory;

    /**
     * @var string
     *
     * @Column(name="image", type="string", length=128, nullable=true)
     */
    private $image;

    /**
     * @var integer
     *
     * @Column(name="percentage", type="integer", nullable=true)
     */
    private $percentage;

    /**
     * @Column(type="integer")
     * @var integer
     */
    private $fk_userId;


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
     * Set supplyCategory
     *
     * @param string $supplyCategory
     * @return Supplycategories
     */
    public function setSupplyCategory($supplyCategory)
    {
        $this->supplyCategory = $supplyCategory;

        return $this;
    }

    /**
     * Get supplyCategory
     *
     * @return string 
     */
    public function getSupplyCategory()
    {
        return $this->supplyCategory;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return Supplycategories
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set percentage
     *
     * @param integer $percentage
     * @return Supplycategories
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * Get percentage
     *
     * @return integer 
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * Set fk_userId
     *
     * @param integer $fkUserId
     * @return Supplycategories
     */
    public function setFkUserId($fkUserId)
    {
        $this->fk_userId = $fkUserId;

        return $this;
    }

    /**
     * Get fk_userId
     *
     * @return integer 
     */
    public function getFkUserId()
    {
        return $this->fk_userId;
    }
}
