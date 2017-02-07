<?php

namespace ITDoors\SenderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Election\ElectionBundle\Entity\Elector;

/**
 * EmailCampaignReceiver
 *
 * @ORM\Table("sender_email_campaign_receiver")
 * @ORM\Entity
 */
class EmailCampaignReceiver
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
     * @ORM\Column(name="contact", type="string", length=255)
     */
    private $contact;

    /**
     * @var Elector
     *
     * @ORM\ManyToOne(targetEntity="\Election\ElectionBundle\Entity\Elector")
     * @ORM\JoinColumn(name="elector_id", referencedColumnName="id")
     */
    private $elector;

    /**
     * @var EmailCampaign
     *
     * @ORM\ManyToOne(targetEntity="EmailCampaign", inversedBy="receivers")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id", nullable=false)
     */
    private $campaign;

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
     * Set contact
     *
     * @param string $contact
     * @return EmailCampaignReceiver
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
    
        return $this;
    }

    /**
     * Get contact
     *
     * @return string 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set elector
     *
     * @param Elector $elector
     * @return EmailCampaignReceiver
     */
    public function setElector(Elector $elector = null)
    {
        $this->elector = $elector;

        return $this;
    }

    /**
     * Get elector
     *
     * @return Elector
     */
    public function getElector()
    {
        return $this->elector;
    }

    /**
     * Set campaign
     *
     * @param EmailCampaign $campaign
     * @return EmailCampaignReceiver
     */
    public function setCampaign(EmailCampaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return EmailCampaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }
}
