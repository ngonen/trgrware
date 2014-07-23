<?php

abstract class IDemoBaseController extends Controller {
    const WEATHER_TEMPERATURE = "weather_temperature";
    const WEATHER_WIND_SPEED = "weather_wind";
    const WEATHER_RAIN = "weather_rain";
    const WEATHER_SUNNY = "weather_sun";
    const WEATHER_CLOUDY = "weather_cloud";
    const WEATHER_SNOW = "weather_snow";
    const WEATHER_THUNDER_STORM = "weather_thunder_storm";
    const WEATHER_STORM = "weather_storm";
    const TRAFFIC_FLOW = "traffic_flow";
    const TRAFFIC_INCIDENTS = "traffic_accident";
    const TWITTER_HASH_TAG = "twitter_hash_tag";
    const OVER = "Over";
    const UNDER = "Under";
    const SKY_SUNNY = "Sunny";
    const SKY_CLOUDY = "Cloudy";
    const SKY_RAIN = "Rain";
    const SKY_SNOW = "Snow";
    const SKY_THUNDERSTORMS = "Thunderstorms";
    const SKY_T_STORM = "T-storms";
    const Accident = "Accident";

    public $layout = '/layouts/layout';

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
        return stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "cache-control: private, max-age=0, no-cache"
            ]
        ]);
    }

    protected function getStreamContextForPlanText() {
        return stream_context_create([
            "gs" => [
                "Content-Type" => "text/plain"
            ]
        ]);
    }

    protected  function readStorage($filePath, $ctx = false) {
        if ($ctx === false) {
            $ctx = $this->getStreamContextForPlanText();
        }

        return unserialize(file_get_contents($filePath, 0, $ctx));
    }

    protected function saveToStorage($filePath, $fc = "", $ctx = false) {
        if ($fc !== "") {
            $fc = serialize($fc);
        }

        if ($ctx === false) {
            $ctx = $this->getStreamContextForPlanText();
        }

        return file_put_contents($filePath, $fc, 0, $ctx);
    }

    protected function createEventStorageArray($value = []) {
        return [
            IDemoBaseController::WEATHER_TEMPERATURE => $value,
            IDemoBaseController::WEATHER_WIND_SPEED => $value,
            IDemoBaseController::WEATHER_RAIN => $value,
            IDemoBaseController::WEATHER_SNOW => $value,
            IDemoBaseController::WEATHER_STORM => $value,
            IDemoBaseController::WEATHER_SUNNY => $value,
            IDemoBaseController::WEATHER_CLOUDY => $value,
            IDemoBaseController::WEATHER_THUNDER_STORM => $value,
            IDemoBaseController::TRAFFIC_INCIDENTS => $value,
            IDemoBaseController::TRAFFIC_FLOW => $value,
            IDemoBaseController::TWITTER_HASH_TAG => $value
        ];
    }

    protected function clearStorage() {
        $this->clearEventStorage();
        $this->clearEventStatistic();
    }

    protected function clearEventStatistic() {
        $storagePath = Yii::app()->params['eventStatisticStoragePath'];

        if (is_file($storagePath)) {
            syslog(LOG_INFO, "Remove statistic file.");

            unlink($storagePath);
        }
    }

    protected function updateEventStatistic($assetId, $eventType, $signName = '', $locationName = '') {
        $filePath = Yii::app()->params['eventStatisticStoragePath'];

        if (is_file($filePath)) {
            syslog(LOG_INFO, "Event statistic storage exists. Start to unserialize.");

            $fc = $this->readStorage($filePath);

            if (!isset($fc[$assetId])) {
                $fc[$assetId]['statistic'] = $this->createEventStorageArray(null);
            }
        } else {
            syslog(LOG_INFO, "Event statistic storage does not exist. Start to create.");

            $fc = [
                $assetId => [
                    'statistic' => $this->createEventStorageArray(null)
                ]
            ];
        }

        if ($locationName) {
            $fc[$assetId]['locationName'] = $locationName;

            if ($signName) {
                $fc[$assetId]['name'] = $signName;
            }

            if (!isset($fc[$assetId]['statistic'][$eventType])) {
                $fc[$assetId]['statistic'][$eventType] = 0;
            }
        } else {
            if (isset($fc[$assetId]['statistic'][$eventType])) {
                $fc[$assetId]['statistic'][$eventType] += 1;
            } else {
                $fc[$assetId]['statistic'][$eventType] = 1;
            }
        }

        if ($this->saveToStorage($filePath, $fc)) {
            syslog(LOG_INFO, "Statistic for [" . $eventType . "] has been successfully updated.");

            return $fc[$assetId]['statistic'][$eventType];
        }

        return false;
    }





    private function clearEventStorage() {
        $storagePath = Yii::app()->params['eventStoragePath'];

        if (is_file($storagePath)) {
            syslog(LOG_INFO, "Remove storage file.");

            unlink($storagePath);
        }
    }
}
