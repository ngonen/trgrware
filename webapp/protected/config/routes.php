<?php

$routes = array(
    'urlFormat' => 'path',
    'baseUrl' => '', // added to fix URL issues under Google App Engine
    'rules' => array(
        '<controller:\w+>/<id:\d+>' => '<controller>/view',
        '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
        '<controller:\w+>/<action:\w+>' => '<controller>/<action>'
    ),
);

return $routes;