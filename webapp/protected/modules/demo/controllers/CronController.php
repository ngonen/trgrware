<?php

class CronController extends IDemoBaseController
{
    public function actionWeatherTemperatureNotifier() {
        syslog(LOG_INFO, "Action WeatherNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::WEATHER_TEMPERATURE);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Weather Temperature': " . $count);

                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_INFO, "Start loop subscribers.");

                    $streamContext = $this->getStreamContext();

                    foreach ($subscribers as $key => $subscriber) {
                        $result = $this->getWeatherInRadius($subscriber['center'], $subscriber['radius'], $securityToken);

                        if ($result->Weather && $result->Weather->Conditions) {
                            foreach ($result->Weather->Conditions->Station as $k => $station) {
                                $actualTemperature = $station->Current->Temperature->attributes()['actual'];
                                $condition = $subscriber['condition'] == IDemoBaseController::OVER
                                    ? $actualTemperature >= $subscriber['threshold']
                                    : $actualTemperature < $subscriber['threshold'];

                                syslog(LOG_INFO, "Actual temperature: " . $actualTemperature . ", expected temperature: "
                                    . $subscriber['threshold'] . ", condition: " . $subscriber['condition']);

                                if ($condition) {
                                    $response = file_get_contents($subscriber["url"], false, $streamContext);
                                    $status = strpos($response, '"ret": "success"');

                                    if ($response && $status && $status >= 0) {
                                        syslog(LOG_INFO, "[WeatherTemperature] Request for asset " . $subscriber['id']
                                            . " has been sent to url " . $subscriber["url"]);

                                        $this->updateEventStatistic($subscriber['id'], IDemoBaseController::WEATHER_TEMPERATURE);

                                        break;
                                    } else {
                                        syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                    }
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Empty result.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Weather temperature' were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherNotifier finished.");
    }

    public function actionTrafficIncidentsNotifier() {
        syslog(LOG_INFO, "Action IncidentsNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::TRAFFIC_INCIDENTS);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Traffic Incident': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_INFO, "Start loop 'traffic incident' subscribers.");

                    foreach ($subscribers as $key => $subscriber) {
                        $result = $this->getIncidentInfo($subscriber['center'], $subscriber['radius'], $securityToken);

                        if ($result->Incidents && count($result->Incidents->Incident)) {
                            syslog(LOG_INFO, "Found " . count($result->Incidents->Incident) . " incidents.");

                            if ($subscriber['severity'] == IDemoBaseController::Accident) {
                                foreach ($result->Incidents->Incident as $i => $v) {
                                    $isAccident = strpos($v->ParameterizedDescription->EventText,
                                        IDemoBaseController::Accident);

                                    if ($isAccident !== false && $isAccident >= 0) {
                                        syslog(LOG_INFO, "Start to run incident: '" . IDemoBaseController::Accident . "'");

                                        $this->runIncidentCampaign($subscriber['url'], $subscriber['id'], $streamContext);

                                        break;
                                    }
                                }
                            } else {
                                syslog(LOG_INFO, "Start to run incident: 'All'");

                                $this->runIncidentCampaign($subscriber['url'], $subscriber['id'], $streamContext);

                                break;
                            }
                        } else {
                            syslog(LOG_INFO, "Empty result.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Traffic Incidents' were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action IncidentsNotifier finished.");
    }

    public function actionTrafficSpeedNotifier() {
        syslog(LOG_INFO, "Action SpeedNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::TRAFFIC_FLOW);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Traffic Speed': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_INFO, "Start loop 'traffic speed' subscribers.");

                    foreach ($subscribers as $key => $subscriber) {
                        $result = $this->getSegmentSpeedInRadius($subscriber['center'], $subscriber['radius'], $securityToken);

                        if ($result->SegmentSpeedResultSet && $result->SegmentSpeedResultSet->SegmentSpeedResults) {
                            foreach ($result->SegmentSpeedResultSet->SegmentSpeedResults->Segment as $k => $segment) {
                                $actualSpeed = $segment->attributes()['speed'];
                                $condition = $subscriber['condition'] == "Over"
                                    ? $actualSpeed >= $subscriber['threshold']
                                    : $actualSpeed < $subscriber['threshold'];

                                if ($condition) {
                                    $response = file_get_contents($subscriber["url"], false, $streamContext);
                                    $status = strpos($response, '"ret": "success"');

                                    if ($response && $status && $status >= 0) {
                                        syslog(LOG_INFO, "[TrafficSpeed] Request '" . $key . "' for asset "
                                            . $subscriber['id'] . " has been sent to url " . $subscriber['url']);

                                        $this->updateEventStatistic($subscriber['id'], IDemoBaseController::TRAFFIC_FLOW);

                                        break;
                                    } else {
                                        syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                    }

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Empty result.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Traffic Speed' were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action SpeedNotifier finished.");
    }

    public function actionTwitterHashTagNotifier() {
        syslog(LOG_INFO, "Action TwitterHashTagNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::TWITTER_HASH_TAG);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Twitter hashtag': " . $count);

                $streamContext = $this->getStreamContext();
                $twitter = Yii::app()->twitter->getTwitterTokened(Yii::app()->params['twitter']['oauth_token'],
                    Yii::app()->params['twitter']['oauth_token_secret']);
                $favorites = $twitter->get('favorites/list');

                foreach ($subscribers as $key => $subscriber) {
                    $userName = $subscriber['userName'];
                    $hashTag = $subscriber['hashTag'];

                    $matches = array_filter($favorites, function($favorite) use ($userName, $hashTag) {
                        if ($userName && $hashTag) {
                            $hasUsername = $hasHashTag = false;

                            foreach ($favorite->entities->user_mentions as $k => $v) {
                                if (strtolower($v->screen_name) == strtolower(substr($userName, 1))) {
                                    $hasUsername = true;

                                    break;
                                }
                            }

                            foreach ($favorite->entities->hashtags as $k => $v) {
                                if (strtolower($v->text) == strtolower(substr($hashTag, 1))) {
                                    $hasHashTag = true;

                                    break;
                                }
                            }

                            return $hasUsername && $hasHashTag;
                        }

                        if ($userName) {
                            foreach ($favorite->entities->user_mentions as $k => $v) {
                                if (strtolower($v->screen_name) == strtolower(substr($userName, 1))) {
                                    return true;
                                }
                            }
                        }

                        if ($hashTag) {
                            foreach ($favorite->entities->hashtags as $k => $v) {
                                if (strtolower($v->text) == strtolower(substr($hashTag, 1))) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    });
                    $matchesCount = count($matches);

                    if ($matchesCount) {
                        foreach ($subscriber['campaigns'] as $c => $url) {
                            if ($matchesCount >= $c) {
                                $response = file_get_contents($url, false, $streamContext);
                                $status = strpos($response, '"ret": "success"');

                                if ($response && $status && $status >= 0) {
                                    syslog(LOG_INFO, "[Twitter hashtag] Request for asset " . $subscriber['id']
                                        . ", hashtag " . $subscriber['hashTag'] . ", count "
                                        . $c . " has been sent to url " . $url);

                                    $this->updateEventStatistic($subscriber['id'], IDemoBaseController::TWITTER_HASH_TAG);

                                    break;
                                } else {
                                    syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                }

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

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::WEATHER_WIND_SPEED);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Weather Wind Speed': " . $count);

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

                                syslog(LOG_INFO, "Actual speed: " . $actualSpeed . "mph, expected speed: "
                                    . $subscriber['speed'] . "mph");

                                if ($subscriber['speed'] && $actualSpeed >= $subscriber['speed']) {
                                    $response = file_get_contents($subscriber["url"], false, $streamContext);
                                    $status = strpos($response, '"ret": "success"');

                                    if ($response && $status && $status >= 0) {
                                        syslog(LOG_INFO, "[WeatherWind Speed] Request '" . $key . "' for asset "
                                            . $subscriber['id'] . " has been sent to url " . $subscriber["url"]);

                                        $this->updateEventStatistic($subscriber['id'], IDemoBaseController::WEATHER_WIND_SPEED);

                                        break;
                                    } else {
                                        syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                    }

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Result is empty.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Weather wind speed' were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherWindNotifier finished.");
    }

    public function actionWeatherSunNotifier() {
        syslog(LOG_INFO, "Action WeatherSunNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::WEATHER_SUNNY);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Weather Sun': " . $count);

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
                                $sky = $station->Current->Sky->attributes()['description'];
                                $contain = strpos($sky, IDemoBaseController::SKY_SUNNY);

                                syslog(LOG_INFO, "Sun value: " . $sky);

                                if ($contain !== false && $contain >= 0) {
                                    $response = file_get_contents($subscriber["url"], false, $streamContext);
                                    $status = strpos($response, '"ret": "success"');

                                    if ($response && $status && $status >= 0) {
                                        syslog(LOG_INFO, "[Weather Sun] Request '" . $key . "' for asset "
                                            . $subscriber['id'] . " has been sent to url " . $subscriber["url"]);

                                        $this->updateEventStatistic($subscriber['id'], IDemoBaseController::WEATHER_SUNNY);

                                        break;
                                    } else {
                                        syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                    }

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Result is empty.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Weather Sun' event were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherSunNotifier finished.");
    }

    public function actionWeatherRainNotifier() {
        syslog(LOG_INFO, "Action WeatherRainNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::WEATHER_RAIN);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Weather Rain': " . $count);

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
                                $sky = $station->Current->Sky->attributes()['description'];
                                $contain = strpos($sky, IDemoBaseController::SKY_RAIN);

                                syslog(LOG_INFO, "Sky value: " . $sky);

                                if ($contain !== false && $contain >= 0) {
                                    $response = file_get_contents($subscriber["url"], false, $streamContext);
                                    $status = strpos($response, '"ret": "success"');

                                    if ($response && $status && $status >= 0) {
                                        syslog(LOG_INFO, "[Weather Rain] Request '" . $key . "' for asset "
                                            . $subscriber['id'] . " has been sent to url " . $subscriber["url"]);

                                        $this->updateEventStatistic($subscriber['id'], IDemoBaseController::WEATHER_RAIN);

                                        break;
                                    } else {
                                        syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                    }

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Result is empty.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Weather Rain' event were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherRainNotifier finished.");
    }

    public function actionWeatherSnowNotifier() {
        syslog(LOG_INFO, "Action WeatherSnowNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::WEATHER_SNOW);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Weather Snow': " . $count);

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
                                $sky = $station->Current->Sky->attributes()['description'];
                                $contain = strpos($sky, IDemoBaseController::SKY_SNOW);

                                syslog(LOG_INFO, "Sky value: " . $sky);

                                if ($contain !== false && $contain >= 0) {
                                    $response = file_get_contents($subscriber["url"], false, $streamContext);
                                    $status = strpos($response, '"ret": "success"');

                                    if ($response && $status && $status >= 0) {
                                        syslog(LOG_INFO, "[Weather Snow] Request '" . $key . "' for asset "
                                            . $subscriber['id'] . " has been sent to url " . $subscriber["url"]);

                                        $this->updateEventStatistic($subscriber['id'], IDemoBaseController::WEATHER_SNOW);

                                        break;
                                    } else {
                                        syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                    }

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Result is empty.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Weather Snow' event were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherSnowNotifier finished.");
    }

    public function actionWeatherThunderstormsNotifier() {
        syslog(LOG_INFO, "Action WeatherThunderstormsNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::WEATHER_THUNDER_STORM);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Weather Thunderstorms': " . $count);

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
                                $sky = $station->Current->Sky->attributes()['description'];
                                $contain = strpos($sky, IDemoBaseController::SKY_THUNDERSTORMS);
                                $contain_t = strpos($sky, IDemoBaseController::SKY_T_STORM);

                                syslog(LOG_INFO, "Sky value: " . $sky);

                                if (($contain !== false && $contain >= 0) || ($contain_t !== false && $contain_t >= 0)) {
                                    $response = file_get_contents($subscriber["url"], false, $streamContext);
                                    $status = strpos($response, '"ret": "success"');

                                    if ($response && $status && $status >= 0) {
                                        syslog(LOG_INFO, "[Weather Thunderstorms] Request '" . $key . "' for asset "
                                            . $subscriber['id'] . " has been sent to url " . $subscriber["url"]);

                                        $this->updateEventStatistic($subscriber['id'], IDemoBaseController::WEATHER_THUNDER_STORM);

                                        break;
                                    } else {
                                        syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                    }

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Result is empty.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Weather Thunderstorms' event were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherThunderstormsNotifier finished.");
    }

    public function actionWeatherCloudyNotifier() {
        syslog(LOG_INFO, "Action WeatherCloudyNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];

        if (is_file($filePath)) {
            $subscribers = $this->getSubscribers($filePath, IDemoBaseController::WEATHER_CLOUDY);
            $count = count($subscribers);

            if ($count) {
                syslog(LOG_INFO, "Count of subscribers for 'Weather Cloudy': " . $count);

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
                                $sky = $station->Current->Sky->attributes()['description'];
                                $contain = strpos($sky, IDemoBaseController::SKY_CLOUDY);

                                syslog(LOG_INFO, "Sun value: " . $sky);

                                if ($contain !== false && $contain >= 0) {
                                    $response = file_get_contents($subscriber["url"], false, $streamContext);
                                    $status = strpos($response, '"ret": "success"');

                                    if ($response && $status && $status >= 0) {
                                        syslog(LOG_INFO, "[Weather Cloudy] Request '" . $key . "' for asset "
                                            . $subscriber['id'] . " has been sent to url " . $subscriber["url"]);

                                        $this->updateEventStatistic($subscriber['id'], IDemoBaseController::WEATHER_CLOUDY);

                                        break;
                                    } else {
                                        syslog(LOG_ERR, "Response message from " . $subscriber["url"] . " is not valid.");
                                    }

                                    break;
                                }
                            }
                        } else {
                            syslog(LOG_INFO, "Result is empty.");
                        }
                    }
                }
            } else {
                syslog(LOG_INFO, "Subscribers for 'Weather Cloudy' event were not found.");
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action WeatherCloudyNotifier finished.");
    }





    private function getSubscribers($filePath, $type) {
        $ctx = $this->getStreamContextForPlanText();

        return unserialize(file_get_contents($filePath, 0, $ctx))[$type];
    }

    private function runIncidentCampaign($url, $id, $streamContext) {
        syslog(LOG_INFO, "Start to send request to " . $url);

        $response = file_get_contents($url, false, $streamContext);
        $status = strpos($response, '"ret": "success"');

        if ($response && $status !== false && $status >= 0) {
            syslog(LOG_INFO, "[TrafficIncidents] Request for asset " . $id
                . " has been sent to url " . $url);

            $this->updateEventStatistic($id, IDemoBaseController::TRAFFIC_INCIDENTS);
        } else {
            syslog(LOG_ERR, "Response message from " . $url . " is not valid.");
        }
    }
}
