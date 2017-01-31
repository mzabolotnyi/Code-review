<?php

namespace ITDoors\SessionBundle\Entity;

use Doctrine\ORM\EntityRepository;

class SessionRepository extends EntityRepository
{
    /**
     * @param int $startTime start of the period in timestamp format
     * @param int $endTime end of the period in timestamp format
     * @return array
     */
    public function findAllExpiredInPeriod($startTime, $endTime)
    {
        $qb = $this->createQueryBuilder('sess');
        $e = $qb->expr();

        $qb->where($e->andX()
            ->add($e->gte('sess.sessTime + sess.sessLifetime', ':startTime'))
            ->add($e->lte('sess.sessTime + sess.sessLifetime', ':endTime'))
        )->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        return $qb->getQuery()->getResult();
    }
}
