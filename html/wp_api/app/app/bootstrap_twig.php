<?php
// bootstrap_twig.php

// setup Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__.'/../views',
    'twig.options' => array('debug' => true,'auto_reload' => true,),//'cache' => __DIR__.'/../cache'),
));
$app['twig']->addFilter('nl2br', new Twig_Filter_Function('nl2br', array('is_safe' => array('html'))));

?>