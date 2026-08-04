<?php

  date_default_timezone_set( 'Pacific/Auckland' );

  error_reporting( E_ALL );
  ini_set( 'display_errors', '1' );

  // Composer

  require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

  // Config

  $config = new \Noodlehaus\Config( __DIR__ . "/config.json" );

  // Database

  $database = new \Medoo\Medoo([
    "type" => "mysql",
    "host" => $config->get( "mysql.host" ),
    "database" => $config->get( "mysql.database" ),
    "username" => $config->get( "mysql.username" ),
    "password" => $config->get( "mysql.password" ),
    "port" => $config->get( "mysql.port" ) ?? null,
    "charset" => "utf8",
    "error" => PDO::ERRMODE_EXCEPTION,
  ]);

  // Template

  $loader = new \Twig\Loader\FilesystemLoader( __DIR__ . "/templates/" );

  $template = new \Twig\Environment( $loader, [
  	"debug" => true,
  	"cache" => __DIR__ . "/cache",
  	"auto_reload" => true,
  ]);

  // Router

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
