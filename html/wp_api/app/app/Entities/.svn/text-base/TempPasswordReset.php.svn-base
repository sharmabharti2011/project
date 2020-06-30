<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="tempPasswordReset")
 */
class TempPasswordReset {
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var integer
     */
    protected $id;      //PK

    /**
     * @Column(type="string")
     * @var string
     */
    protected $email;   //name of reviewer

    /**
     * @Column(type="string")
     * @var string
     */
    protected $hash;   //home state of reviewer

    

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
     * Set email
     *
     * @param string $email
     * @return TempPasswordReset
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return TempPasswordReset
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string 
     */
    public function getHash()
    {
        return $this->hash;
    }
}
