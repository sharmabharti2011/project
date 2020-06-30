<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="rank")
 */
class Rank
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
    private $rankName;

    /**
     * @Column(type="string")
     * @var string
     */
    private $rankImg;


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
     * Set rankName
     *
     * @param string $rankName
     * @return Rank
     */
    public function setRankName($rankName)
    {
        $this->rankName = $rankName;

        return $this;
    }

    /**
     * Get rankName
     *
     * @return string 
     */
    public function getRankName()
    {
        return $this->rankName;
    }

    /**
     * Set rankImg
     *
     * @param string $rankImg
     * @return Rank
     */
    public function setRankImg($rankImg)
    {
        $this->rankImg = $rankImg;

        return $this;
    }

    /**
     * Get rankImg
     *
     * @return string 
     */
    public function getRankImg()
    {
        return $this->rankImg;
    }
}
