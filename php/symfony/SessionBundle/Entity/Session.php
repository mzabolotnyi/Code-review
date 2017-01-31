<?php

namespace ITDoors\SessionBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Table("sessions")
 * @ORM\Entity(repositoryClass="ITDoors\SessionBundle\Entity\SessionRepository")
 */
class Session
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="sess_id", type="string", length=128, nullable=false)
     */
    private $sessId;

    /**
     * @var string
     * @ORM\Column(name="sess_data", type="blob", nullable=false)
     */
    private $sessData;

    /**
     * @var integer
     * @ORM\Column(name="sess_time", type="integer", nullable=false)
     */
    private $sessTime;

    /**
     * @var integer
     * @ORM\Column(name="sess_lifetime", type="integer", nullable=false)
     */
    private $sessLifetime;

    /**
     * Get sessId
     *
     * @return string
     */
    public function getSessId()
    {
        return $this->sessId;
    }

    /**
     * Get sessData
     *
     * @return string
     */
    public function getSessData()
    {
        return $this->sessData;
    }

    /**
     * Set sessData
     *
     * @param string $sessData
     * @return Session
     */
    public function setSessData($sessData)
    {
        $this->sessData = $sessData;

        return $this;
    }

    /**
     * Get sessTime
     *
     * @return integer
     */
    public function getSessTime()
    {
        return $this->sessTime;
    }

    /**
     * Set sessTime
     *
     * @param integer $sessTime
     * @return Session
     */
    public function setSessTime($sessTime)
    {
        $this->sessTime = $sessTime;

        return $this;
    }

    /**
     * Get sessLifetime
     *
     * @return integer
     */
    public function getSessLifetime()
    {
        return $this->sessLifetime;
    }

    /**
     * Set sessLifetime
     *
     * @param integer $sessLifetime
     * @return Session
     */
    public function setSessLifetime($sessLifetime)
    {
        $this->sessLifetime = $sessLifetime;

        return $this;
    }

    public function getExpiredTime()
    {
        return $this->getSessTime() + $this->getSessLifetime();
    }
}