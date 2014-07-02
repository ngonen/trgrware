<?php

class CronController extends DemoBaseController
{
    // TODO: delete
    public function actionEvents() {
        syslog(LOG_INFO, "Action actionEvents started.");

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);

        if (is_file($filePath)) {
            $fc = unserialize(file_get_contents($filePath, 0, $ctx));

            syslog(LOG_INFO, "Content unserialized.");

            foreach ($fc as $key => $value) {
                foreach ($value as $v) {
                    syslog(LOG_INFO, $key . ": id-" . $v['id'] . ", center-" . $v['center']);

                    if ($key == 'weather') {
                        syslog(LOG_INFO, "Callback request for weather URL was sent.");

                        file_get_contents($v['url'] . "?id=" . $v['id'], false, stream_context_create(array(
                            'http' => array(
                                'method' => 'GET',
                                'header' => "cache-control: private, max-age=0, no-cache"
                            )
                        )));
                    }
                }
            }
        }

        syslog(LOG_INFO, "Action actionEvents finished.");
    }

    public function actionWeatherTemperatureNotifier() {
        syslog(LOG_INFO, "Action WeatherNotifier started.");

        $filePath = Yii::app()->params['eventStoragePath'];
        $ctx = stream_context_create(["gs" => ["Content-Type" => "text/plain"]]);

        if (is_file($filePath)) {
            $weatherSubscribers = unserialize(file_get_contents($filePath, 0, $ctx))[DemoBaseController::WEATHER_TEMPERATURE];
            $streamContext = $this->getStreamContext();
            $securityToken = $this->getSecurityToken();

            if (!$securityToken) {
                syslog(LOG_WARNING, "Cannot get security token.");
            } else {
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
                        syslog(LOG_INFO, "Do not have any new info to push.");
                    }
                }
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
            $streamContext = $this->getStreamContext();
            $securityToken = $this->getSecurityToken();

            if (!$securityToken) {
                syslog(LOG_WARNING, "Cannot get security token.");
            } else {
                foreach ($incidentSubscribers as $key => $value) {
                    $result = $this->getIncidentInfo($value['center'], $value['radius'], $securityToken);

                    if ($result->Incidents && count($result->Incidents->Incident)) {
                        $url = $value["url"];

                        file_get_contents($url, false, $streamContext);

                        syslog(LOG_INFO, "[TrafficIncidents] Request '" . $key . "' for asset "
                            . $value['id'] . " has been sent to url " . $url);
                    } else {
                        syslog(LOG_INFO, "Do not have any new info to push.");
                    }
                }
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
            $streamContext = $this->getStreamContext();
            $securityToken = $this->getSecurityToken();

            if (!$securityToken) {
                syslog(LOG_WARNING, "Cannot get security token.");
            } else {
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
                        syslog(LOG_INFO, "Have not gotten any new info to push.");
                    }
                }
            }
        } else {
            syslog(LOG_INFO, "FileStorage not found. Requested path: " . $filePath);
        }

        syslog(LOG_INFO, "Action SpeedNotifier finished.");
    }
}
