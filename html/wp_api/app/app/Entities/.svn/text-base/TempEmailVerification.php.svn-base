<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Tempemailverification
 *
 * @ORM\Table(name="tempEmailVerification")
 * @ORM\Entity
 */
class TempEmailVerification
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=128, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=32, nullable=true)
     */
    private $hash;

    /**
     * @var \Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="fk_userId", referencedColumnName="id")
     * })
     */
    private $fkUserid;


}
