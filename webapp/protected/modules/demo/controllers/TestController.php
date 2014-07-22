<?php

class TestController extends IDemoBaseController {
    // Test function
    public function actionAjaxGetSecurityToken() {
        $token = $this->getSecurityToken();

        $result = array(
            "status" => $token ? true : false,
            "token" => $token
        );

        $this->renderJSON($result);
        $this->endApp();
    }

    // Test function
    public function actionAjaxGetIncidentInfo() {
        syslog(LOG_INFO, "Action AjaxGetIncidentInfo start.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $center = Yii::app()->request->getParam('center');
        $radius = Yii::app()->request->getParam('radius');
        $resultXML = $this->getIncidentInfo($center, $radius);

        syslog(LOG_INFO, "XML result created: " . $resultXML);

        $response = array(
            "status" => count($resultXML->Incidents) > 0,
            "incidents" => $resultXML->asXML()
        );

        $this->renderJSON($response);
        $this->endApp();

        syslog(LOG_INFO, "Action AjaxGetIncidentInfo end.");
    }

    // Test function
    public function actionAjaxGetSegmentSpeedInRadius() {
        syslog(LOG_INFO, "Action AjaxGetSegmentSpeedInRadius start.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $center = Yii::app()->request->getParam('center');
        $radius = Yii::app()->request->getParam('radius');
        $resultXML = $this->getSegmentSpeedInRadius($center, $radius);

        $response = array(
            "status" => count($resultXML->SegmentSpeedResults) > 0,
            "segmentSpeed" => $resultXML->asXML()
        );

        $this->renderJSON($response);
        $this->endApp();

        syslog(LOG_INFO, "Action AjaxGetWeatherInBoxFGC end.");
    }
} 