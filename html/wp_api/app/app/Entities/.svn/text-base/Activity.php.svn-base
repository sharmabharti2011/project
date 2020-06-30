<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="activity")
 */
class Activity {

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var integer
     */
    protected $id;      //PK

    /**
     * @Column(type="string")
     * @var string
     */
    protected $activity;   //text to add when the activity takes place

    /**
     * @Column(type="string")
     * @var string
     */
    protected $class;    //css class to use

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
     * Set activity
     *
     * @param string $activity
     * @return Activity
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return string 
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set class
     *
     * @param string $class
     * @return Activity
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string 
     */
    public function getClass()
    {
        return $this->class;
    }
}
