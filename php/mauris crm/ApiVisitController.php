<?php

require_once 'classloader.php';

class ApiVisitController extends OBaseApiController
{
    public function rules()
    {
        return array_merge(parent::rules(), array(
            'add' => array(Controller::ALL_USERS, 0),
        ));
    }

    /**
     * Action for adding visit from mobile client
     */
    public function actionAdd()
    {
        $user = $this->getOAuthUser();

        if (empty($_POST['patientId'])) {
            $this->errorResponse('Not all parameters provided (patientId)');
        }

        if (empty($_POST['doctorId'])) {
            $this->errorResponse('Not all parameters provided (doctorId)');
        }

        if (empty($_POST['medCourseId'])) {
            $this->errorResponse('Not all parameters provided (medCourseId)');
        }

        if (empty($_POST['time'])) {
            $this->errorResponse('Not all parameters provided (time)');
        }

        $patientId = $_POST['patientId'];
        $doctorId = $_POST['doctorId'];
        $medCourseId = $_POST['medCourseId'];
        $timestamp = $_POST['time'];

        $patient = Patient::model()->find()->where('code', '=', $patientId)->execute();
        if ($patient == null) {
            $this->errorResponse("Patient with ID $patientId not found");
        }

        $doctor = Doctor::model()->find()->where('code', '=', $doctorId)->execute();
        if ($doctor == null) {
            $this->errorResponse("Doctor with ID $doctorId not found");
        }

        $medCourse = ServiceMedCourse::model()->find()->where('id', '=', $medCourseId)->execute();
        if ($medCourse == null) {
            $this->errorResponse("Med. course with ID $medCourseId not found");
        }

        $sessionInfo = SessionsInfo::findSession($doctor, $timestamp);

        if ($sessionInfo == null) {
            $this->errorResponse('Failed to find suitable doctor session');
        }

        $doctorRoom = $sessionInfo->getDoctorRoom();

        if ($doctorRoom == null) {
            $this->errorResponse('Failed to find doctor room', 500);
        }

        $params = [
            'PatientId' => $patient->code,
            'DepartmentId' => Department::DEFAULT_ID,
            'DoctorId' => $doctor->code,
            'SessionId' => $sessionInfo->code,
            'MedCourceId' => $medCourse->id,
            'RoomId' => $doctorRoom->code,
            'Date' => date('c', $timestamp),
        ];

        list($response, $error) = ApiHelper::getCurlResult(ApiHelper::BPM_DOMEN . "/0/rest/MobAppSevrice/AddVisitInfo", $params);
        $response = json_decode($response);

        if ($error) {
            $this->errorResponse('Failed to connect with bmp\'online, try again later', 500);
        } elseif ($response->InfoResult->Result == 1) {
            $this->errorResponse('Failed to create visit in bmp\'online', 500, (array)$response->InfoResult->ResultCodeDescription);
        } else {

            $visit = new Visit();
            $visit->startTime = strtotime($response->Result->StartDate);
            $visit->endTime = strtotime($response->Result->DueDate);
            $visit->status = $response->Result->Status;
            $visit->code = $response->Result->Id;
            $visit->patient = array($patient->pk);
            $visit->serviceMedCourse = array($medCourse->pk);
            $visit->sessionsInfo = array($sessionInfo->pk);

            if ($visit->save()) {
                $this->successResponse($visit->serialize());
            } else {
                $this->errorResponse('Failed to create visit, try again later', 500);
            }
        }
    }
}