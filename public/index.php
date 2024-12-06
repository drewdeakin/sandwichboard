<?php

    date_default_timezone_set( 'Pacific/Auckland' );

    // Composer

    require_once( dirname( __DIR__, 1 ) . '/vendor/autoload.php' );

    // Template

    $loader = new \Twig\Loader\FilesystemLoader( dirname( __DIR__, 1 ) . '/templates' );

    $template = new \Twig\Environment( $loader, [ "debug" => false, "cache" => dirname(__DIR__, 1) . "/cache", "auto_reload" => true, ] );

    // Router

    $router = new \Bramus\Router\Router( );

    // Admin

    $router->mount( '/admin', function( ) use ($router) {

        // Places

        $router->mount( '/places', function( ) use ($router) {

            // Insert

            $router->mount( '/insert', function( ) use ($router) {

                $router->get( '/', function( ) {

                    global $template;

                    echo $template->render( 'admin/places/insert.html' );

                });

                $router->post( '/', function( ) {

                });

            });

            // Place

            $router->mount( '/(\d+)', function( ) use ($router) {

                // Menu

                $router->mount( '/menu', function( ) use ($router) {

                    $router->get( '/', function( $product_id ) {

                        echo $product_id;

                    });

                });

                $router->get( '/', function( $product_id ) {

                    global $template;

                    echo $template->render( 'admin/places/update.html' );

                });

                $router->post( '/', function( $product_id ) {

                    echo $product_id;

                });

            });

            /* Homepage */

            $router->get( '/', function( ) {

                global $template;

                echo $template->render( 'admin/places/homepage.html' );

            });

        });

        /* Homepage */

        $router->get( '/', function( ) {

            global $template;

            echo $template->render( 'admin/homepage.html' );

        });

    });

    /* Homepage */

    $router->get( '/', function( ) {

        global $template;

        echo $template->render( 'homepage.html' );

    });

    $router->run( );

?>