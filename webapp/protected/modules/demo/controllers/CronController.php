<?php

class CronController extends IDemoBaseController
{
    public function actionWeatherTemperatureNotifier() {
        syslog(LOG_INFO, "Action WeatherNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = $this->getStreamContextForPlanText();

        if (is_file($filePath)) {
            $subscribers = unserialize(file_get_contents($filePath, 0, $ctx))[IDemoBaseController::WEATHER_TEMPERATURE];
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_WARNING, "Count of subscribers for 'weather temperature': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_INFO, "Start loop subscribers.");

                    foreach ($subscribers as $key => $value) {
                        $result = $this->getWeatherInRadius($value['center'], $value['radius'], $securityToken);

                        if ($result->Weather && $result->Weather->Conditions) {
                            foreach ($result->Weather->Conditions->Station as $k => $station) {
                                $actualTemperature = $station->Current->Temperature->attributes()['actual'];
                                $condition = $value['condition'] == IDemoBaseController::OVER
                                    ? $actualTemperature >= $value['threshold']
                                    : $actualTemperature < $value['threshold'];

                                syslog(LOG_INFO, "Actual temperature: " . $actualTemperature . ", expected temperature: "
                                    . $value['threshold'] . ", condition: " . $value['condition']);

                                if ($condition) {
                                    file_get_contents($value["url"], false, $streamContext);

                                    syslog(LOG_INFO, "[WeatherTemperature] Request '" . $key . "' for asset "
                                        . $value['id'] . " has been sent to url " . $value["url"]);

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Empty result.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for weather temperature were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherNotifier finished.");
    }

    public function actionTrafficIncidentsNotifier() {
        syslog(LOG_INFO, "Action IncidentsNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = $this->getStreamContextForPlanText();

        if (is_file($filePath)) {
            $subscribers = unserialize(file_get_contents($filePath, 0, $ctx))[IDemoBaseController::TRAFFIC_INCIDENTS];
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_WARNING, "Count of subscribers for 'traffic incident': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_INFO, "Start loop 'traffic incident' subscribers.");

                    foreach ($subscribers as $key => $subscriber) {
                        $result = $this->getIncidentInfo($subscriber['center'], $subscriber['radius'], $securityToken);

                        if ($result->Incidents && count($result->Incidents->Incident)) {
                            file_get_contents($subscriber["url"], false, $streamContext);

                            syslog(LOG_INFO, "[TrafficIncidents] Request '" . $key . "' for asset "
                                . $subscriber['id'] . " has been sent to url " . $subscriber["url"]);
                        } else {
                            syslog(LOG_INFO, "Empty result.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for traffic incidents were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action IncidentsNotifier finished.");
    }

    public function actionTrafficSpeedNotifier() {
        syslog(LOG_INFO, "Action SpeedNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = $this->getStreamContextForPlanText();

        if (is_file($filePath)) {
            $subscribers =  unserialize(file_get_contents($filePath, 0, $ctx))[IDemoBaseController::TRAFFIC_FLOW];
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_WARNING, "Count of subscribers for 'traffic speed': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_INFO, "Start loop 'traffic speed' subscribers.");

                    foreach ($subscribers as $key => $value) {
                        $result = $this->getSegmentSpeedInRadius($value['center'], $value['radius'], $securityToken);

                        if ($result->SegmentSpeedResultSet && $result->SegmentSpeedResultSet->SegmentSpeedResults) {
                            foreach ($result->SegmentSpeedResultSet->SegmentSpeedResults->Segment as $k => $segment) {
                                $actualSpeed = $segment->attributes()['speed'];
                                $condition = $value['condition'] == "Over"
                                    ? $actualSpeed >= $value['threshold']
                                    : $actualSpeed < $value['threshold'];

                                if ($condition) {
                                    file_get_contents($value['url'], false, $streamContext);

                                    syslog(LOG_INFO, "[TrafficSpeed] Request '" . $key . "' for asset "
                                        . $value['id'] . " has been sent to url " . $value['url']);

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Empty result.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'traffic speed' were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action SpeedNotifier finished.");
    }

    // TODO: Finish
    // TODO: Test
    public function actionTwitterHashTagNotifier() {
        syslog(LOG_INFO, "Action TwitterHashTagNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = $this->getStreamContextForPlanText();

        if (is_file($filePath)) {
            $subscribers =  unserialize(file_get_contents($filePath, 0, $ctx))[IDemoBaseController::TWITTER_HASH_TAG];
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Twitter hashtag': " . $count);

                $streamContext = $this->getStreamContext();

                foreach ($subscribers as $key => $subscriber) {
                    // TODO: Get result from Twitter [FIX]
                    $result = "";
                    // TODO: Get count of hashtags [FIX]
                    $tagsCount = count($result);

                    if ($tagsCount) {
                        foreach ($subscriber['campaigns'] as $c => $campaign) {
                            if ($tagsCount >= $c) {
                                file_get_contents($campaign['url'], false, $streamContext);

                                syslog(LOG_INFO, "[Twitter hashtag] Request '" . $key . "' for asset "
                                    . $subscriber['id'] . ", hashtag " . $subscriber['hashTag'] . ", count "
                                    . $c . " has been sent to url " . $campaign['url']);

                                break;
                            }
                        }
                    } else {
                        syslog(LOG_INFO, "Result is empty.");
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Twitter hashtags' were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action TwitterHashTagNotifier finished.");
    }

    public function actionWeatherWindSpeedNotifier() {
        syslog(LOG_INFO, "Action WeatherWindNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = $this->getStreamContextForPlanText();

        if (is_file($filePath)) {
            $subscribers = unserialize(file_get_contents($filePath, 0, $ctx))[IDemoBaseController::WEATHER_WIND_SPEED];
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Weather wind speed': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_INFO, "Start loop subscribers.");

                    foreach ($subscribers as $key => $subscriber) {
                        $result = $this->getWeatherInRadius($subscriber['center'], $subscriber['radius'],
                            $securityToken);

                        if ($result->Weather && $result->Weather->Conditions) {
                            foreach ($result->Weather->Conditions->Station as $k => $station) {
                                $actualSpeed = $station->Current->Wind->attributes()['speed'];

                                syslog(LOG_INFO, "Actual speed: " . $actualSpeed . ", expected speed: "
                                    . $subscriber['speed']);

                                if ($subscriber['speed'] && $actualSpeed >= $subscriber['speed']) {
                                    file_get_contents($subscriber["url"], false, $streamContext);

                                    syslog(LOG_INFO, "[WeatherWind Speed] Request '" . $key . "' for asset "
                                        . $subscriber['id'] . " has been sent to url " . $subscriber["url"]);

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Result is empty.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for weather temperature were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherWindNotifier finished.");
    }
}
