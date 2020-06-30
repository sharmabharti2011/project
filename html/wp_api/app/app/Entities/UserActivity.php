<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="userActivity")
 */
class UserActivity {

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @Column(type="integer")
     * @var integer
     */
    protected $fk_userId;      //FK to user table
    
    /**
     * @ManyToOne(targetEntity="Activity", inversedBy="fk_activityId")
     * @JoinColumn(name="fk_activityId", referencedColumnName="id")
     * @var \Entities\Activity
     */
    protected $activity;   //FK to activity table

    /**
     * @Column(type="string")
     * @var string
     */
    protected $extra;    //description??

    /**
     * @Column(type="string")
     * @var string
     */
    protected $date;    //date - of activity?

    /**
     * Set extra
     *
     * @param string $extra
     * @return UserActivity
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Get extra
     *
     * @return string 
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Set date
     *
     * @param string $date
     * @return UserActivity
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
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
     * Set fk_userId
     *
     * @param integer $fkUserId
     * @return UserActivity
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
     * Set activity
     *
     * @param \Entities\Activity $activity
     * @return UserActivity
     */
    public function setActivity(\Entities\Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \Entities\Activity 
     */
    public function getActivity()
    {
        return $this->activity;
    }
}
