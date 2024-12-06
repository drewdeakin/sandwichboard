<?php

    date_default_timezone_set( 'Pacific/Auckland' );

    // Composer

    require_once( dirname( __DIR__, 1 ) . '/vendor/autoload.php' );

    // Router

    $router = new \Bramus\Router\Router( );

    // Admin

    $router->mount( '/admin', function( ) use ($router) {

        // Places

        $router->mount( '/place', function( ) use ($router) {

            // Place

            $router->mount( '/(\d+)', function( ) use ($router) {

                // Menu

                $router->mount( '/menu', function( ) use ($router) {

                    $router->get( '/', function( $product_id ) {

                        echo $product_id;

                    });

                });

                $router->get( '/(\d+)', function( $product_id ) {

                    echo $product_id;

                });

                $router->post( '/(\d+)', function( $product_id ) {

                    echo $product_id;

                });

            });

            /* Homepage */

            $router->get( '/', function( ) {

                echo "Admin Homepage";

            });

        });

        /* Homepage */

        $router->get( '/', function( ) {

            echo "Admin Homepage";

        });

    });

    /* Homepage */

    $router->get( '/', function( ) {

        echo "Homepage";

    });

    $router->run( );

?>