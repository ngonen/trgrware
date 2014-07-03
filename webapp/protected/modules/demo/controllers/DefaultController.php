<?php

class DefaultController extends DemoBaseController
{
    public $layout = '/layouts/layout';

	public function actionIndex() {
        if (Yii::app()->user->isGuest) {
            Yii::app()->user->returnUrl = "/demo";

            $this->redirect('site/login');
        }

        // Clear file storage
        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            syslog(LOG_INFO, "Remove storage file.");

            unlink($filePath);
        }

		$this->render('index');
	}

    // TODO: remove as redundant
    public function actionAjaxGetSecurityToken() {
        $token = $this->getSecurityToken();

        $result = array(
            "status" => $token ? true : false,
            "token" => $token
        );

        $this->renderJSON($result);
        $this->endApp();
    }

    // TODO: remove as redundant
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

    // TODO: remove as redundant
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

    public function actionAjaxGetWeatherInRadius() {
        syslog(LOG_INFO, "Action AjaxGetWeatherInRadius start.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $response = [
            "status" => false
        ];

        $center = Yii::app()->request->getParam('center');
        $radius = Yii::app()->request->getParam('radius');
        $refreshKey = Yii::app()->request->getParam('refreshKey');

        if (!$center || !$radius || !$refreshKey) {
            $response["errorMessage"] = "Check your parameters".

                $this->renderJSON($response);
            $this->endApp();
        }

        $resultXML = $this->getWeatherInRadius($center, $radius);
        $response = array(
            "status" => $resultXML->Weather->Conditions && count($resultXML->Weather->Conditions->Station) > 0,
            "refreshKey" => $refreshKey,
            "weather" => $resultXML->asXML()
        );

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action AjaxGetWeatherInRadius end.");

        $this->endApp();
    }

    /**
     * Adds asset to events queue
     * This queue will be used by cron for notifying subscribed users
     */
    public function actionRegisterEvent() {
        syslog(LOG_INFO, "Action 'RegisterEvent' started.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $event = Yii::app()->request->getParam('eventType');
        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);
        $response = [
            "status" => false
        ];
        $eventParams = [
            'id' => Yii::app()->request->getParam('asset_id'),
            'center' => Yii::app()->request->getParam('center'),
            'radius' => Yii::app()->request->getParam('radius'),
            'url' => Yii::app()->request->getParam('callbackURL')
        ];

        switch ($event) {
            case DemoBaseController::WEATHER_TEMPERATURE: {
                $eventParams['temperature'] = Yii::app()->request->getParam('temperature');
                $eventParams['threshold'] = Yii::app()->request->getParam('threshold');

                break;
            }

            case DemoBaseController::TRAFFIC_INCIDENTS: {
                $eventParams['severity'] = Yii::app()->request->getParam('severity');

                break;
            }

            case DemoBaseController::TRAFFIC_FLOW: {
                $eventParams['speedUnder'] = Yii::app()->request->getParam('speedUnder');

                break;
            }
        }

        if (is_file($filePath)) {
            syslog(LOG_INFO, "Storage exists. Start to unserialize.");

            $fc = unserialize(file_get_contents($filePath, 0, $ctx));
        } else {
            syslog(LOG_INFO, "Storage does not exist. Start to create.");

            $fc = [
                DemoBaseController::WEATHER_TEMPERATURE => [],
                DemoBaseController::TRAFFIC_INCIDENTS => [],
                DemoBaseController::TRAFFIC_FLOW => []
            ];
        }

        $fc[$event][] = $eventParams;
        $isPut = file_put_contents($filePath, serialize($fc), 0, $ctx);

        if ($isPut) {
            syslog(LOG_INFO, "Storage has been successfully saved.");

            $response["status"] = $isPut > 0;
            $response["message"] = "Your event has been successfully queued.";
            $response["statistic"] = [
                DemoBaseController::WEATHER_TEMPERATURE => count($fc[DemoBaseController::WEATHER_TEMPERATURE]),
                DemoBaseController::TRAFFIC_INCIDENTS => count($fc[DemoBaseController::TRAFFIC_INCIDENTS]),
                DemoBaseController::TRAFFIC_FLOW => count($fc[DemoBaseController::TRAFFIC_FLOW])
            ];
            $response["content"] = $fc;
            $response["assetId"] = Yii::app()->request->getParam('asset_id');
            $response["eventType"] = $event;
        } else {
            $response["errorMessage"] = "Error when tried to store event.";
        }

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'RegisterEvent' finished.");

        $this->endApp();
    }

    /**
     * Update triggers in storage for specific type of event
     * @throws CHttpException
     */
    public function actionUpdateEvent() {
        syslog(LOG_INFO, "Action 'UpdateEvent' started.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $assetId = Yii::app()->request->getParam('asset_id');
        $event = Yii::app()->request->getParam('eventType');
        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);
        $response = [
            "status" => false
        ];
        $eventParams = [
            'id' => $assetId,
            'center' => Yii::app()->request->getParam('center'),
            'radius' => Yii::app()->request->getParam('radius'),
            'url' => Yii::app()->request->getParam('callbackURL')
        ];
        $isFound = false;
        $storageExists = is_file($filePath);

        switch ($event) {
            case DemoBaseController::WEATHER_TEMPERATURE: {
                $eventParams['temperature'] = Yii::app()->request->getParam('temperature');
                $eventParams['threshold'] = Yii::app()->request->getParam('threshold');

                break;
            }

            case DemoBaseController::TRAFFIC_INCIDENTS: {
                $eventParams['severity'] = Yii::app()->request->getParam('severity');

                break;
            }

            case DemoBaseController::TRAFFIC_FLOW: {
                $eventParams['speedUnder'] = Yii::app()->request->getParam('speedUnder');

                break;
            }
        }

        if ($storageExists) {
            $fc = unserialize(file_get_contents($filePath, 0, $ctx));

            foreach ($fc[$event] as $key => $value) {
                if ($value['id'] == $assetId) {
                    $isFound = true;
                    $fc[$event][$key] = $eventParams;

                    break;
                }
            }
        }

        if (!$isFound || !$storageExists) {
            $fc = [
                DemoBaseController::WEATHER_TEMPERATURE => [],
                DemoBaseController::TRAFFIC_INCIDENTS => [],
                DemoBaseController::TRAFFIC_FLOW => []
            ];
            $fc[$event][] = $eventParams;
        }

        $isPut = file_put_contents($filePath, serialize($fc), 0, $ctx);

        if ($isPut) {
            $response["status"] = $isPut > 0;
            $response["message"] = $isFound
                ? "Your event has been successfully updated."
                : "Your event has been successfully created.";
            $response["statistic"] = [
                DemoBaseController::WEATHER_TEMPERATURE => count($fc[DemoBaseController::WEATHER_TEMPERATURE]),
                DemoBaseController::TRAFFIC_INCIDENTS => count($fc[DemoBaseController::TRAFFIC_INCIDENTS]),
                DemoBaseController::TRAFFIC_FLOW => count($fc[DemoBaseController::TRAFFIC_FLOW])
            ];
            $response["content"] = $fc;
        } else {
            $response["errorMessage"] = "Error when tried to store event.";
        }

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'UpdateEvent' finished.");

        $this->endApp();
    }

    /**
     * Update triggers in storage for asset
     * @throws CHttpException
     */
    public function actionUpdateAsset() {
        syslog(LOG_INFO, "Action 'UpdateAsset' started.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $assetId = Yii::app()->request->getParam('asset_id');
        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);
        $response = ["status" => false];
        $storageExists = is_file($filePath);

        if ($storageExists) {
            $fc = unserialize(file_get_contents($filePath, 0, $ctx));

            if (!is_array($fc)) {
                file_put_contents($filePath, "", 0, $ctx);

                $response["errorMessage"] = "Data in storage is not correct. Reload the page.";

                $this->renderJSON($response);
                $this->endApp();
            }

            foreach ($fc as $key => $value) {
                foreach ($value as $i => $item) {
                    if ($item['id'] == $assetId) {
                        $fc[$key][$i]['center'] = Yii::app()->request->getParam('center');
                    }
                }
            }

            $isPut = file_put_contents($filePath, serialize($fc), 0, $ctx);

            if ($isPut) {
                $response["status"] = $isPut > 0;
                $response["message"] = "Your asset has been successfully updated.";
                $response["statistic"] = [
                    DemoBaseController::WEATHER_TEMPERATURE => count($fc[DemoBaseController::WEATHER_TEMPERATURE]),
                    DemoBaseController::TRAFFIC_INCIDENTS => count($fc[DemoBaseController::TRAFFIC_INCIDENTS]),
                    DemoBaseController::TRAFFIC_FLOW => count($fc[DemoBaseController::TRAFFIC_FLOW])
                ];
                $response["content"] = $fc;
            } else {
                $response["errorMessage"] = "Error when tried to store event.";
            }
        } else {
            $response["errorMessage"] = "Events for this asset were not found in storage. Please create it again.";
        }

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'UpdateAsset' started.");

        $this->endApp();
    }

    /**
     * Delete created asset on the map
     */
    public function actionDeleteAsset() {
        syslog(LOG_INFO, "Action 'DeleteAsset' started.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $response = $this->deleteAssetInfo(Yii::app()->request->getParam('assetId'));

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'DeleteAsset' finished.");

        $this->endApp();
    }

    /**
     * Delete event for asset
     */
    public function actionDeleteEvent() {
        syslog(LOG_INFO, "Action 'DeleteEvent' started.");

        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('403', 'Forbidden access.');
        }

        $response = $this->deleteAssetInfo(Yii::app()->request->getParam('assetId'), Yii::app()->request->getParam('eventType'));

        $this->renderJSON($response);

        syslog(LOG_INFO, "Action 'DeleteEvent' finished.");

        $this->endApp();
    }





    private function deleteAssetInfo($assetId, $eventType = false) {
        $response = ["status" => false];
        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);
        $isFound = false;

        if (is_file($filePath)) {
            $fc = unserialize(file_get_contents($filePath, 0, $ctx));

            if (is_array($fc)) {
                if ($eventType) {
                    syslog(LOG_INFO, "Start delete event.");

                    foreach ($fc[$eventType] as $key => $value) {
                        if ($value['id'] == $assetId) {
                            unset($fc[$eventType][$key]);

                            $isFound = true;
                        }
                    }

                    $response["eventType"] = $eventType;
                } else {
                    syslog(LOG_INFO, "Start delete asset.");

                    foreach ($fc as $key => $value) {
                        foreach ($value as $i => $item) {
                            if ($item['id'] == $assetId) {
                                unset($fc[$key][$i]);

                                $isFound = true;
                            }
                        }
                    }
                }

                if (!$isFound) {
                    $response["errorMessage"] = $eventType
                        ? "Event has not been found in storage."
                        : "Asset has not been found in storage.";

                    return $response;
                }

                $isPut = file_put_contents($filePath, serialize($fc), 0, $ctx);

                if ($isPut) {
                    $response["status"] = $isPut > 0;
                    $response["assetId"] = $assetId;
                    $response["statistic"] = [
                        DemoBaseController::WEATHER_TEMPERATURE => count($fc[DemoBaseController::WEATHER_TEMPERATURE]),
                        DemoBaseController::TRAFFIC_INCIDENTS => count($fc[DemoBaseController::TRAFFIC_INCIDENTS]),
                        DemoBaseController::TRAFFIC_FLOW => count($fc[DemoBaseController::TRAFFIC_FLOW])
                    ];
                    $response["content"] = $fc;
                    $response["message"] = $eventType
                        ? "Event has been successfully deleted."
                        : "Asset has been successfully deleted.";
                } else {
                    $response["errorMessage"] = "Error when tried to store event.";
                }
            } else {
                file_put_contents($filePath, "", 0, $ctx);

                $response["errorMessage"] = "Data in storage is not correct. Reload the page.";
            }
        } else {
            $response["errorMessage"] = "FileStorage was not found. Reload the page.";
        }

        return $response;
    }
}
