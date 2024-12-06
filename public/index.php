<?php

    date_default_timezone_set( 'Pacific/Auckland' );

    // Composer

    require_once( dirname( __DIR__, 1 ) . '/vendor/autoload.php' );

    // Router

    $router = new \Bramus\Router\Router( );

    /* Homepage */

    $router->get( '/', function( ) {

        echo "Homepage";

    });

    $router->run( );

?>