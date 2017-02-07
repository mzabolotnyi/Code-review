<?php

namespace ITDoors\SenderBundle\Entity;

use Doctrine\ORM\EntityRepository;

class SmsCampaignRepository extends EntityRepository
{
    /**
     * @param integer $id
     * @return SmsCampaign|null
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