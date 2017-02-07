<?php

namespace ITDoors\SenderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Election\ElectionBundle\Entity\Elector;
use ITDoors\ContactBundle\Entity\Phone;

/**
 * SmsCampaignReceiver
 *
 * @ORM\Table("sender_sms_campaign_receiver")
 * @ORM\Entity
 */
class SmsCampaignReceiver
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
     * @var SmsCampaign
     *
     * @ORM\ManyToOne(targetEntity="SmsCampaign", inversedBy="receivers")
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
     * @return SmsCampaignReceiver
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
     * @return SmsCampaignReceiver
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
     * @param SmsCampaign $campaign
     * @return SmsCampaignReceiver
     */
    public function setCampaign(SmsCampaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return SmsCampaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }
}
