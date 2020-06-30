<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="foodTypes")
 */
class Foodtypes
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
     * @Column(name="foodType", type="string", length=128, nullable=true)
     */
    private $foodType;

    /**
     * @var float
     *
     * @Column(name="oneAdultNeedsMonth", type="float", nullable=true)
     */
    private $oneAdultNeedsMonth;

    /**
     * @var float
     *
     * @Column(name="userHas", type="float", nullable=true)
     */
    private $userHas;

    /**
     * @var integer
     *
     * @Column(name="averageHave", type="integer", nullable=true)
     */
    private $averageHave;

    /**
     * @var \Entities\Measurements
     *
     * @OneToOne(targetEntity="Measurements")
     * @JoinColumn(name="fk_measurementId", referencedColumnName="id")
     */
    private $measurement;

    /**
     * @var \Entities\FoodCategories
     *
     * @ManyToOne(targetEntity="Foodcategories", inversedBy="fk_FoodCategoryId")
     * @JoinColumn(name="fk_foodCategoryId", referencedColumnName="id")
     */
    private $foodCategory;

    /**
     * @Column(type="integer")
     * @var integer
     */
    private $fk_userId;

    private $userNeeds;


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
     * Set foodType
     *
     * @param string $foodType
     * @return Foodtypes
     */
    public function setFoodType($foodType)
    {
        $this->foodType = $foodType;

        return $this;
    }

    /**
     * Get foodType
     *
     * @return string 
     */
    public function getFoodType()
    {
        return $this->foodType;
    }

    /**
     * Set oneAdultNeedsMonth
     *
     * @param float $oneAdultNeedsMonth
     * @return Foodtypes
     */
    public function setOneAdultNeedsMonth($oneAdultNeedsMonth)
    {
        $this->oneAdultNeedsMonth = $oneAdultNeedsMonth;

        return $this;
    }

    /**
     * Get oneAdultNeedsMonth
     *
     * @return float 
     */
    public function getOneAdultNeedsMonth()
    {
        return $this->oneAdultNeedsMonth;
    }

    /**
     * Set userHas
     *
     * @param float $userHas
     * @return Foodtypes
     */
    public function setUserHas($userHas)
    {
        $this->userHas = $userHas;

        return $this;
    }

    /**
     * Get userHas
     *
     * @return float 
     */
    public function getUserHas()
    {
        return $this->userHas;
    }

    /**
     * Set averageHave
     *
     * @param integer $averageHave
     * @return Foodtypes
     */
    public function setAverageHave($averageHave)
    {
        $this->averageHave = $averageHave;

        return $this;
    }

    /**
     * Get averageHave
     *
     * @return integer 
     */
    public function getAverageHave()
    {
        return $this->averageHave;
    }

    /**
     * Set fk_userId
     *
     * @param integer $fkUserId
     * @return Foodtypes
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

    /**
     * Set measurement
     *
     * @param \Entities\Measurements $measurement
     * @return Foodtypes
     */
    public function setMeasurement(\Entities\Measurements $measurement = null)
    {
        $this->measurement = $measurement;

        return $this;
    }

    /**
     * Get measurement
     *
     * @return \Entities\Measurements 
     */
    public function getMeasurement()
    {
        return $this->measurement;
    }

    /**
     * Set foodCategory
     *
     * @param \Entities\Foodcategories $foodCategory
     * @return Foodtypes
     */
    public function setFoodCategory(\Entities\Foodcategories $foodCategory = null)
    {
        $this->foodCategory = $foodCategory;

        return $this;
    }

    /**
     * Get foodCategory
     *
     * @return \Entities\Foodcategories 
     */
    public function getFoodCategory()
    {
        return $this->foodCategory;
    }

    public function setUserNeeds($userNeeds)
    {
        $this->userNeeds = $userNeeds;

        return $this;
    }

    public function getUserNeeds()
    {
        return $this->userNeeds;
    }
}
