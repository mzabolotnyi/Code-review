<?php

namespace ITDoors\SenderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * SmsCampaign
 *
 * @ORM\Table("sender_sms_campaign")
 * @ORM\Entity(repositoryClass="SmsCampaignRepository")
 */
class SmsCampaign
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text")
     */
    private $body;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="external_id", type="string", length=50, nullable=true)
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(name="error_message", type="string", nullable=true)
     */
    private $errorMsg;

    /**
     * @var \Core\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Core\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="SmsCampaignReceiver", mappedBy="campaign", cascade={"remove"})
     */
    private $receivers;

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
     * Set body
     *
     * @param string $body
     * @return SmsCampaign
     */
    public function setBody($body)
    {
        $this->body = $body;
    
        return $this;
    }

    /**
     * Get body
     *
     * @return string 
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return SmsCampaign
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     * @return SmsCampaign
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
    
        return $this;
    }

    /**
     * Get externalId
     *
     * @return string 
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMsg;
    }

    /**
     * Set error message
     *
     * @param string $errorMsg
     * @return EmailCampaign
     */
    public function setErrorMessage($errorMsg)
    {
        $this->errorMsg = $errorMsg;

        return $this;
    }

    /**
     * Set user
     *
     * @param \Core\UserBundle\Entity\User $user
     * @return SmsCampaign
     */
    public function setUser(\Core\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Core\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get receivers
     *
     * @return ArrayCollection
     */
    public function getReceivers()
    {
        return $this->receivers;
    }
}
