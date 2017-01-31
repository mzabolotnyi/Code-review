<?php

namespace ITDoors\WebSocketNotifierBundle\Command;

use Gos\Component\WebSocketClient\Wamp\Client;
use ITDoors\SessionBundle\Entity\Session;
use ITDoors\WebSocketNotifierBundle\Classes\WSNotifier;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SessionExpiredCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('ws:session-expired');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            $container = $this->getContainer();

            $now = time();
            $notificationTime = $container->getParameter('ws.session.notification.time');
            $range = $container->getParameter('ws.session.daemon.periodic') + $container->getParameter('ws.session.daemon.offset');

            // expired soon
            $startPeriod = $now + $notificationTime;
            $endPeriod = $startPeriod + $range;

            $result = $this->publishSessionEvent($startPeriod, $endPeriod, WSNotifier::EVENT_TYPE_SESSION_EXPIRED_SOON);

            $output->writeln('<fg=white;bg=green>[ info ]</> Finished, sessions soon expired processed ' . $result);

            //already expired
            $startPeriod = $now;
            $endPeriod = $startPeriod + $range;

            $result = $this->publishSessionEvent($startPeriod, $endPeriod, WSNotifier::EVENT_TYPE_SESSION_EXPIRED);

            $output->writeln('<fg=white;bg=green>[ info ]</> Finished, sessions expired processed ' . $result);

        } catch (\Exception $e) {
            $output->writeln('<fg=white;bg=red>[ error ]</> ' . $e->getMessage());
        }
    }

    private function publishSessionEvent($startPeriod, $endPeriod, $eventType)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /**
         * @var Session[] $sessions
         */
        $sessions = $em->getRepository('ITDoorsSessionBundle:Session')->findAllExpiredInPeriod($startPeriod, $endPeriod);
        $sessionsData = [];

        foreach ($sessions as $session) {
            $sessionsData[] = [
                'sessionId' => $session->getSessId(),
                'timeLeft' => $session->getExpiredTime() - time(),
            ];
        }

        $server = new Client($this->getContainer()->getParameter('ws.host'), $this->getContainer()->getParameter('ws.port'));
        $server->connect('/');
        $server->publish(WSNotifier::ROOM_COMMON, [
            'event_type' => $eventType,
            'data' => $sessionsData
        ]);

        return count($sessionsData);
    }
}