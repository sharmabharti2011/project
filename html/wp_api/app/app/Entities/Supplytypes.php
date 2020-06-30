<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="supplyTypes")
 */
class Supplytypes
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
     * @Column(name="supplyType", type="string", length=128, nullable=true)
     */
    private $supplyType;

    /**
     * @var string
     *
     * @Column(name="consumedMonthly", type="string", length=128, nullable=true)
     */
    private $consumedMonthly;

    /**
     * @var string
     *
     * @Column(name="need", type="string", length=128, nullable=true)
     */
    private $need;

    /**
     * @var string
     *
     * @Column(name="eachFamilyMember", type="string", length=128, nullable=true)
     */
    private $eachFamilyMember;

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
     * @var \Entities\Supplycategories
     *
     * @ManyToOne(targetEntity="Supplycategories", inversedBy="fk_supplyCategoryId")
     * @JoinColumn(name="fk_supplyCategoryId", referencedColumnName="id")
     */
    private $supplyCategory;

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
     * Set supplyType
     *
     * @param string $supplyType
     * @return Supplytypes
     */
    public function setSupplyType($supplyType)
    {
        $this->supplyType = $supplyType;

        return $this;
    }

    /**
     * Get supplyType
     *
     * @return string 
     */
    public function getSupplyType()
    {
        return $this->supplyType;
    }

    /**
     * Set consumedMonthly
     *
     * @param string $consumedMonthly
     * @return Supplytypes
     */
    public function setConsumedMonthly($consumedMonthly)
    {
        $this->consumedMonthly = $consumedMonthly;

        return $this;
    }

    /**
     * Get consumedMonthly
     *
     * @return string 
     */
    public function getConsumedMonthly()
    {
        return $this->consumedMonthly;
    }

    /**
     * Set need
     *
     * @param string $need
     * @return Supplytypes
     */
    public function setNeed($need)
    {
        $this->need = $need;

        return $this;
    }

    /**
     * Get need
     *
     * @return string 
     */
    public function getNeed()
    {
        return $this->need;
    }

    /**
     * Set eachFamilyMember
     *
     * @param string $eachFamilyMember
     * @return Supplytypes
     */
    public function setEachFamilyMember($eachFamilyMember)
    {
        $this->eachFamilyMember = $eachFamilyMember;

        return $this;
    }

    /**
     * Get eachFamilyMember
     *
     * @return string 
     */
    public function getEachFamilyMember()
    {
        return $this->eachFamilyMember;
    }

    /**
     * Set oneAdultNeedsMonth
     *
     * @param float $oneAdultNeedsMonth
     * @return Supplytypes
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
     * @return Supplytypes
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
     * @return Supplytypes
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
     * @return Supplytypes
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
     * @return Supplytypes
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
     * Set supplyCategory
     *
     * @param \Entities\Supplycategories $supplyCategory
     * @return Supplytypes
     */
    public function setSupplyCategory(\Entities\Supplycategories $supplyCategory = null)
    {
        $this->supplyCategory = $supplyCategory;

        return $this;
    }

    /**
     * Get supplyCategory
     *
     * @return \Entities\Supplycategories 
     */
    public function getSupplyCategory()
    {
        return $this->supplyCategory;
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
