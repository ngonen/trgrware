<?php

class AnalyticsController extends IDemoBaseController {

    public function actionIndex() {
        return $this->render("index");
    }

    public function actionAjaxGetEventStatistic() {
        syslog(LOG_INFO, "Action 'AjaxGetEventStatistic' started.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $assetId = Yii::app()->request->getParam('assetId');
        $response = ['status' => false];
        $storageStatisticPath = Yii::app()->params['eventStatisticStoragePath'];

        if (!$assetId) {
            $response['errorMessage'] = "Asset ID not found";

            $this->renderJSON($response);
            $this->endApp();
        }

        if (is_file($storageStatisticPath)) {
            $assetStatistic = [];
            $statistic = $this->readStorage($storageStatisticPath);

            if (!isset($statistic[$assetId])) {
                $response['errorMessage'] = "Statistic does not exists for this event type.";

                $this->renderJSON($response);
                $this->endApp();
            }

            foreach ($statistic[$assetId]['statistic'] as $k => $v) {
                $assetStatistic[$k] = isset($v) ? $v : 0;
            }

            $response['status'] = true;
            $response['statistic'] = $assetStatistic;
        } else {
            $response['errorMessage'] = "Events have not been triggered yet.";
        }

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'AjaxGetEventStatistic' finished.");

        $this->endApp();
    }

    public function actionAjaxGetEventList() {
        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $response = ['status' => false];
        $storageStatisticPath = Yii::app()->params['eventStatisticStoragePath'];
        $response['categories'] = [];
        $series = $this->getSeriesForEventList();

        if (is_file($storageStatisticPath)) {
            $statistic = $this->readStorage($storageStatisticPath);

            foreach ($statistic as $id => $asset) {
                array_push($response['categories'], $asset['name']);

                foreach ($asset['statistic'] as $e => $event) {
                    array_push($series[$e]['data'], isset($event) ? $event : null);
                }
            }
        }

        if (count($series)) {
            $response['status'] = true;
        }

        $response['series'] = array_values($series);

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'EventStatistic' finished.");

        $this->endApp();
    }

    // TODO: finish
    public function actionAjaxGetLocationList() {
        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $response = ['status' => false];
        $storageStatisticPath = Yii::app()->params['eventStatisticStoragePath'];
        $response['categories'] = [];
        $response['series'] = [];
        array_push($response['series'], [
            'name' => 'Location',
            'data' => []
        ]);

//        $series = $this->getSeriesForEventList();

        if (is_file($storageStatisticPath)) {
            $statistic = $this->readStorage($storageStatisticPath);

            foreach ($statistic as $id => $asset) {
                if (in_array($asset['locationName'], $response['categories'])) {

                } else {
                    array_push($response['categories'], $asset['locationName']);
                }

//                $index = array_search($asset['locationName'], $response['categories']);
//
//                if (in_array($asset['locationName'], $response['categories'])) {
//                    $response['series']['data'][$index] += 1;
//                } else {
//                    array_push($response['categories'], $asset['locationName']);
//                    array_push($response['series'][0]['data'], 1);
//                }
            }
        }

        if (count($response['series'])) {
            $response['status'] = true;
        }

//        $response['series'] = array_values($series);

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'EventStatistic' finished.");

        $this->endApp();
    }





    private function getSeriesForEventList() {
        return [
            IDemoBaseController::WEATHER_TEMPERATURE => [name => "Temperature", data => []],
            IDemoBaseController::WEATHER_WIND_SPEED => [name => "Wind", data => []],
            IDemoBaseController::WEATHER_RAIN => [name => "Rain", data => []],
            IDemoBaseController::WEATHER_SUNNY => [name => "Sunny", data => []],
            IDemoBaseController::WEATHER_CLOUDY => [name => "Cloudy", data => []],
            IDemoBaseController::WEATHER_SNOW => [name => "Snow", data => []],
            IDemoBaseController::WEATHER_THUNDER_STORM => [name => "Thunderstorm", data => []],
            IDemoBaseController::WEATHER_STORM => [name => "Storm", data => []],
            IDemoBaseController::TRAFFIC_FLOW => [name => "Flow", data => []],
            IDemoBaseController::TRAFFIC_INCIDENTS => [name => "Incidents", data => []],
            IDemoBaseController::TWITTER_HASH_TAG => [name => "Twitter hashtag", data => []]
        ];
    }
}