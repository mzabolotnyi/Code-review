<?php

namespace ITDoors\SenderBundle\Services;

use Election\ElectionBundle\Entity\PolSector;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Epochta REST API PHP Class
 *
 * Documentation
 * https://www.atompark.com/ru/servis-sms-rassylok/epochta-sms-api-v-2-0/
 *
 */
class EpochtaService
{
    /** @var ContainerInterface */
    private $container;

    private $apiUrl;
    private $username;
    private $password;
    private $sender;

    /**
     * Epochta API constructor
     *
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->apiUrl = $this->container->getParameter('epochta_sms.host');
        $this->username = $this->container->getParameter('epochta_sms.username');
        $this->password = $this->container->getParameter('epochta_sms.password');
        $this->sender = $this->container->getParameter('epochta_sms.from');
    }

    /**
     * Process results
     *
     * @param $response
     * @return mixed
     */
    private function handleResult($response)
    {
        $data = new \stdClass();

        if (isset($response->status) && $status = (int)$response->status < 0) {

            $data->is_error = true;

            switch ($status) {
                case -1:
                    $data->message = 'Incorrect username and/or password';
                    break;
                case -2:
                    $data->message = 'Incorrect XML format';
                    break;
                case -3:
                    $data->message = 'Not enough credit on the user\'s account';
                    break;
                case -4:
                    $data->message = 'No any valid phone numbers';
                    break;
                default:
                    $data->message = 'Unclassified error';
                    break;
            }
        } elseif (!isset($response->status)) {
            $data->is_error = true;
            $data->message = 'Unclassified error';
        }

        return $data;
    }

    /**
     * Process errors
     *
     * @param null $customMessage
     * @return \stdClass
     */
    private function handleError($customMessage = NULL)
    {
        $message = new \stdClass();
        $message->is_error = true;
        if (!is_null($customMessage)) {
            $message->message = $customMessage;
        }
        return $message;
    }

    /**
     * Create new campaign
     *
     * @param string $body
     * @param array $phoneNumbers
     * @return mixed
     */
    public function createCampaign($body, $phoneNumbers)
    {
        if (empty($body) || empty($phoneNumbers)) {
            return $this->handleError('Not all data.');
        }

        $src = $this->formatXML($body, $phoneNumbers);

        $Curl = curl_init();

        $CurlOptions = array(
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 100,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_POSTFIELDS => array('XML' => $src),
        );

        curl_setopt_array($Curl, $CurlOptions);

        if (false === ($Result = curl_exec($Curl))) {
            return false;
        }

        curl_close($Curl);

        $response = simplexml_load_string($Result);

        return $this->handleResult($response);
    }

    private function formatXML($body, $numbers)
    {
        $body = mb_convert_encoding($body, 'utf8');

        $result = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <SMS>
                <operations>
                    <operation>SEND</operation>
                </operations>
                <authentification>
                    <username>$this->username</username>
                    <password>$this->password</password>
                </authentification>
                <message>
                    <sender>$this->sender</sender>
                    <text>$body</text>
                </message>
                <numbers>";

        foreach ($numbers as $number) {
            $result .= '<number>' . preg_replace('/[^0-9]/', '', $number) . '</number>';
        }

        $result .= "</numbers></SMS>";

        return trim($result);
    }
}