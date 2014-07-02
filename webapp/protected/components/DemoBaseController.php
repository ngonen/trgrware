<?php

abstract class DemoBaseController extends Controller {
    const WEATHER_TEMPERATURE = "weather_temperature";
    const TRAFFIC_FLOW = "traffic_flow";
    const TRAFFIC_INCIDENTS = "traffic_accident";

    /**
     * Get Security Token for vendor
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function getSecurityToken() {
        syslog(LOG_INFO, "Check if security token exists in session.");

        if (Yii::app()->session['tokenInfo']) {
            $token = Yii::app()->session['tokenInfo']['token'];
            $expiry = Yii::app()->session['tokenInfo']['expiry'];

            date_default_timezone_set('UTC');

            if (strtotime($expiry) > time()) {
                syslog(LOG_INFO, "Get security token from session. Expiry: " . $expiry . ", CurrentTime: " . date("Y-m-d h-m-s", time()));

                return $token;
            }
        }

        syslog(LOG_INFO, "Security token does not exist in session. Generate new.");

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "cache-control: private, max-age=0, no-cache\r\n"
            )
        ));
        $result = file_get_contents(Yii::app()->params['Inrix']['inrixAPIUrl'] . "?Action=GetSecurityToken&VendorID="
            . Yii::app()->params['Inrix']['vendorId'] . "&ConsumerID=" . Yii::app()->params['Inrix']['consumerId'],
            false, $context);
        $resultXML = simplexml_load_string($result);

        if (is_object($resultXML->AuthResponse->AuthToken)) {
            $token = $resultXML->AuthResponse->AuthToken->__toString();
            $expiry = $resultXML->AuthResponse->AuthToken->attributes()['expiry']->__toString();

            Yii::app()->session['tokenInfo'] = [
                'token' => $token,
                'expiry' => $expiry
            ];

            syslog(LOG_INFO, "AuthToken: " . $token . ", expiry: " . $expiry);

            return $token;
        }

        syslog(LOG_WARNING, "Could not get security token.");

        return false;
    }

    protected function getWeatherInRadius($center, $radius, $securityToken = false) {
        $token = $securityToken ? $securityToken : $this->getSecurityToken();
        $context = $this->getStreamContext();
        $result = file_get_contents(Yii::app()->params['Inrix']['inrixAPIUrl'] . "?Action=GetWeatherInRadius&Center="
            . $center . "&Radius=" . $radius . "&Token=" . $token, false, $context);

        return simplexml_load_string($result);
    }

    protected function getIncidentInfo($center, $radius, $securityToken = false) {
        $token = $securityToken ? $securityToken : $this->getSecurityToken();
        $context = $this->getStreamContext();
        $result = file_get_contents(Yii::app()->params['Inrix']['inrixAPIUrl'] . '?Action=GetIncidentsInRadius&Center='
            . $center . '&Radius=' . $radius . '&Token=' . $token . '&IncedentType=Incidents', false, $context);

        return simplexml_load_string($result);
    }

    protected function getSegmentSpeedInRadius($center, $radius, $securityToken = false) {
        $token = $securityToken ? $securityToken : $this->getSecurityToken();
        $context = $this->getStreamContext();
        $result = file_get_contents(Yii::app()->params['Inrix']['inrixAPIUrl'] . "?Action=GetSegmentSpeedInRadius&Center="
            . $center . "&Radius=" . $radius . "&Token=" . $token, false, $context);

        return simplexml_load_string($result);
    }

    protected function getStreamContext() {
        return stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "cache-control: private, max-age=0, no-cache"
            )
        ));
    }


    // TODO: delete
    public function actionGalaxyCallBack() {
        $context = $this->getStreamContext();
        $result = file_get_contents(Yii::app()->params['galaxyDomainUrl'] . "?i_user=rgralert&" .
            "i_password=123&i_password=123&i_stationId=-1&i_command=event&i_param1=accident&callback=", false, $context);

        $response = array(
            "status" => $result ? true : false,
            "result" => $result
        );

        $this->renderJSON($response);
        $this->endApp();
    }
} 