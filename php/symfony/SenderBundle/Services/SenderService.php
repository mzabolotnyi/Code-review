<?php

namespace ITDoors\SenderBundle\Services;

use Core\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Election\ElectionBundle\Entity\Elector;
use Election\ElectionBundle\Entity\ElectorRepository;
use Election\ElectionBundle\Entity\PolDistrict;
use Election\ElectionBundle\Entity\PolSector;
use Election\ElectionBundle\Entity\PolTerritory;
use ITDoors\ContactBundle\Entity\Contact;
use ITDoors\FilterBundle\Services\FilterService;
use ITDoors\GeoBundle\Entity\City;
use ITDoors\GeoBundle\Entity\CityDistrict;
use ITDoors\GeoBundle\Entity\District;
use ITDoors\SenderBundle\Entity\EmailCampaign;
use ITDoors\SenderBundle\Entity\EmailCampaignReceiver;
use ITDoors\SenderBundle\Entity\SmsCampaign;
use ITDoors\SenderBundle\Entity\SmsCampaignReceiver;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SenderService
{
    /** @var ContainerInterface */
    private $container;

    /** @var User */
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $securityToken = $this->container->get('security.context')->getToken();

        if ($securityToken) {
            $this->user = $securityToken->getUser();
        }
    }

    /**
     * Creates email campaign. Receivers data get from current elector filter (see ITDoors\FilterBundle)
     *
     * @param string $subject
     * @param string $body
     * @return EmailCampaign
     */
    public function createEmailCampaignFromFilter($subject, $body)
    {
        try {
            $receiversData = $this->getReceiversDataFromFilter('email');
        } catch (\Exception $e) {
            $receiversData = [];
        }

        $campaign = $this->createEmailCampaign($subject, $body, $receiversData);

        return $campaign;
    }

    /**
     * Creates email campaign
     *
     * @param string $subject
     * @param string $body
     * @param array $receiversData array of target email addresses
     * @return EmailCampaign
     */
    public function createEmailCampaign($subject, $body, $receiversData)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        $campaign = new EmailCampaign();
        $campaign->setSubject($subject)
            ->setBody($body)
            ->setUser($this->user);

        $em->persist($campaign);

        foreach ($receiversData as $receiverData) {

            /**
             * @var Elector $elector
             */
            $email = isset($receiverData['contact']) ? $receiverData['contact'] : null;
            $elector = isset($receiverData['elector']) ? $receiverData['elector'] : null;

            if (!$email) {
                continue;
            }

            $receiver = new EmailCampaignReceiver();
            $receiver->setCampaign($campaign)
                ->setContact($email);

            if (is_a($elector, Elector::class)) {
                $receiver->setElector($elector);
            }

            $em->persist($receiver);
        }

        $em->flush();

        return $campaign;
    }

    /**
     * Launches sending using an external service
     *
     * @param EmailCampaign $campaign
     * @throws
     */
    public function executeEmailCampaign($campaign)
    {
        /** @var SendPulseService $sendpulseService */
        $sendpulseService = $this->container->get('sendpulse_service');

        $bookName = md5($campaign->getId() . '_' . time() . '_' . rand(100, 999));
        $addressBook = $sendpulseService->createAddressBook($bookName);

        if (isset($addressBook->is_error) && $addressBook->is_error) {
            throw new \Exception($addressBook->message);
        }

        $emails = [];

        foreach ($campaign->getReceivers() as $receiver) {
            $emails[] = [
                'email' => $receiver->getContact(),
            ];
        }

        if (count($emails) > 0) {

            $result = $sendpulseService->addEmails($addressBook->id, $emails);

            if (isset($result->is_error) && $result->is_error) {
                throw new \Exception($result->message);
            }
        } else {
            throw new \Exception('Not found any receivers');
        }

        $senderName = $this->container->getParameter('sendpulse.sender_name');
        $senderEmail = $this->container->getParameter('sendpulse.sender_email');
        $subject = $campaign->getSubject();
        $body = $campaign->getBody();

        $result = $sendpulseService->createCampaign($senderName, $senderEmail, $subject, $body, $addressBook->id);

        if (isset($result->is_error) && $result->is_error) {
            throw new \Exception($result->message);
        }

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        $campaign->setExternalId($result->id);
        $em->flush($campaign);
    }

    /**
     * Creates sms campaign. Receivers data get from current elector filter (see ITDoors\FilterBundle)
     *
     * @param string $body
     * @return SmsCampaign
     */
    public function createSmsCampaignFromFilter($body)
    {
        try {
            $receiversData = $this->getReceiversDataFromFilter('sms');
        } catch (\Exception $e) {
            $receiversData = [];
        }

        $campaign = $this->createSmsCampaign($body, $receiversData);

        return $campaign;
    }

    /**
     * Creates sms campaign
     *
     * @param string $body
     * @param array $receiversData array of target phones
     * @return SmsCampaign
     */
    public function createSmsCampaign($body, $receiversData)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        $campaign = new SmsCampaign();
        $campaign->setBody($body)
            ->setUser($this->user);

        $em->persist($campaign);

        foreach ($receiversData as $receiverData) {

            /**
             * @var Elector $elector
             */
            $phone = isset($receiverData['contact']) ? $receiverData['contact'] : null;
            $elector = isset($receiverData['elector']) ? $receiverData['elector'] : null;

            if (!$phone) {
                continue;
            }

            $receiver = new SmsCampaignReceiver();
            $receiver->setCampaign($campaign)
                ->setContact($phone);

            if (is_a($elector, Elector::class)) {
                $receiver->setElector($elector);
            }

            $em->persist($receiver);
        }

        $em->flush();

        return $campaign;
    }

    /**
     * Launches sending using an external service
     *
     * @param SmsCampaign $campaign
     * @throws
     */
    public function executeSmsCampaign($campaign)
    {
        /** @var EpochtaService $epochtaService */
        $epochtaService = $this->container->get('epochta_service');

        $phoneNumbers = [];

        foreach ($campaign->getReceivers() as $receiver) {
            $phoneNumbers[] = $receiver->getContact();
        }

        if (count($phoneNumbers) > 0) {

            $result = $epochtaService->createCampaign($campaign->getBody(), $phoneNumbers);

            if (isset($result->is_error) && $result->is_error) {
                throw new \Exception($result->message);
            }
        } else {
            throw new \Exception('Not found any receivers');
        }
    }

    /**
     * @param string $campaignType one of values 'email', 'sms'
     * @return array
     */
    private function getReceiversDataFromFilter($campaignType = 'email')
    {
        /**
         * @var EntityManager $em
         * @var FilterService $filterService
         * @var Elector[] $electors
         */
        $em = $this->container->get('doctrine')->getManager();
        $filterService = $this->container->get('it_doors.filter_service');
        $electors = [];
        $result = [];

        /**
         * NOTE: Logic copied from FilterService->getFilteredDataForTable()
         */
        if ($filterService->isSetCriteria($filterService::electionFilterCriteria)) {

            $criteria = $filterService->getCriteria($filterService::electionFilterCriteria);
            $alias = $criteria['alias'];
            $id = $criteria['id'];

            /** @var ElectorRepository $electorRepo */
            $electorRepo = $em->getRepository('ElectionBundle:Elector');

            $methodSuffix = $this->getRepoMethodSuffix($campaignType);

            switch ($alias) {

                //POLITICS FILTER
                case PolDistrict::$ALIAS:
                    $electors = $electorRepo->{'findJoinedArraybyFilter' . $methodSuffix}($filterService, $id);
                    break;
                case PolSector::$ALIAS:
                    $electors = $electorRepo->{'queryByFilterAndSectorWithSub' . $methodSuffix}($filterService, $id)->getResult();
                    break;
                case PolTerritory::$ALIAS:
                    $electors = $electorRepo->{'queryByFilterAndTerritoryWithSub' . $methodSuffix}($filterService, $id)->getResult();
                    break;

                //TERRITORY FILTER
                case District::$ALIAS:
                    $electors = $electorRepo->{'queryByFilterAndGeoDistrictWithSub' . $methodSuffix}($filterService, $id)->getResult();
                    break;
                case City::$ALIAS:
                    $electors = $electorRepo->{'queryByFilterAndCityWithSub' . $methodSuffix}($filterService, $id)->getResult();
                    break;
                case CityDistrict::$ALIAS:
                    $electors = $electorRepo->{'queryByFilterAndCityDistrictWithSub' . $methodSuffix}($filterService, $id)->getResult();
                    break;

                default:
                    break;
            }
        }

        foreach ($electors as $elector) {

            $contact = $elector->getContacts();

            if ($campaignType == 'email') {

                $result[] = $this->getReceiverData($elector, $contact->getEmail());

            } elseif ($campaignType == 'sms') {

                $mobilePhones = $contact->getMobilePhones();

                foreach ($mobilePhones as $mobilePhone) {
                    $result[] = $this->getReceiverData($elector, $mobilePhone->getName());
                }
            }
        }

        return $result;
    }

    /**
     * Returns suffix of the elector's repository method depending on campaign type
     *
     * @param $campaignType
     * @return string
     * @throws \Exception
     */
    private function getRepoMethodSuffix($campaignType)
    {
        switch ($campaignType) {
            case 'email':
                return 'WithEmails';
            case 'sms':
                return 'WithMobilePhones';
            default:
                throw new \Exception('Incorrect campaign type');
        }
    }

    /**
     * Returns array of receiver data
     *
     * @param Elector $elector
     * @param string $contact
     * @return array
     */
    private function getReceiverData(Elector $elector, $contact)
    {
        return [
            'elector' => $elector,
            'contact' => $contact,
        ];
    }
}