<?php

class CronController extends DemoBaseController
{
    public function actionWeatherTemperatureNotifier() {
        syslog(LOG_INFO, "Action WeatherNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);

        if (is_file($filePath)) {
            $weatherSubscribers = unserialize(file_get_contents($filePath, 0, $ctx))[DemoBaseController::WEATHER_TEMPERATURE];
            $count = count($weatherSubscribers);

            if ($count) {
                syslog(LOG_WARNING, "Count of subscribers for 'weather temperature': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_WARNING, "Start loop subscribers.");

                    foreach ($weatherSubscribers as $key => $value) {
                        $result = $this->getWeatherInRadius($value['center'], $value['radius'], $securityToken);

                        if ($result->Weather && $result->Weather->Conditions) {
                            foreach ($result->Weather->Conditions->Station as $k => $station) {
                                $actualTemperature = $station->Current->Temperature->attributes()['actual'];
                                $condition = $value['threshold'] == "Over"
                                    ? $actualTemperature >= $value['temperature']
                                    : $actualTemperature < $value['temperature'];

                                syslog(LOG_INFO, "Actual temperature: " . $actualTemperature . ", expected temperature: "
                                    . $value['temperature'] . ", threshold: " . $value['threshold']);

                                if ($condition) {
                                    $url = $value["url"];

                                    file_get_contents($url, false, $streamContext);

                                    syslog(LOG_INFO, "[WeatherTemperature] Request '" . $key . "' for asset "
                                        . $value['id'] . " has been sent to url " . $url);

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
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);

        if (is_file($filePath)) {
            $incidentSubscribers = unserialize(file_get_contents($filePath, 0, $ctx))[DemoBaseController::TRAFFIC_INCIDENTS];
            $count = count($incidentSubscribers);

            if ($count) {
                syslog(LOG_WARNING, "Count of subscribers for 'traffic incident': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_WARNING, "Start loop 'traffic incident' subscribers.");

                    foreach ($incidentSubscribers as $key => $value) {
                        $result = $this->getIncidentInfo($value['center'], $value['radius'], $securityToken);

                        if ($result->Incidents && count($result->Incidents->Incident)) {
                            $url = $value["url"];

                            file_get_contents($url, false, $streamContext);

                            syslog(LOG_INFO, "[TrafficIncidents] Request '" . $key . "' for asset "
                                . $value['id'] . " has been sent to url " . $url);
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
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);

        if (is_file($filePath)) {
            $speedSubscribers =  unserialize(file_get_contents($filePath, 0, $ctx))[DemoBaseController::TRAFFIC_FLOW];
            $count = count($speedSubscribers);

            if ($count) {
                syslog(LOG_WARNING, "Count of subscribers for 'traffic speed': " . $count);

                $streamContext = $this->getStreamContext();
                $securityToken = $this->getSecurityToken();

                if (!$securityToken) {
                    syslog(LOG_WARNING, "Cannot get security token.");
                } else {
                    syslog(LOG_WARNING, "Start loop 'traffic speed' subscribers.");

                    foreach ($speedSubscribers as $key => $value) {
                        $result = $this->getSegmentSpeedInRadius($value['center'], $value['radius'], $securityToken);

                        if ($result->SegmentSpeedResultSet && $result->SegmentSpeedResultSet->SegmentSpeedResults) {
                            foreach ($result->SegmentSpeedResultSet->SegmentSpeedResults->Segment as $k => $segment) {
                                if ($segment->attributes()['speed'] <= $value['speedUnder']) {
                                    $url = $value['url'];

                                    file_get_contents($url, false, $streamContext);

                                    syslog(LOG_INFO, "[TrafficSpeed] Request '" . $key . "' for asset "
                                        . $value['id'] . " has been sent to url " . $url);

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
}
