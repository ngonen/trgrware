<?php
    Yii::app()->clientScript->registerScriptFile("//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/highcharts/highcharts.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/highcharts/highcharts-3d.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/highcharts/modules/exporting.js", CClientScript::POS_END);
    Yii::app()->clientScript->registerScriptFile("/js/analytics.js", CClientScript::POS_END);
?>

<div class="a-item">
    <button id="Btn1">Refresh</button>
    <div id="a-container"></div>
</div>

<div class="a-item" style="display: none;">
    <button id="Btn2">Refresh</button>
    <div id="location-container"></div>
</div>