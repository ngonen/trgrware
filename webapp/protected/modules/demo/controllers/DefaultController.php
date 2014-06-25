<?php

class DefaultController extends Controller
{
    public $layout = '/layouts/layout';

	public function actionIndex()
	{
        if (Yii::app()->user->isGuest) {
            Yii::app()->user->returnUrl = "/demo";

            $this->redirect('site/login');
        }

		$this->render('index');
	}

    public function actionAjaxGetSecurityToken() {
        $token = $this->getSecurityToken();

        $result = array(
            "status" => $token ? true : false,
            "token" => $token
        );

        $this->renderJSON($result);
        $this->endApp();
    }

    public function actionAjaxGetIncidentInfo() {
        syslog(LOG_INFO, "Action AjaxGetIncidentInfo start.");

        if (/*!YII_DEBUG && */!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $token = Yii::app()->request->getParam('token');
        $center = Yii::app()->request->getParam('center');
        $radius = Yii::app()->request->getParam('radius');

//        syslog(LOG_INFO, "token: " . $token);
//        syslog(LOG_INFO, "center: " . $center);
//        syslog(LOG_INFO, "radius: " . $radius);

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "cache-control: private, max-age=0, no-cache"
//                'content' => $content
            )
        ));
        $result = file_get_contents('http://api.sandbox.inrix.com/Traffic/Inrix.ashx?Action=GetIncidentsInRadius&Center=' . $center
            . '&Radius=' . $radius . '&Token=' . $token . '&IncedentType=Incidents', false, $context);
        $resultXML = simplexml_load_string($result);

        syslog(LOG_INFO, "XML result created: " . $resultXML);

        $response = array(
            "status" => count($resultXML->Incidents) > 0,
            "incidents" => $resultXML->asXML()
        );

        $this->renderJSON($response);
        $this->endApp();

        syslog(LOG_INFO, "Action AjaxGetIncidentInfo end.");
    }

    public function actionAjaxGetWeatherInRadius() {
        syslog(LOG_INFO, "Action AjaxGetWeatherInRadius start.");

        if (/*!YII_DEBUG && */!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $token = Yii::app()->request->getParam('token');
        $center = Yii::app()->request->getParam('center');
        $radius = Yii::app()->request->getParam('radius');

//        syslog(LOG_INFO, "token: " . $token);
//        syslog(LOG_INFO, "center: " . $center);
//        syslog(LOG_INFO, "radius: " . $radius);

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "cache-control: private, max-age=0, no-cache"
            )
        ));
        $result = file_get_contents(Yii::app()->params['Inrix']['inrixAPIUrl'] . "?Action=GetWeatherInRadius&Center=" . $center
            . "&Radius=" . $radius . "&Token=" . $token, false, $context);
        $resultXML = simplexml_load_string($result);

        syslog(LOG_INFO, "XML result created.");

        $response = array(
            "status" => count($resultXML->SegmentSpeedResults) > 0,
            "weather" => $resultXML->asXML()
        );

        $this->renderJSON($response);
        $this->endApp();

        syslog(LOG_INFO, "Action AjaxGetWeatherInRadius end.");
    }

    public function actionAjaxGetSegmentSpeedInRadius() {
        syslog(LOG_INFO, "Action AjaxGetSegmentSpeedInRadius start.");

        if (/*!YII_DEBUG && */!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $token = Yii::app()->request->getParam('token');
        $center = Yii::app()->request->getParam('center');
        $radius = Yii::app()->request->getParam('radius');

//        syslog(LOG_INFO, "token: " . $token);
//        syslog(LOG_INFO, "center: " . $center);
//        syslog(LOG_INFO, "radius: " . $radius);

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "cache-control: private, max-age=0, no-cache"
            )
        ));
        $result = file_get_contents(Yii::app()->params['Inrix']['inrixAPIUrl'] . "?Action=GetSegmentSpeedInRadius&Center="
            . $center . "&Radius=" . $radius . "&Token=" . $token, false, $context);
        $resultXML = simplexml_load_string($result);

        $response = array(
            "status" => count($resultXML->SegmentSpeedResults) > 0,
            "segmentSpeed" => $resultXML->asXML()
        );

        $this->renderJSON($response);
        $this->endApp();

        syslog(LOG_INFO, "Action AjaxGetWeatherInBoxFGC end.");
    }

    public function actionGalaxyCallBack() {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "cache-control: private, max-age=0, no-cache"
            )
        ));
        $result = file_get_contents(Yii::app()->params['galaxyDomainUrl'] . "?i_user=rgralert&" .
            "i_password=123&i_password=123&i_stationId=-1&i_command=event&i_param1=accident&callback=", false, $context);

        $response = array(
            "status" => $result ? true : false,
            "result" => $result
        );

        $this->renderJSON($response);
        $this->endApp();
    }


    /**
     * Get Security Token for vendor
     * @return bool
     * @throws InvalidArgumentException
     */
    private function getSecurityToken() {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "cache-control: private, max-age=0, no-cache\r\n"
            )
        ));
        $result = file_get_contents(Yii::app()->params['Inrix']['inrixAPIUrl'] . "?Action=GetSecurityToken&VendorID="
            . Yii::app()->params['Inrix']['vendorId'] . "&ConsumerID=" . Yii::app()->params['Inrix']['consumerId'], false, $context);
        $resultXML = simplexml_load_string($result);

        if (!$resultXML->AuthResponse->AuthToken) {
            throw new InvalidArgumentException("Auth token could not be found.");
        }

        syslog(LOG_INFO, "AuthToken: " . $resultXML->AuthResponse->AuthToken);

        return is_object($resultXML->AuthResponse->AuthToken)
            ? $resultXML->AuthResponse->AuthToken->__toString()
            : false;
    }
}
