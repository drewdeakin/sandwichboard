<?php

  date_default_timezone_set( 'Pacific/Auckland' );

  // Composer

  require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

  // Config

  $config = new \Noodlehaus\Config( dirname( __DIR__, 1 ) . "/config.json" );

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

  $loader = new \Twig\Loader\FilesystemLoader( dirname( __DIR__, 1 ) . "/templates/" );

  $template = new \Twig\Environment( $loader, [
  	"debug" => true,
  	"cache" => __DIR__ . "/cache",
  	"auto_reload" => true,
  ]);

  $template->addGlobal( 'site', $config->get( 'site' ) );
  $template->addGlobal( 'updated', filemtime( __DIR__ . '/public/index.php' ) );

  // Router

  $router = new \Bramus\Router\Router( );

  $router->set404( function( ) {

    header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

  });

  // Admin
  $router->mount( '/admin', function( ) use ( $router ) {

    $router->get( '/', function( ) {

      global $template;

      echo $template->render( 'admin/homepage.twig' );

    });

  });

  // Login
  $router->mount( '/login', function( ) use ( $router ) {

    $router->get( '/', function( ) {

      global $template;

      echo $template->render( 'user/login.twig' );

    });

    $router->post( '/', function( ) {

    });

  });

  // Manage
  $router->mount( '/manage', function( ) use ( $router ) {

    $router->get( '/', function( ) {

      global $template;

      echo $template->render( 'manage/homepage.twig' );

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

      global $template;

      echo $template->render( 'user/register.twig' );

    });

    $router->post( '/', function( ) {

    });

  });

  // Submit
  $router->mount( '/submit', function( ) use ( $router ) {

    $router->get( '/', function( ) {

      global $template;

      echo $template->render( 'submit/homepage.twig' );

    });

  });

  $router->get( '/', function( ) {

    global $template;

    echo $template->render( 'homepage.twig' );

  });

  $router->run( );

?>
