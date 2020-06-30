<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="reviews")
 */
class Review {
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var integer
     */
    protected $id;      //PK

    /**
     * @Column(type="string")
     * @var string
     */
    protected $name;   //name of reviewer

    /**
     * @Column(type="string")
     * @var string
     */
    protected $state;   //home state of reviewer

    /**
     * @Column(type="text")
     * @var text
     */
    protected $review;  

    /**
     * @Column(type="string")
     * @var string
     */  
    protected $reviewDate;  //date

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
     * Set name
     *
     * @param string $name
     * @return Review
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Review
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set review
     *
     * @param string $review
     * @return Review
     */
    public function setReview($review)
    {
        $this->review = $review;

        return $this;
    }

    /**
     * Get review
     *
     * @return string 
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * Set reviewDate
     *
     * @param string $reviewDate
     * @return Review
     */
    public function setReviewDate($reviewDate)
    {
        $this->reviewDate = $reviewDate;

        return $this;
    }

    /**
     * Get reviewDate
     *
     * @return string 
     */
    public function getReviewDate()
    {
        return $this->reviewDate;
    }
}
