<?php

require_once 'classloader.php';

class ApiDialogController extends OBaseApiController
{
    public function rules()
    {
        return array_merge(parent::rules(), array(
            'getDialogs' => array(Controller::ALL_USERS, 0),
            'getMessages' => array(Controller::ALL_USERS, 0),
            'addMessage' => array(Controller::ALL_USERS, 0),
            'getSyncTime' => array(Controller::ALL_USERS, 0),
            'markAsRead' => array(Controller::ALL_USERS, 0),
        ));
    }

    public function actionGetDialogs()
    {
        $user = $this->getOAuthUser();

        $dialogs = Dialog::model()
            ->findAll()
            ->where('account')
            ->like('%|' . $user->pk . '|%');

        if (isset($_GET['lastId'])) {
            $dialogs = $dialogs->whereAND('pk', '>', $_GET['lastId']);
        }

        if (isset($_GET['limit'])) {
            $dialogs = $dialogs->limit('0, ' . $_GET['limit']);
        }

        $dialogs = $dialogs->execute(["cache" => false]);

        $result = [];
        foreach ($dialogs as $dialog) {
            $result[] = $dialog->serialize();
        }

        $this->successResponse($result);
    }

    public function actionGetMessages()
    {
        $user = $this->getOAuthUser();

        if (!isset($_GET['dialogId'])) {
            $this->errorResponse('Not all parameters provided (dialogId)');
        }
        $dialogId = $_GET['dialogId'];

        $dialog = Dialog::model()
            ->find($dialogId)
            ->where('pk', '=', $dialogId)
            ->execute();

        if ($dialog == null) {
            $this->errorResponse("Dialog with ID $dialogId not found");
        }

        $result = [];
        $needReorder = false;
        if (count($dialog->dialogMessage) > 0) {

            $messages = DialogMessage::model()
                ->findAll()
                ->where('pk')
                ->in($dialog->dialogMessage);

            if (isset($_GET['lastId'])) {
                $operator = isset($_GET['reverse']) ? '<' : '>';
                $messages = $messages->whereAND('pk', $operator, $_GET['lastId']);
            }

            if (isset($_GET['limit'])) {

                $limit = (int)$_GET['limit'];

                if ($limit >= 0) {
                    $messages = $messages->limit('0, ' . $limit)->order('time');
                } else {
                    $messages = $messages->limit('0, ' . -$limit)->order('time', 'DESC');
                    $needReorder = true;
                }
            }

            $messages = $messages->execute(["cache" => false]);

            foreach ($messages as $message) {
                $result[] = $message->serialize();
            }
        }

        if ($needReorder) {
            $result = array_reverse($result);
        }

        $this->successResponse($result);
    }

    public function actionAddMessage()
    {
        $user = $this->getOAuthUser();

        $dialogType = isset($_POST['dialogType'])
            ? DialogTypeList::getId($_POST['dialogType']) : DialogTypeList::getDefaultId();

        $messageType = isset($_POST['messageType'])
            ? DialogMessageTypeList::getId($_POST['messageType']) : DialogMessageTypeList::getDefaultId();

        if ($dialogType == DialogTypeList::DOCTOR) {

            if (!isset($_POST['doctorId'])) {
                $this->errorResponse('Not all parameters provided (doctorId)');
            }

            $doctorCode = $_POST['doctorId'];
            $doctor = Doctor::model()->find()->where('code', '=', $doctorCode)->execute();

            if ($doctor == null) {
                $this->errorResponse("Doctor with code $doctorCode not found");
            } else {
                $doctorId = $doctor->pk;
            }
        } else {
            $doctorId = null;
        }

        if (empty($_POST['text'])) {
            $this->errorResponse('Not all parameters provided (text)');
        }
        $text = trim($_POST['text']);

        try {
            $dialog = Dialog::findOrCreate($dialogType, $user->pk, ['doctorId' => $doctorId]);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), $e->getCode());
        }

        try {
            $message = DialogMessage::create($messageType, $text);
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), $e->getCode());
        }

        if ($dialog->bindMessage($message)) {
            $result = array_merge($message->serialize(), ['dialogId' => (int)$dialog->pk]);
            $this->successResponse($result);
        } else {
            $this->errorResponse('Failed to bind dialog message', 500);
        }
    }

    public function actionGetSyncTime()
    {
        $user = $this->getOAuthUser();

        $dialogs = Dialog::model()
            ->findAll()
            ->where('account')
            ->like('%|' . $user->pk . '|%')
            ->execute(["cache" => false]);

        $messageIds = [];
        foreach ($dialogs as $dialog) {
            $messageIds = array_merge($messageIds, $dialog->dialogMessage);
        }

        if (count($messageIds) > 0) {

            $lastMessage = DialogMessage::model()
                ->find()
                ->where('pk')
                ->in($messageIds)
                ->order('time', 'DESC')
                ->execute();

            $syncTime = $lastMessage == null ? 0 : (int)$lastMessage->time;

        } else {
            $syncTime = 0;
        }

        $this->successResponse(['timestamp' => $syncTime]);
    }

    public function actionMarkAsRead()
    {
        $user = $this->getOAuthUser();

        if (!isset($_POST['messageId'])) {
            $this->errorResponse('Not all parameters provided (messageId)');
        }

        $messageId = $_POST['messageId'];
        $message = DialogMessage::model()->findByPk($messageId);

        if ($message == null) {
            $this->errorResponse("Message with code $messageId not found");
        }

        $dialog = $message->getDialog();

        $messages = DialogMessage::model()
            ->findAll()
            ->where('pk', '<=', $messageId)
            ->whereAND('pk')
            ->in($dialog->dialogMessage)
            ->whereANDBegin()
            ->whereOR('read', '=', 0)
            ->whereOR('read')->is('NULL')
            ->whereEnd()
            ->execute(["cache" => false]);

        $fails = 0;
        foreach ($messages as $message) {
            if (!$message->markAsRead()) {
                $fails++;
            }
        }

        if ($fails > 0) {
            $this->errorResponse('Internal error', 500);
        }

        $this->successResponse('OK');
    }
}