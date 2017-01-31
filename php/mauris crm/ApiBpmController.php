<?php

require_once 'classloader.php';

class ApiBpmController extends OBaseApiController
{
    const ACCESS_TOKEN = "d691e9c915b8eb0aa07f08dbbc965b1dae25a26b";

    public function rules()
    {
        return array_merge(parent::rules(), array(
            'getVisits' => array(Controller::ALL_USERS, 0),
            'getVisit' => array(Controller::ALL_USERS, 1),
            'postVisit' => array(Controller::ALL_USERS, 0),
            'updateVisit' => array(Controller::ALL_USERS, 1),
            'deleteVisit' => array(Controller::ALL_USERS, 1),
        ));
    }

    public function beforeAction($method, $arguments)
    {
        parent::beforeAction($method, $arguments);
        $this->checkAccessToken();
    }

    /**
     * Action for getting visits
     */
    public function actionGetVisits()
    {
        $visits = Visit::model()
            ->findAll()
            ->order('startTime');

        if (isset($_GET['startPeriod'])) {

            $startPeriod = strtotime($_GET['startPeriod']);

            if ($startPeriod) {
                $visits = $visits->whereAND('startTime', '>', $startPeriod);
            }
        }

        if (isset($_GET['endPeriod'])) {

            $endPeriod = strtotime($_GET['endPeriod']);

            if ($endPeriod) {
                $visits = $visits->whereAND('endTime', '<', $endPeriod);
            }
        }

        $visits = $visits->execute();

        $result = [];
        foreach ($visits as $visit) {
            $result[] = $this->serializeVisit($visit);
        }

        $this->successResponse($result);
    }

    /**
     * Action for getting an existing visit
     *
     * @param int $id
     */
    public function actionGetVisit($id)
    {
        $visit = $this->findVisitById($id);

        $this->successResponse($this->serializeVisit($visit));
    }

    /**
     * Action for adding a new visit
     */
    public function actionPostVisit()
    {
        $visit = new Visit;
        $this->fillVisit($visit);

        if ($visit->save()) {
            $response = $this->serializeVisit($visit);
            $this->successResponse($response);
        } else {
            $this->errorResponse('Failed to create visit', 500);
        }
    }

    /**
     * Action for updating an existing visit
     *
     * @param int $id
     */
    public function actionUpdateVisit($id)
    {
        $visit = $this->findVisitById($id);
        $this->fillVisit($visit);

        if ($visit->save()) {
            $response = $this->serializeVisit($this->findVisitById($id));
            $this->successResponse($response);
        } else {
            $this->errorResponse('Failed to update visit', 500);
        }
    }

    /**
     * Action for deleting a visit
     *
     * @param int $id
     */
    public function actionDeleteVisit($id)
    {
        $visit = $this->findVisitById($id);

        if (!$visit->delete()) {
            $this->errorResponse('Failed to delete visit', 500);
        }
    }

    /**
     * Finds visit by code (bpm id)
     *
     * @param int $id
     * @return Model|Visit
     */
    private function findVisitById($id)
    {
        $visit = Visit::model()->find()->where('code', '=', $id)->execute();

        if ($visit == null) {
            $this->errorResponse("Visit with ID $id not found");
        }

        return $visit;
    }

    /**
     * Serializes Visit object into an associative array with needed keys
     *
     * @param Visit $visit
     * @return array
     */
    private function serializeVisit($visit)
    {
        /**
         * @var Patient $patient
         * @var ServiceMedCourse $medCourse
         * @var SessionsInfo $session
         * @var Doctor $doctor
         * @var DoctorRoom $doctorRoom
         */

        $patient = count($visit->patient) > 0
            ? Patient::model()->findByPk($visit->patient[0]) : null;

        $medCourse = count($visit->serviceMedCourse) > 0
            ? ServiceMedCourse::model()->findByPk($visit->serviceMedCourse[0]) : null;

        $session = count($visit->sessionsInfo) > 0
            ? SessionsInfo::model()->findByPk($visit->sessionsInfo[0]) : null;

        $doctor = $session == null ? null : $session->getDoctor();
        $doctorRoom = $session == null ? null : $session->getDoctorRoom();

        return [
            'id' => $visit->code,
            'startDate' => date('c', $visit->startTime),
            'dueDate' => date('c', $visit->endTime),
            'status' => $visit->status,
            'patientId' => $patient == null ? null : $patient->code,
            'sessionId' => $session == null ? null : $session->code,
            'medCourseId' => $medCourse == null ? null : $medCourse->id,
            'doctorId' => $doctor == null ? null : $doctor->code,
            'doctorRoomId' => $doctorRoom == null ? null : $doctorRoom->code,
        ];
    }

    /**
     * Fills a visit data from $_POST
     *
     * @param Visit $visit
     */
    private function fillVisit($visit)
    {
        // checks required field for new visit
        if ($visit->isNew()) {

            $requiredFields = [
                'startDate',
                'dueDate',
                'status',
                'code',
                'patientId',
                'medCourseId',
                'sessionId',
            ];

            foreach ($requiredFields as $fieldName) {
                if (empty($_POST[$fieldName])) {
                    $this->errorResponse("Not all parameters provided ($fieldName)");
                }
            }
        }

        // fills start time
        if (!empty($_POST['startDate'])) {

            $startTime = strtotime($_POST['startDate']);

            if (!$startTime) {
                $this->errorResponse('Invalid format (startDate)');
            }

            $visit->startTime = $startTime;
        }

        // fills end time
        if (!empty($_POST['dueDate'])) {

            $endTime = strtotime($_POST['dueDate']);

            if (!$endTime) {
                $this->errorResponse('Invalid format (dueDate)');
            }

            $visit->endTime = $endTime;
        }

        // fills status
        if (!empty($_POST['status'])) {
            $visit->status = $_POST['status'];
        }

        // fills code
        if (!empty($_POST['code'])) {
            $visit->code = $_POST['code'];
        }

        // fills patient
        if (!empty($_POST['patientId'])) {

            $patientId = $_POST['patientId'];
            $patient = Patient::model()->find()->where('code', '=', $patientId)->execute();

            if ($patient == null) {
                $this->errorResponse("Patient with ID $patientId not found");
            }

            $visit->patient = array($patient->pk);
        }

        // fills med. course
        if (!empty($_POST['medCourseId'])) {

            $medCourseId = $_POST['medCourseId'];
            $medCourse = ServiceMedCourse::model()->find()->where('id', '=', $medCourseId)->execute();

            if ($medCourse == null) {
                $this->errorResponse("Med.course with ID $medCourseId not found");
            }

            $visit->serviceMedCourse = array($medCourse->pk);
        }

        // fills session
        if (!empty($_POST['sessionId'])) {

            $sessionId = $_POST['sessionId'];
            $session = SessionsInfo::model()->find()->where('code', '=', $sessionId)->execute();

            if ($session == null) {
                $this->errorResponse("Session with ID $sessionId not found");
            }

            $visit->sessionsInfo = array($session->pk);
        }
    }

    /**
     * Checks provided access token with valid
     */
    private function checkAccessToken()
    {
        if (empty($_GET['access_token'])) {
            $this->errorResponse('Not all parameters provided (access_token)');
        }

        if ($_GET['access_token'] != self::ACCESS_TOKEN) {
            $this->errorResponse('Invalid access token', 401);
        }
    }
}