<?php

namespace ITDoors\WebSocketNotifierBundle\Controller;

use Gos\Component\WebSocketClient\Wamp\Client;
use ITDoors\SessionBundle\Entity\Session;
use ITDoors\WebSocketNotifierBundle\Classes\WSNotifier;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class DefaultController extends Controller
{
    /**
     * @Route("/test", name="ws")
     * @Template("ITDoorsWebSocketNotifierBundle:Default:index.html.twig")
     */
    public function indexAction()
    {
    }

    /**
     * Notifies user about end of session
     *
     * @Route("/ws/sessionEndSoon", name="ws_session_end")
     */
    public function sessionEndSoonAction()
    {die;
        $now = time();
        $notificationTime = $this->getParameter('ws.session.notification.time');
        $range = $this->getParameter('ws.session.daemon.periodic') + 10;
        $startPeriod = $now + $notificationTime;
        $endPeriod = $startPeriod + $range;

        $em = $this->get('doctrine.orm.entity_manager');

        /**
         * @var Session[] $sessions
         */
        $sessions = $em->getRepository('ITDoorsSessionBundle:Session')->findAllExpiredInPeriod($startPeriod, $endPeriod);
//        dump($sessions);die;

        $sessionsData = [];

        foreach ($sessions as $session){

            $sessionsData[] = [
                'sessionId' => $session->getSessId(),
                'timeLeft' => $session->getExpiredTime() - time(),
            ];
        }

        $server = new Client($this->getParameter('ws.host'), $this->getParameter('ws.port'));
        $server->connect('/');
        $server->publish(WSNotifier::ROOM_COMMON, [
            'event_type' => WSNotifier::EVENT_TYPE_SESSION_EXPIRED_SOON,
            'data' => $sessionsData
        ]);

        return new Response();
    }
}
