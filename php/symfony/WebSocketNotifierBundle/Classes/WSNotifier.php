<?php

namespace ITDoors\WebSocketNotifierBundle\Classes;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use ITDoors\SessionBundle\Entity\Session;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class WSNotifier implements TopicInterface
{
    const EVENT_TYPE_CONNECTED = 'connected';
    const EVENT_TYPE_DISCONNECTED = 'disconnected';
    const EVENT_TYPE_SESSION_EXPIRED = 'session_end';
    const EVENT_TYPE_SESSION_EXPIRED_SOON = 'session_end_soon';

    const ROOM_COMMON = 'common';

    protected $clientManipulator;

    /**
     * @param ClientManipulatorInterface $clientManipulator
     */
    public function __construct(ClientManipulatorInterface $clientManipulator)
    {
        $this->clientManipulator = $clientManipulator;
    }

    /**
     * This will receive any Subscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        $client = $this->clientManipulator->getClient($connection);

        if ($client instanceof UserInterface) {

            $data = [
                'event_type' => self::EVENT_TYPE_CONNECTED,
                'data' => null,
            ];

            $topic->broadcast(json_encode($data), [], [$connection->WAMP->sessionId]);
        }
    }

    /**
     * This will receive any UnSubscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
    {
        $client = $this->clientManipulator->getClient($connection);

        if ($client instanceof UserInterface) {

            $data = [
                'event_type' => self::EVENT_TYPE_DISCONNECTED,
                'data' => null,
            ];

            $topic->broadcast(json_encode($data), [], [$connection->WAMP->sessionId]);
        }
    }

    /**
     * This will receive any Publish requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @param $event
     * @param array $exclude
     * @param array $eligible
     * @return mixed|void
     */
    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
    {
        if (!isset($event['event_type'])) {
            return;
        }

        switch ($event['event_type']) {
            case self::EVENT_TYPE_SESSION_EXPIRED:
                $this->broadcastSessionExpired($topic, $event['data']);
                break;
            case self::EVENT_TYPE_SESSION_EXPIRED_SOON:
                $this->broadcastSessionExpiredSoon($topic, $event['data']);
                break;
            default:
                break;
        }
    }

    public function getName()
    {
        return 'ws.notifier';
    }

    /**
     * @param Topic $topic
     * @param $sessionsData
     */
    protected function broadcastSessionExpired(Topic $topic, $sessionsData)
    {
        $events = [];

        foreach ($topic as $connection) {

            $client = $this->clientManipulator->getClient($connection);

            if (!$client instanceof UserInterface) {
                continue;
            }

            $connectionSessionId = $connection->Session->getId();

            foreach ($sessionsData as $sessionData) {

                $sessionId = $sessionData['sessionId'];

                if ($sessionId == $connectionSessionId) {

                    $events[] = [
                        'sessionId' => $connection->WAMP->sessionId,
                        'data' => [
                            'event_type' => self::EVENT_TYPE_SESSION_EXPIRED,
                            'data' => null
                        ]
                    ];
                }
            }
        }

        $this->sendEvents($topic, $events);
    }

    /**
     * @param Topic $topic
     * @param $sessionsData
     */
    protected function broadcastSessionExpiredSoon(Topic $topic, $sessionsData)
    {
        $events = [];

        foreach ($topic as $connection) {

            $client = $this->clientManipulator->getClient($connection);

            if (!$client instanceof UserInterface) {
                continue;
            }

            $connectionSessionId = $connection->Session->getId();

            foreach ($sessionsData as $sessionData) {

                $sessionId = $sessionData['sessionId'];
                $timeLeft = $sessionData['timeLeft'];

                if ($sessionId == $connectionSessionId) {

                    $events[] = [
                        'sessionId' => $connection->WAMP->sessionId,
                        'data' => [
                            'event_type' => self::EVENT_TYPE_SESSION_EXPIRED_SOON,
                            'data' => [
                                'timeLeft' => $timeLeft,
                            ]
                        ]
                    ];
                }
            }
        }

        $this->sendEvents($topic, $events);
    }

    /**
     * @param Topic $topic
     * @param array $events ['sessionId' => '123', 'data' => 'data']
     */
    protected function sendEvents(Topic $topic, $events)
    {
        foreach ($events as $event) {
            $topic->broadcast(json_encode($event['data']), [], [$event['sessionId']]);
        }
    }
}