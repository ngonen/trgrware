<?php
    Yii::app()->clientScript->registerScriptFile("//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("//maps.google.com/maps/api/js?v=3.2&sensor=false", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/xmlToJSON.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/demo.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/inrix.3.0.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/inrix.layer.js", CClientScript::POS_END);   
    Yii::app()->clientScript->registerScriptFile("/js/MarkerWithLabel.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/jquery.pubsub.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/script-main.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/asset.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/trigger.js", CClientScript::POS_END);
?>

<div class="left_panel">
    <div class="title">Assets</div>
    <div class="assets_list">
        <div class="list_item">
            <img src="img/screen.png" width="18" height="18" class="item" id="asset-1" data-type="screen" draggable="true"/>Screen
        </div>
    </div>
    <div class="title">Triggers</div>
    <div class="triggers_list">
        <div class="list_item">
            <img src="img/incident@2x.png" width="18" height="18" class="item" data-type="traffic_accident" draggable="true"/>Accident
        </div>
        <div class="list_item">
            <img src="img/congestion@2x.png" width="18" height="18" class="item" data-type="traffic_flow" draggable="true"/>Flow
        </div>
        <div class="list_item">
            <img src="img/temp@2x.png" width="18" height="18" class="item" data-type="weather_temperature" draggable="true"/>Temperature
        </div>
        <div class="list_item">
            <img src="img/rain.png" width="18" height="18" class="item" data-type="weather_event" draggable="true"/>Rain
        </div>
        <div class="list_item">
            <img src="img/storm.jpg" width="18" height="18" class="item" data-type="weather_event" draggable="true"/>Storm
        </div>
        <div class="list_item">
            <img src="img/snowflake.jpg" width="18" height="18" class="item" data-type="weather_event" draggable="true"/>Snow
        </div>
        <div class="list_item">
            <img src="img/cloud-wind.png" width="18" height="18" class="item" data-type="weather_event" draggable="true"/>Wind
        </div>
        <div class="list_item">
            <img src="img/twitter.jpg" width="18" height="18" class="item" data-type="social_twitter" draggable="true"/>Twitter
        </div>
    </div>
    <div class="title">Map info</div>
    <div>
        <div class="list_item">
            <input class="item_checkbox" type="checkbox" id="traffic" checked>
            <label for="traffic">Traffic</label>
        </div>
        <div class="list_item">
            <input class="item_checkbox" type="checkbox" id="accidents" checked>
            <label for="accidents">Accidents</label>
        </div>
        <div class="list_item">
            <input class="item_checkbox" type="checkbox" id="temperature" checked>
            <label for="temperature">Temperature</label>
        </div>
    </div>
</div>
<div class="loading">
    <img src="/img/loading.gif" width="13px" height="13px">
    Updating map
</div>
<div id="map_canvas"></div>
<div class="popup_bg"></div>
<div id="asset_popup" class="popup">
    <div class="popup_title">Asset properties</div>
    <form>
        <div class="popup-field">
            <span class="popup-label">Asset Name</span>
            <input data-property="name" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Description</span>
            <input data-property="description" class="textinput prop" type="text">
        </div>
        <div class="popup-field">
            <span class="popup-label">Cap Period</span>
            <input data-property="capPeriod" data-type="int" class="textinput prop" type="text">
        </div>
        <div class="popup-field">
            <span class="popup-label">Cap Unit</span>
            <select data-property="capUnit" class="drop-down prop">
                <option value="weeks">Weeks</option>
                <option value="days">Days</option>
                <option value="hours">Hours</option>
            </select>
        </div>
        <div class="popup-field">
            <span class="popup-label">Sign ID</span>
            <input data-property="sid" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Lattitude</span>
            <input data-property="lat" data-type="float" data-min="-90" data-max="90" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Longitude</span>
            <input data-property="lng" data-type="float" data-min="-90" data-max="90" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field error"></div>
        <div class="buttons-block">
            <input class="button button_action" type="button" data-popup="#asset_popup" value="Submit" onclick="updateAsset(event)">
            <input class="button" type="button" value="Cancel" onclick="onCancel()">
        </div>
    </form>
</div>
<div id="trigger_accident_popup" class="popup trigger_popup">
    <div class="popup_title">Trigger properties</div>
    <form>
        <div class="popup-field">
            <span class="popup-label">Campain ID</span>
            <input data-property="cid" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Retrigger Wait Period</span>
            <input data-property="rwp" data-type="int" class="textinput prop" type="text">
        </div>
        <div class="popup-field">
            <span class="popup-label">Radius</span>
            <input data-property="radius" data-type="float" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Severity</span>
            <select data-property="severity" class="drop-down prop">
                <option value="All">All</option>
            </select>
        </div>
        <div class="popup-field">
            <span class="popup-label">Callback URL</span>
            <input data-property="url" data-type="url" class="textinput prop" type="url" required>
        </div>
        <div class="popup-field error"></div>
        <div class="buttons-block">
            <input class="button button_action" type="button" data-popup="#trigger_accident_popup" value="Submit" onclick="updateTrigger(event)">
            <input class="button" type="button" value="Cancel" onclick="hidePopup()">
        </div>
    </form>
</div>
<div id="trigger_flow_popup" class="popup trigger_popup">
    <div class="popup_title">Trigger properties</div>
    <form>
        <div class="popup-field">
            <span class="popup-label">Campain ID</span>
            <input data-property="cid" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Retrigger Wait Period</span>
            <input data-property="rwp" data-type="int" class="textinput prop" type="text">
        </div>
        <div class="popup-field">
            <span class="popup-label">Radius</span>
            <input data-property="radius" data-type="float" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Speed Under</span>
            <input data-property="speedUnder" data-type="int" data-min="0" data-max="200" data-default="25" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Callback URL</span>
            <input data-property="url" data-type="url" class="textinput prop" type="url" required>
        </div>
        <div class="popup-field error"></div>
        <div class="buttons-block">
            <input class="button button_action" type="button" data-popup="#trigger_flow_popup" value="Submit" onclick="updateTrigger(event)">
            <input class="button" type="button" value="Cancel" onclick="hidePopup()">
        </div>
    </form>
</div>
<div id="trigger_twitter_popup" class="popup trigger_popup">
    <div class="popup_title">Trigger properties</div>
    <form>
        <div class="popup-field">
            <span class="popup-label">Campain ID</span>
            <input data-property="cid" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Retrigger Wait Period</span>
            <input data-property="rwp" class="textinput prop" type="text">
        </div>
        <div class="popup-field">
            <span class="popup-label">Hashtag</span>
            <input data-property="hashtag" data-type="hashtag" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Callback URL</span>
            <input data-property="url" data-type="url" class="textinput prop" type="url" required>
        </div>
        <div class="popup-field error"></div>
        <div class="buttons-block">
            <input class="button button_action" type="button" data-popup="#trigger_twitter_popup" value="Submit" onclick="updateTrigger(event)">
            <input class="button" type="button" value="Cancel" onclick="hidePopup()">
        </div>
    </form>
</div>
<div id="trigger_temperature_popup" class="popup trigger_popup">
    <div class="popup_title">Trigger properties</div>
    <form>
        <div class="popup-field">
            <span class="popup-label">Campain ID</span>
            <input data-property="cid" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Retrigger Wait Period</span>
            <input data-property="rwp" data-type="int" class="textinput prop" type="text">
        </div>
        <div class="popup-field">
            <span class="popup-label">Radius</span>
            <input data-property="radius" data-type="float" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Threshold</span>
            <select data-property="threshold" class="drop-down prop">
                <option value="Over">Over</option>
                <option value="Under">Under</option>
            </select>
        </div>
        <div class="popup-field">
            <span class="popup-label">Target Temp.</span>
            <input data-property="temperature" data-type="int" data-min="-50" data-max="150" data-default="0" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Callback URL</span>
            <input data-property="url" data-type="url" class="textinput prop" type="url" required>
        </div>
        <div class="popup-field error"></div>
        <div class="buttons-block">
            <input class="button button_action" type="button" data-popup="#trigger_temperature_popup" value="Submit" onclick="updateTrigger(event)">
            <input class="button" type="button" value="Cancel" onclick="hidePopup()">
        </div>
    </form>
</div>
<div id="trigger_weather_popup" class="popup trigger_popup">
    <div class="popup_title">Trigger properties</div>
    <form>
        <div class="popup-field">
            <span class="popup-label">Campain ID</span>
            <input data-property="cid" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Retrigger Wait Period</span>
            <input data-property="rwp" class="textinput prop" type="text">
        </div>
        <div class="popup-field">
            <span class="popup-label">Radius</span>
            <input data-property="radius" class="textinput prop" type="text" required>
        </div>
        <div class="popup-field">
            <span class="popup-label">Storm (Generic)</span>
            <input data-property="storm" type="checkbox" class="prop exclusive">
            <div class="popup-field subitem">
                <span class="popup-label">Severity</span>
                <select data-property="severity" class="drop-down prop">
                    <option value="All">All</option>
                </select>
            </div>
        </div>
        <div class="popup-field">
            <span class="popup-label">Rain</span>
            <input data-property="rain" type="checkbox" class="prop">
        </div>
        <div class="popup-field">
            <span class="popup-label">Snow</span>
            <input data-property="snow" type="checkbox" class="prop">
        </div>
        <div class="popup-field">
            <span class="popup-label">Sunny</span>
            <input data-property="sunny" type="checkbox" class="prop">
        </div>
        <div class="popup-field">
            <span class="popup-label">Thunder Storm</span>
            <input data-property="thunderStorm" type="checkbox" class="prop">
        </div>
        <div class="popup-field">
            <span class="popup-label">Windy</span>
            <input data-property="windy" type="checkbox" class="prop">
            <div class="popup-field subitem">
                <span class="popup-label">Wind Speed Over</span>
                <input data-property="windSpeed" data-type="int" data-min="0" data-max="200" data-default="25" class="textinput prop" type="text">
            </div>
        </div>
        <div class="popup-field">
            <span class="popup-label">Callback URL</span>
            <input data-property="url" data-type="url" class="textinput prop" type="url" required>
        </div>
        <div class="popup-field error"></div>
        <div class="buttons-block">
            <input class="button button_action" type="button" data-popup="#trigger_weather_popup" value="Submit" onclick="updateTrigger(event)">
            <input class="button" type="button" value="Cancel" onclick="hidePopup()">
        </div>
    </form>
</div>
<div id="context_menu">
    <div class="menu_item sub triggers_menu">Triggers
        <div class="submenu">
            <div class="menu_item sub traffic_menu">Traffic
                <div class="traffic_sub_menu">
                    <div class="menu_item" data-popup="#trigger_accident_popup" data-type="traffic_accident" onclick="showTriggerPopup(event)">
                        Incident
                        <div class="cross hidden" onclick="removeTrigger(event)"></div>
                    </div>
                    <div class="separator"></div>
                    <div class="menu_item" data-popup="#trigger_flow_popup" data-type="traffic_flow" onclick="showTriggerPopup(event)">
                        Flow
                        <div class="cross hidden" onclick="removeTrigger(event)"></div>
                    </div>
                </div>
            </div>
            <div class="separator"></div>
            <div class="menu_item sub weather_menu">Weather
                <div class="weather_sub_menu">
                    <div class="menu_item" data-popup="#trigger_weather_popup" data-type="weather_event" onclick="showTriggerPopup(event)">
                        Event
                        <div class="cross hidden" onclick="removeTrigger(event)"></div>
                    </div>
                    <div class="separator"></div>
                    <div class="menu_item" data-popup="#trigger_temperature_popup" data-type="weather_temperature" onclick="showTriggerPopup(event)">
                        Temperature
                        <div class="cross hidden" onclick="removeTrigger(event)"></div>
                    </div>
                </div>
            </div>
            <div class="separator"></div>
            <div class="menu_item sub social_menu">Social
                <div class="social_sub_menu">
                    <div class="menu_item" data-popup="#trigger_twitter_popup" data-type="social_twitter" onclick="showTriggerPopup(event)">
                        Twitter
                        <div class="cross hidden" onclick="removeTrigger(event)"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="separator"></div>
    <div class="menu_item" onclick="showPropertiesPopup()">Properties</div>
    <div class="separator"></div>
    <div class="menu_item" onclick="removeAsset()">Remove asset</div>
</div>
            

<div class="page-width-container">
<h1>
    Demo page
</h1>
<br/><br/>


<button id="PopUp">Show popup</button>
<br/><br/><br/><br/>

<div>
    <label for="Token">Security Token</label>
    <input type="text" id="Token" value=""/>
    <button id="GetToken">Get New Token</button>
</div>
<br/><br/>

<img id="Loader" src="/img/loading.gif" style="display: none; margin: 0 auto; width: 50px;">

<div style="height: 50px;">
    <div id="Results"></div>
    <div id="CallBackInfo"></div>
</div>

<button id="GetIncidentInfo" disabled="disabled">Get Incident Info</button>
<button id="GetWeatherInfo" disabled="disabled">Get Weather Info</button>
<button id="GetSegmentSpeedInfo" disabled="disabled">Get Segment Speed Info</button>
<button id="SimulateCallback">Simulate Galaxy Request</button>

    <label style="display: block; text-align: center; font-weight: bold;">Test block</label>
    <button id="BtnCronTest">Cron Test Weather</button>
    <button id="BtnCronTestAccident">Cron Test Accident</button>
    <button id="BtnCronTestFlow">Cron Test Flow</button>

    <div style="border: 1px solid; margin: 2px;">
        AssetId:<input type="test" id="assetId" value="" />
        EventType:<input type="test" id="eventType" value="" />
        <button id="BtnDeleteAsset">Delete Asset</button>
        <button id="BtnDeleteEvent">Delete Event</button>
    </div>
<div class="modal-dialog-bg" style="opacity: 0.75; width: 1920px; height: 656px; display: none;" aria-hidden="true"></div>
<div class="modal-dialog p6n-popup" style="left: 761px; top: 100px; opacity: 1; display: none;" aria-labelledby=":gg" tabindex="0" role="dialog">
    <div class="modal-dialog-title p6n-popup-title modal-dialog-title-draggable">
        New Bucket
        <span class="modal-dialog-title-text" id=":gg"></span>
        <span class="modal-dialog-title-close" style="display: none;"></span>
    </div>

    <form class="modal-dialog-content p6n-cloudstorage-create-bucket-content" onsubmit="return false;">
        <label for="create-bucket-bucketname">Name</label>
        <input class="jfk-textinput p6n-popup-default-focus" maxlength="222" name="bucketname" id="create-bucket-bucketname">
    </form>

    <div class="modal-dialog-buttons p6n-cloudstorage-create-bucket-buttons">
        <button name="save" class="goog-buttonset-default goog-buttonset-action" disabled="">Create</button>
        <button name="cancel">Cancel</button>
    </div>
</div>

</div>
