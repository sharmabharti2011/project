<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="tbl_pp_recurring_profiles")
 */
class PaypalProfile
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @Column(type="string")
     * @var string
     */
    private $pp_rp_id;

    /**
     * @OneToOne(targetEntity="User")
     * @JoinColumn(name="fk_userId", referencedColumnName="id")
     * @var \Entities\User
     */
    private $user;

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
     * Set pp_rp_id
     *
     * @param string $ppRpId
     * @return PaypalProfile
     */
    public function setPpRpId($ppRpId)
    {
        $this->pp_rp_id = $ppRpId;

        return $this;
    }

    /**
     * Get pp_rp_id
     *
     * @return string 
     */
    public function getPpRpId()
    {
        return $this->pp_rp_id;
    }

    /**
     * Set user
     *
     * @param \Entities\User $user
     * @return PaypalProfile
     */
    public function setUser(\Entities\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Entities\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}
