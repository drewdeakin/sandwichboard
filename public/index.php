<?php

  require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

  $router = new \Bramus\Router\Router( );

  $router->set404( function( ) {

    header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

  });

  // Admin
  $router->mount( '/admin', function( ) use ( $router ) {

    $router->get( '/', function( ) {

    });

  });

  // Login
  $router->mount( '/login', function( ) use ( $router ) {

    $router->get( '/', function( ) {

    });

    $router->post( '/', function( ) {

    });

  });

  // Manage
  $router->mount( '/manage', function( ) use ( $router ) {

    $router->get( '/', function( ) {

    });

  });

  // Password
  $router->mount( '/password', function( ) use ( $router ) {

    // Forgot
    $router->mount( '/forgot', function( ) use ( $router ) {

      $router->get( '/', function( ) {

      });

      $router->post( '/', function( ) {

      });

    });

    // Reset
    $router->mount( '/reset', function( ) use ( $router ) {

      $router->get( '/', function( ) {

      });

    });

  });

  // Register
  $router->mount( '/register', function( ) use ( $router ) {

    $router->get( '/', function( ) {

    });

    $router->post( '/', function( ) {

    });

  });

  // Submit
  $router->mount( '/submit', function( ) use ( $router ) {

    $router->get( '/', function( ) {

    });

  });

  $router->get( '/', function( ) {

  });

  $router->run( );

?>
