<?php

namespace Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity @Table(name="users")
 */
class User
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
    private $userName;

    /**
     * @Column(type="string")
     * @var string
     */
    private $password;

    /**
     * @Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @Column(type="string")
     * @var string
     */
    private $email;

    /**
     * @Column(type="integer")
     * @var integer
     */
    private $adults;

    /**
     * @Column(type="integer")
     * @var integer
     */
    private $children;

    /**
     * @Column(type="integer")
     * @var integer
     */
    private $goal;

    /**
     * @Column(type="string")
     * @var string
     */
    private $registeredDate;

    /**
     * @Column(type="integer")
     * @var integer
     */
    private $active;

    /**
     * @Column(type="string")
     * @var string
     */
    private $account;

    /**
    * @Column(type="string")
    * @var string
    */
    private $subscription;

    /**
     * @Column(type="string")
     * @var string
     */
    private $lastLoggedIn;

    /**
     * @Column(type="integer")
     * @var integer
     */
    private $timesLoggedIn;

    /**
     * @OneToOne(targetEntity="Rank")
     * @JoinColumn(name="fk_rankId", referencedColumnName="id")
     * @var \Entities\Rank
     */
    private $rank;

    /**
     * @Column(type="string")
     * @var string
     */
    private $customerToken;

    /**
     * @Column(type="string")
     * @var string
     */
    private $emailVerifyHash;

    /**
     * @Column(type="string")
     * @var string
     */
    private $billingDate;

    public function getAdmin() {
        if ($this->id == 1 or $this->id == 6570 or $this->id == 6662 or $this->id == 8955) {
            return true;
        } else {
            return false;
        }
    }

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
     * Set userName
     *
     * @param string $userName
     * @return User
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName
     *
     * @return string 
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return User
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
     * Set email
     *
     * @param string $email
     * @return User
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
     * Set adults
     *
     * @param integer $adults
     * @return User
     */
    public function setAdults($adults)
    {
        $this->adults = $adults;

        return $this;
    }

    /**
     * Get adults
     *
     * @return integer 
     */
    public function getAdults()
    {
        return $this->adults;
    }

    /**
     * Set children
     *
     * @param integer $children
     * @return User
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Get children
     *
     * @return integer 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set goal
     *
     * @param integer $goal
     * @return User
     */
    public function setGoal($goal)
    {
        $this->goal = $goal;

        return $this;
    }

    /**
     * Get goal
     *
     * @return integer 
     */
    public function getGoal()
    {
        return $this->goal;
    }

    /**
     * Set registeredDate
     *
     * @param string $registeredDate
     * @return User
     */
    public function setRegisteredDate($registeredDate)
    {
        $this->registeredDate = $registeredDate;

        return $this;
    }

    /**
     * Get registeredDate
     *
     * @return string 
     */
    public function getRegisteredDate()
    {
        return $this->registeredDate;
    }

    /**
     * Set active
     *
     * @param integer $active
     * @return User
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return integer 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set account
     *
     * @param string $account
     * @return User
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return string 
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set subscription
     *
     * @param string $subscription
     * @return User
     */
    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * Get subscription
     *
     * @return string 
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * Set lastLoggedIn
     *
     * @param string $lastLoggedIn
     * @return User
     */
    public function setLastLoggedIn($lastLoggedIn)
    {
        $this->lastLoggedIn = $lastLoggedIn;

        return $this;
    }

    /**
     * Get lastLoggedIn
     *
     * @return string 
     */
    public function getLastLoggedIn()
    {
        return $this->lastLoggedIn;
    }

    /**
     * Set timesLoggedIn
     *
     * @param integer $timesLoggedIn
     * @return User
     */
    public function setTimesLoggedIn($timesLoggedIn)
    {
        $this->timesLoggedIn = $timesLoggedIn;

        return $this;
    }

    /**
     * Get timesLoggedIn
     *
     * @return integer 
     */
    public function getTimesLoggedIn()
    {
        return $this->timesLoggedIn;
    }

    /**
     * Set rank
     *
     * @param \Entities\Rank $rank
     * @return User
     */
    public function setRank(\Entities\Rank $rank = null)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return \Entities\Rank 
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set customerToken
     *
     * @param string $customerToken
     * @return User
     */
    public function setCustomerToken($customerToken)
    {
        $this->customerToken = $customerToken;

        return $this;
    }

    /**
     * Get customerToken
     *
     * @return string 
     */
    public function getCustomerToken()
    {
        return $this->customerToken;
    }

    /**
     * Set emailVerifyHash
     *
     * @param string $emailVerifyHash
     * @return User
     */
    public function setEmailVerifyHash($emailVerifyHash)
    {
        $this->emailVerifyHash = $emailVerifyHash;

        return $this;
    }

    /**
     * Get emailVerifyHash
     *
     * @return string 
     */
    public function getEmailVerifyHash()
    {
        return $this->emailVerifyHash;
    }

    /**
     * Set billingDate
     *
     * @param string $billingDate
     * @return User
     */
    public function setBillingDate($billingDate)
    {
        $this->billingDate = $billingDate;

        return $this;
    }

    /**
     * Get billingDate
     *
     * @return string 
     */
    public function getBillingDate()
    {
        return $this->billingDate;
    }
}
