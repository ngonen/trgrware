<?php
    Yii::app()->clientScript->registerScriptFile("//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/xmlToJSON.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/demo.js", CClientScript::POS_END);
?>

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
