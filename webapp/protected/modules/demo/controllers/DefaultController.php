<?php

class DefaultController extends IDemoBaseController
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

        $response = ["status" => false];
        $center = Yii::app()->request->getParam('center');
        $radius = Yii::app()->request->getParam('radius');
        $refreshKey = Yii::app()->request->getParam('refreshKey');

        if (!$center || !$radius || !$refreshKey) {
            $response["errorMessage"] = "Check your parameters";

            $this->renderJSON($response);
            $this->endApp();
        }

        $resultXML = $this->getWeatherInRadius($center, $radius);
        $status = $resultXML->Weather->Conditions && count($resultXML->Weather->Conditions->Station) > 0;

        if ($status) {
            $response["status"] = $status;
            $response['message'] = "Weather stations were successfully returned.";
            $response["refreshKey"] = $refreshKey;
            $response["weather"] = $resultXML->asXML();
        } else {
            $response['errorMessage'] = "Stations were not found.";
        }

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

        $response = ["status" => false];
        $assetId = Yii::app()->request->getParam('assetId');
        $event = Yii::app()->request->getParam('eventType');

        if (!$event) {
            $response['errorMessage'] = "Type of event is not defined.";

            $this->renderJSON($response);
            $this->endApp();
        }

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = $this->getStreamContextForPlanText();

        if (is_file($filePath)) {
            syslog(LOG_INFO, "Storage exists. Start to unserialize.");

            $fc = $this->readStorage($filePath, $ctx);
        } else {
            syslog(LOG_INFO, "Storage does not exist. Start to create.");

            $fc = $this->createStorageArray();
        }

        $fc[$event][] = $this->updateEventParameters($event, $assetId);
        $isPut = $this->saveToStorage($filePath, $ctx, $fc);

        if ($isPut) {
            syslog(LOG_INFO, "Storage has been successfully saved.");

            $response["status"] = $isPut > 0;
            $response["message"] = "Event has been successfully registered.";
            $response["statistic"] = $this->getStatistic($fc);
            $response["content"] = $fc;
            $response["assetId"] = $assetId;
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

        $assetId = Yii::app()->request->getParam('assetId');
        $event = Yii::app()->request->getParam('eventType');
        $deleteEventType = Yii::app()->request->getParam('delete');
        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = $this->getStreamContextForPlanText();
        $response = ["status" => false];
        $isFound = false;

        if ($deleteEventType) {
            $res = $this->deleteAssetInfo($assetId, $deleteEventType);

            if (!$res['status']) {
                $this->renderJSON($res);
                $this->endApp();
            }
        }

        $storageExists = is_file($filePath);
        $eventParams = $this->updateEventParameters($event, $assetId);

        if ($storageExists) {
            syslog(LOG_INFO, "Storage exists. Start to unserialize.");

            $fc = $this->readStorage($filePath, $ctx);

            foreach ($fc[$event] as $key => $value) {
                if ($value['id'] == $assetId) {
                    $isFound = true;
                    $fc[$event][$key] = $eventParams;

                    break;
                }
            }
        } else {
            syslog(LOG_INFO, "Storage does not exist. Start to create.");

            $fc = $this->createStorageArray();
            $fc[$event][] = $eventParams;
        }

        if (!$isFound && $storageExists) {
            syslog(LOG_INFO, "Event was not found in existing storage. Add event.");

            $fc[$event][] = $eventParams;
        }

        $isPut = $this->saveToStorage($filePath, $ctx, $fc);

        if ($isPut) {
            $response["status"] = $isPut > 0;
            $response["message"] = $isFound
                ? "Your event has been successfully updated."
                : "Your event has been successfully created.";
            $response["statistic"] = $this->getStatistic($fc);
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

        $assetId = Yii::app()->request->getParam('assetId');
        $center = Yii::app()->request->getParam('center');
        $events = json_decode(Yii::app()->request->getParam('events'), true);
        $response = ["status" => false];
        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = $this->getStreamContextForPlanText();

        if (is_file($filePath)) {
            $fc = $this->readStorage($filePath, $ctx);

            if (!is_array($fc)) {
                $this->saveToStorage($filePath, $ctx);

                $response["errorMessage"] = "Data in storage is not correct. Reload the page.";

                $this->renderJSON($response);
                $this->endApp();
            }

            foreach ($fc as $key => $value) {
                foreach ($value as $i => $item) {
                    if ($item['id'] == $assetId) {
                        if ($events && $key == IDemoBaseController::TWITTER_HASH_TAG) {
                            krsort($events[IDemoBaseController::TWITTER_HASH_TAG]);

                            $fc[$key][$i]['campaigns'] = $events[IDemoBaseController::TWITTER_HASH_TAG];
                        } else {
                            $fc[$key][$i]['center'] = $center;

                            if (isset($events[$key])) {
                                $fc[$key][$i]['url'] = $events[$key];
                            }
                        }
                    }
                }
            }

            $isPut = $this->saveToStorage($filePath, $ctx, $fc);

            if ($isPut) {
                $response["status"] = $isPut > 0;
                $response["message"] = "Your asset has been successfully updated.";
                $response["statistic"] = $this->getStatistic($fc);
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
            $fc = $this->readStorage($filePath, $ctx);

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

                $isPut = $this->saveToStorage($filePath, $ctx, $fc);

                if ($isPut) {
                    $response["status"] = $isPut > 0;
                    $response["assetId"] = $assetId;
                    $response["statistic"] = $this->getStatistic($fc);
                    $response["content"] = $fc;
                    $response["message"] = $eventType
                        ? "Event has been successfully deleted."
                        : "Asset has been successfully deleted.";
                } else {
                    $response["errorMessage"] = "Error when tried to store event.";
                }
            } else {
                $this->saveToStorage($filePath, $ctx);

                $response["errorMessage"] = "Data in storage is not correct. Reload the page.";
            }
        } else {
            $response["errorMessage"] = "FileStorage was not found. Reload the page.";
        }

        return $response;
    }

    private function updateEventParameters($event, $assetId) {
        $params = ["id" => $assetId];

        switch ($event) {
            case IDemoBaseController::TRAFFIC_FLOW:
            case IDemoBaseController::WEATHER_TEMPERATURE: {
                $params['condition'] = Yii::app()->request->getParam('condition');
                $params['threshold'] = Yii::app()->request->getParam('threshold');

                break;
            }

            case IDemoBaseController::WEATHER_WIND_SPEED: {
                $params['speed'] = Yii::app()->request->getParam('speed');

                break;
            }

            case IDemoBaseController::TRAFFIC_INCIDENTS: {
                $params['severity'] = Yii::app()->request->getParam('severity');

                break;
            }

            case IDemoBaseController::TWITTER_HASH_TAG: {
                $campaigns = json_decode(Yii::app()->request->getParam('campaigns'), true);
                krsort($campaigns);

//                $params['retriggerPeriod'] = Yii::app()->request->getParam('retriggerPeriod');
                $params['hashTag'] = Yii::app()->request->getParam('hashTag');
                $params['userName'] = Yii::app()->request->getParam('userName');
                $params['campaigns'] = $campaigns;

                break;
            }

            case IDemoBaseController::WEATHER_WIND_SPEED:
            case IDemoBaseController::WEATHER_RAIN:
            case IDemoBaseController::WEATHER_SNOW:
            case IDemoBaseController::WEATHER_STORM:
            case IDemoBaseController::WEATHER_SUN:
            case IDemoBaseController::WEATHER_THUNDER_STORM: {
                break;
            }

            default: {
                $this->renderJSON([
                    'status' => false,
                    'errorMessage' => "This type of event is not supported."
                ]);
                $this->endApp();
            }
        }

        if ($event != IDemoBaseController::TWITTER_HASH_TAG) {
            $params['center'] = Yii::app()->request->getParam('center');
            $params['radius'] = Yii::app()->request->getParam('radius');
            $params['url'] = Yii::app()->request->getParam('callbackURL');
        }

        return $params;
    }

    private function createStorageArray() {
        return [
            IDemoBaseController::WEATHER_TEMPERATURE => [],
            IDemoBaseController::WEATHER_WIND_SPEED => [],
            IDemoBaseController::WEATHER_RAIN => [],
            IDemoBaseController::WEATHER_SNOW => [],
            IDemoBaseController::WEATHER_STORM => [],
            IDemoBaseController::WEATHER_SUN => [],
            IDemoBaseController::WEATHER_THUNDER_STORM => [],
            IDemoBaseController::TRAFFIC_INCIDENTS => [],
            IDemoBaseController::TRAFFIC_FLOW => [],
            IDemoBaseController::TWITTER_HASH_TAG => []
        ];
    }

    private function readStorage($filePath, $ctx) {
        return unserialize(file_get_contents($filePath, 0, $ctx));
    }

    private function saveToStorage($filePath, $ctx, $fc = "") {
        $data = "";

        if ($fc) {
            $data = serialize($fc);
        }

        return file_put_contents($filePath, $data, 0, $ctx);
    }

    private function getStatistic($fc) {
        return [
            IDemoBaseController::WEATHER_TEMPERATURE => count($fc[IDemoBaseController::WEATHER_TEMPERATURE]),
            IDemoBaseController::WEATHER_WIND_SPEED => count($fc[IDemoBaseController::WEATHER_WIND_SPEED]),
            IDemoBaseController::WEATHER_RAIN => count($fc[IDemoBaseController::WEATHER_RAIN]),
            IDemoBaseController::WEATHER_SNOW => count($fc[IDemoBaseController::WEATHER_SNOW]),
            IDemoBaseController::WEATHER_STORM => count($fc[IDemoBaseController::WEATHER_STORM]),
            IDemoBaseController::WEATHER_SUN => count($fc[IDemoBaseController::WEATHER_SUN]),
            IDemoBaseController::WEATHER_THUNDER_STORM => count($fc[IDemoBaseController::WEATHER_THUNDER_STORM]),
            IDemoBaseController::TRAFFIC_INCIDENTS => count($fc[IDemoBaseController::TRAFFIC_INCIDENTS]),
            IDemoBaseController::TRAFFIC_FLOW => count($fc[IDemoBaseController::TRAFFIC_FLOW]),
            IDemoBaseController::TWITTER_HASH_TAG => count($fc[IDemoBaseController::TWITTER_HASH_TAG])
        ];
    }
}
