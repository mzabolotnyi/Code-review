<?php

namespace ITDoors\SenderBundle\Entity;

use Doctrine\ORM\EntityRepository;

class EmailCampaignRepository extends EntityRepository
{
    /**
     * @param integer $id
     * @return EmailCampaign|null
     */
    public function findById($id)
    {
        $qb = $this->createQueryBuilder('campaign');

        $qb->leftJoin('campaign.receivers', 'receivers')
        ->addSelect('receivers')
        ->where('campaign.id = :id')
        ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }
}