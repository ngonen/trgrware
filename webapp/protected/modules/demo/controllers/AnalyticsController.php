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

    public function actionAjaxGetLocationList() {
        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $response = ['status' => false];
        $storageStatisticPath = Yii::app()->params['eventStatisticStoragePath'];
        $response['categories'] = [];

//        $response['series'] = [[
//            'name' => 'Count',
//            'showInLegend' => false,
//            'data' => []
//        ]];
//
//        if (is_file($storageStatisticPath)) {
//            $statistic = $this->readStorage($storageStatisticPath);
//
//            foreach ($statistic as $id => $asset) {
//                $index = array_search($asset['locationName'], $response['categories']);
//
//                if ($index >= 0 && $index !== false) {
//                    $response['series'][0]['data'][$index] += 1;
//                } else {
//                    array_push($response['categories'], $asset['locationName']);
//                    array_push($response['series'][0]['data'], 1);
//                }
//            }
//        }

        $response['series'] = [];
        $data = [];

        if (is_file($storageStatisticPath)) {
            $statistic = $this->readStorage($storageStatisticPath);

            // Prepare categories and data array
            foreach ($statistic as $id => $asset) {
                $index = array_search($asset['locationName'], $response['categories']);

                if ($index === false) {
                    array_push($response['categories'], $asset['locationName']);
                    array_push($data, null);
                }
            }

            foreach ($statistic as $id => $asset) {
                foreach ($asset['statistic'] as $k => $v) {
                    $series = $this->getEventNameByKey($k);
                    $eventType = $series['name'];
                    $index = array_search($asset['locationName'], $response['categories']);

                    if ($v !== null) {
                        if (isset($response['series'][$eventType])) {
                            $response['series'][$eventType]['data'][$index] += 1;
                        } else {
                            $response['series'][$eventType] = $series;
                            $response['series'][$eventType]['data'] = $data;
                            $response['series'][$eventType]['data'][$index] = 1;
                        }
                    }
                }
            }
        }

        if (count($response['series'])) {
            $response['status'] = true;
            $response['series'] = array_values($response['series']);
        }

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'EventStatistic' finished.");

        $this->endApp();
    }





    private function getSeriesForEventList() {
        return [
            IDemoBaseController::WEATHER_TEMPERATURE => [name => "Temperature", color => '#d11430', data => []],
            IDemoBaseController::WEATHER_WIND_SPEED => [name => "Wind", color => "#7cb5ec", data => []],
            IDemoBaseController::WEATHER_RAIN => [name => "Rain", color => "#434348", color => "#", data => []],
            IDemoBaseController::WEATHER_SUNNY => [name => "Sunny", color => "#90ed7d", data => []],
            IDemoBaseController::WEATHER_CLOUDY => [name => "Cloudy", color => "#f7a35c", data => []],
            IDemoBaseController::WEATHER_SNOW => [name => "Snow", color => "#4072b4", data => []],
            IDemoBaseController::WEATHER_THUNDER_STORM => [name => "Thunderstorm", color => "#", data => []],
            IDemoBaseController::WEATHER_STORM => [name => "Storm", color => "#a2014c", data => []],
            IDemoBaseController::TRAFFIC_FLOW => [name => "Flow", color => "#e370b9", data => []],
            IDemoBaseController::TRAFFIC_INCIDENTS => [name => "Incidents", color => "#848484", data => []],
            IDemoBaseController::TWITTER_HASH_TAG => [name => "Twitter hashtag", color => "#31ad51", data => []]
        ];
    }

    private function getAssetName($asset) {
        foreach ($asset['statistic'] as $k => $v) {
            if (isset($v)) {
                return $this->getEventNameByKey($k);
            }
        }
    }

    private function getEventNameByKey($key) {
        $values = $this->getSeriesForEventList();

        return $values[$key];
    }
}