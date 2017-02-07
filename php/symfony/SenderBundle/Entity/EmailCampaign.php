<?php

namespace ITDoors\SenderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * EmailCampaign
 *
 * @ORM\Table("sender_email_campaign")
 * @ORM\Entity(repositoryClass="EmailCampaignRepository")
 */
class EmailCampaign
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
     * @ORM\Column(name="subject", type="string", length=255)
     */
    private $subject;

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
     * @ORM\OneToMany(targetEntity="EmailCampaignReceiver", mappedBy="campaign", cascade={"remove"})
     */
    private $receivers;

    public function __construct()
    {
        $this->receivers = new ArrayCollection();
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
     * Set subject
     *
     * @param string $subject
     * @return EmailCampaign
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return EmailCampaign
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
     * @return EmailCampaign
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
     * @return EmailCampaign
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
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
     * Get externalId
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set user
     *
     * @param \Core\UserBundle\Entity\User $user
     * @return EmailCampaign
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
     * Add receiver
     *
     * @param EmailCampaignReceiver $receiver
     * @return EmailCampaign
     */
    public function addReceiver(EmailCampaignReceiver $receiver)
    {
        $this->receivers[] = $receiver;

        return $this;
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
