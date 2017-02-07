<?php

namespace ITDoors\SenderBundle\Command;

use Doctrine\ORM\EntityManager;
use ITDoors\SenderBundle\Entity\EmailCampaignRepository;
use ITDoors\SenderBundle\Entity\SmsCampaignRepository;
use ITDoors\SenderBundle\Services\SenderService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteSmsCampaignCommand extends ContainerAwareCommand
{
    const NAME = 'sender:sms:execute';

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDefinition([
                new InputOption('campaign', null, InputOption::VALUE_REQUIRED, 'Sms campaign ID'),
                new InputOption('jms-job-id', null, InputOption::VALUE_OPTIONAL),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var EntityManager $em
         * @var SmsCampaignRepository $campaignRepo
         * @var SenderService $senderService
         */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $campaignRepo = $em->getRepository('ITDoorsSenderBundle:SmsCampaign');
        $senderService = $this->getContainer()->get('sender_service');

        $campaignId = $input->getOption('campaign');
        $campaign = $campaignRepo->findById($campaignId);

        try {
            $senderService->executeSmsCampaign($campaign);
        } catch (\Exception $e) {

            $campaign->setErrorMessage($e->getMessage());
            $em->flush($campaign);

            throw $e;
        }
    }

}