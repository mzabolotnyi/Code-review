<?php

require_once 'classloader.php';

class ApiSessionController extends OBaseApiController
{
    public function rules()
    {
        return array_merge(parent::rules(), array(
            'getAvailableTime' => array(Controller::ALL_USERS, 0),
        ));
    }

    /**
     * Action for getting the available time to make an appointment to see a doctor
     */
    public function actionGetAvailableTime()
    {
        if (empty($_GET['doctorId'])) {
            $this->errorResponse('Not all parameters provided (doctorId)');
        }

        if (empty($_GET['date'])) {
            $this->errorResponse('Not all parameters provided (date)');
        }

        $doctorId = $_GET['doctorId'];

        $dateStr = $_GET['date'];

        if (!DateTime::createFromFormat('Y-m-d', $dateStr)) {
            $this->errorResponse('Date invalid format');
        }

        $startDay = DateTime::createFromFormat('Y-m-d H:i:s', (new DateTime($dateStr))->format('Y-m-d 00:00:00'))->getTimestamp();
        $endDay = DateTime::createFromFormat('Y-m-d H:i:s', (new DateTime($dateStr))->format('Y-m-d 23:59:59'))->getTimestamp();

        $doctor = Doctor::model()->find()->where('code', '=', $doctorId)->execute();
        if ($doctor == null) {
            $this->errorResponse("Doctor with ID $doctorId not found");
        }

        if (count($doctor->sessionsInfo) == 0) {
            $this->successResponse(array());
        }

        $sessions = SessionsInfo::findSessionsInPeriod($startDay, $endDay, $doctor);

        $result = [];

        foreach ($sessions as $session) {

            $begin = max($startDay, (int)$session->startTime);
            $end = min($endDay, (int)$session->endTime);

            $visits = $session->getVisits($begin, $end);
            $counter = 0;

            while ($begin < $end) {

                $interval = null;

                if ($counter == count($visits)) {

                    //this is last interval
                    $interval = [
                        'begin' => $begin,
                        'end' => $end,
                        'beginISO' => date('Y-m-d H:i:s', $begin),
                        'endISO' => date('Y-m-d H:i:s', $end),
                    ];

                    $begin = $end;

                } else {

                    $visit = $visits[$counter];
                    $startVisit = (int)$visit->startTime;
                    $endVisit = (int)$visit->endTime;

                    if ($startVisit != $begin && $begin < $startVisit) {

                        $interval = [
                            'begin' => $begin,
                            'end' => $startVisit,
                            'beginISO' => date('Y-m-d H:i:s', $begin),
                            'endISO' => date('Y-m-d H:i:s', $startVisit),
                        ];
                    }

                    $begin = $endVisit;
                    $counter++;
                }

                if ($interval) {
                    $result[] = $interval;
                }
            }
        }

        $this->successResponse($result);
    }
}