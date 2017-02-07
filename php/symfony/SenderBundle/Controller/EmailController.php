<?php

namespace ITDoors\SenderBundle\Controller;

use ITDoors\JobBundle\Services\JobService;
use ITDoors\SenderBundle\Command\ExecuteEmailCampaignCommand;
use ITDoors\SenderBundle\Entity\EmailCampaign;
use ITDoors\SenderBundle\Form\EmailCampaignType;
use ITDoors\SenderBundle\Services\SenderService;
use ITDoors\SurveyBundle\Services\FormErrorParserService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\DataCollectorTranslator;

/**
 * @Route("/email")
 */
class EmailController extends Controller
{
    /**
     * @Route("/campaign/createFromFilter", name="sender_email_campaign_create_from_filter")
     *
     * @ApiDoc(
     *  section = "Sender",
     *  description="Creates task for executing email campaign for current filtered electors",
     *  method="POST",
     *  parameters={
     *      {"name"="subject", "dataType"="string", "required"=true, "description"="mail subject"},
     *      {"name"="body", "dataType"="string", "required"=true, "description"="mail body"}
     *  },
     *  statusCodes = {
     *      200 = "Returned when successful",
     *      400 = "Returned when validation incoming data fails"
     *  },
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function createCampaignFromFilterAction(Request $request)
    {
        $form = $this->createForm(new EmailCampaignType(), null);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $formData = $form->getData();
            $subject = $formData['subject'];
            $body = $formData['body'];

            /**
             * @var SenderService $senderService
             * @var JobService $jobService
             * @var EmailCampaign $campaign
             */
            $senderService = $this->get('sender_service');
            $jobService = $this->get('itdoors.jobs_queue');

            $campaign = $senderService->createEmailCampaignFromFilter($subject, $body);

            $jobService->create(
                ExecuteEmailCampaignCommand::NAME,
                ["--campaign={$campaign->getId()}"],
                'Виконання емейл розсилки',
                $this->getUser()
            );

            return new JsonResponse([
                'message' => $this->get('translator')->trans('Survey statistic in progress')
            ]);
        }

        /** @var FormErrorParserService $formErrorParser */
        $formErrorParser = $this->container->get('survey.form_error_parser_service');

        return new JsonResponse([
            'error' => 'Validation fails',
            'details' => $formErrorParser->parse($form),
        ], Response::HTTP_BAD_REQUEST);
    }
}
