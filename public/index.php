<?php

  session_start( );

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

  // Functions

  function generate_key( $length = 10 ) {

  	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  	$charactersLength = strlen( $characters);
  	$string = '';

  	for( $i = 0; $i < $length; $i++ ):

  		$string .= $characters[rand( 0, $charactersLength - 1 )];

  	endfor;

  	return $string;

  }

  /* Notifications */

  function flash_set( string $type, string $message ): void {

    $_SESSION['flash'] = [ 'type' => $type, 'message' => $message ];

  }

  /* User */

  function user( ) {

    global $database;

    $user_key = $_COOKIE["user_key"] ?? null;
    $session_key = $_COOKIE["session_key"] ?? null;

    if( $database->has( 'session', [
      "[>]user" => "user_id"
    ], [
      "user_key" => $user_key,
      "session_key" => $session_key,
      "session_created[>=]" => time( ) - 86400 * 1,
    ] ) ):

    // Keep the cookies fresh
    setcookie( 'user_key', $user_key, time( ) + 21600, '/' );
    setcookie( 'session_key', $session_key, time( ) + 21600, '/' );

    $user = $database->get( 'user', [ "user_id(id)" ], [ "user_key" => $user_key ] );

    // Update user last active
    $database->update( 'user', [ "user_updated" => time( ) ], [ "user_id" => $user["id"] ] );

    return [ "key" => md5( $user_key ) ];

    else:

      return false;

    endif;

  }

  function user_role( ) {

  	global $database;

  	$user_key = $_COOKIE["user_key"] ?? null;
  	$session_key = $_COOKIE["session_key"] ?? null;

  	if(  $database->has( 'session', [
      "[>]user" => "user_id"
    ], [
      "user_key" => $user_key,
      "session_key" => $session_key
    ] ) ):

		$session = $database->get( 'session', [ "user_id" ], [ "session_key" => $session_key ] );

		$user = $database->get( 'user', [ "role_id" ], [ "user_id" => $session["user_id"] ] );

		return $user["role_id"];

  	else:

  		return false;

  	endif;

  }

  // Template

  $loader = new \Twig\Loader\FilesystemLoader( dirname( __DIR__, 1 ) . "/templates/" );

  $template = new \Twig\Environment( $loader, [
  	"debug" => true,
  	"cache" => dirname( __DIR__, 1 ) . "/cache",
  	"auto_reload" => true,
  ]);

  $template->addGlobal( 'site', $config->get( 'site' ) );
  $template->addGlobal( 'updated', filemtime( __DIR__ . '/index.php' ) );
  $template->addGlobal( 'user', user( ) );
  $template->addGlobal( 'user_role', user_role( ) );

  // Router

  $router = new \Bramus\Router\Router( );

  $router->set404( function( ) {

    header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

  });

  // Account

  function reset_request_ip( ) {

    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

  }

  function account_log( ?int $user_id, string $action, ?string $detail = null ) {

    global $database;

    $database->insert( 'account_log', [
      "user_id" => $user_id,
      "account_log_action" => $action,
      "account_log_detail" => $detail,
      "account_log_ip" => reset_request_ip( ),
      "account_log_created" => time( ),
    ]);

  }

  $router->mount( '/account', function( ) use ( $router ) {

    $router->get( '/', function( ) {

  		global $database, $template;

  		if( !user( ) ):

  			header( "Location: /login/" );

  		else:

  			$user_key = $_COOKIE["user_key"] ?? null;

  			$account = $database->get( 'user', [ "user_email(email)" ], [ "user_key" => $user_key ] );

  			echo $template->render( 'user/account.twig', [
  				"account" => $account,
  			]);

  		endif;

  	});

    $router->post( '/email', function( ) {

  		global $database, $template;

  		if( !user( ) ):

  			header( "Location: /login/" );

  			return;

  		endif;

  		$user_key = $_COOKIE["user_key"] ?? null;
  		$current = $database->get( 'user', [ "user_id(id)", "user_email(email)" ], [ "user_key" => $user_key ] );

  		$validator = new Valitron\Validator( $_POST );

  		$validator->rule( "required", [ "user_email", "user_password" ] );
  		$validator->rule( "email", "user_email" );

  		$validator->rule(function ( $field, $value, $params, $fields ) use ( $current ) {

  				global $database;

  				if( $value === $current["email"] ) return true;

  				return !$database->has( 'user', [ "user_email" => $value ] );

  			}, "user_email" )->message( 'An account with that email address already exists.' );

  		$validator->labels([
  			"user_email" => "Email Address",
  			"user_password" => "Current Password",
  		]);

  		if( $validator->validate( ) ):

  			$stored = $database->get( 'user', [ "user_password(password)" ], [ "user_id" => $current["id"] ] );

  			if( password_verify( $_POST["user_password"], $stored["password"] ) ):

  				$database->update( 'user', [
  					"user_email" => $_POST["user_email"],
  				], [
  					"user_id" => $current["id"],
  				]);

          account_log( $current["id"], "email_updated", $current["email"] . " -> " . $_POST["user_email"] );

  				echo $template->render( 'user/account.twig', [
  					"account" => [ "email" => $_POST["user_email"] ],
  					"toast" => [
              "section" => "email",
  						"type" => "success",
              "icon" => "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"currentColor\"><path d=\"M720-120H280v-520l280-280 50 50q7 7 11.5 19t4.5 23v14l-44 174h258q32 0 56 24t24 56v80q0 7-2 15t-4 15L794-168q-9 20-30 34t-44 14Zm-360-80h360l120-280v-80H480l54-220-174 174v406Zm0-406v406-406Zm-80-34v80H160v360h120v80H80v-520h200Z\"/></svg>",
              "message" => "Your email address has been updated.",
  					],
  				]);

  			else:

          account_log( $current["id"], "email_update_failed", "wrong current password" );

  				echo $template->render( 'user/account.twig', [
  					"account" => $current,
  					"toast" => [
              "section" => "email",
  						"type" => "error",
              "icon" => "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"currentColor\"><path d=\"M508.5-291.5Q520-303 520-320t-11.5-28.5Q497-360 480-360t-28.5 11.5Q440-337 440-320t11.5 28.5Q463-280 480-280t28.5-11.5ZM440-440h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z\"/></svg>",
  						"message" => "That password doesn’t look right. Give it another go?",
  					],
  				]);

  			endif;

  		else:

  			echo $template->render( 'user/account.twig', [
  				"account" => $current,
  				"form" => [
  					"error" => $validator->errors( ),
  				],
  			]);

  		endif;

  	});

    $router->post( '/password', function( ) {

  		global $database, $template;

  		if( !user( ) ):

  			header( "Location: /login/" );

  			return;

  		endif;

  		$user_key = $_COOKIE["user_key"] ?? null;
  		$current = $database->get( 'user', [ "user_id(id)", "user_email(email)", "user_password(password)" ], [ "user_key" => $user_key ] );

  		$validator = new Valitron\Validator( $_POST );

  		$validator->rule( "required", [ "user_password_current", "user_password_new", "user_password_confirm" ] );
  		$validator->rule( "lengthMin", "user_password_new", 6 );
  		$validator->rule( "equals", "user_password_confirm", "user_password_new" );

  		$validator->labels([
  			"user_password_current" => "Current Password",
  			"user_password_new" => "New Password",
  			"user_password_confirm" => "Confirm New Password",
  		]);

  		if( $validator->validate( ) && password_verify( $_POST["user_password_current"], $current["password"] ) ):

  			$database->update( 'user', [
  				"user_password" => password_hash( $_POST["user_password_new"], PASSWORD_DEFAULT ),
  			], [
  				"user_id" => $current["id"],
  			]);

        account_log( $current["id"], "password_updated" );

  			echo $template->render( 'user/account.twig', [
  				"account" => $current,
  				"toast" => [
            "section" => "password",
  					"type" => "success",
            "icon" => "
            <svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"currentColor\"><path d=\"M720-120H280v-520l280-280 50 50q7 7 11.5 19t4.5 23v14l-44 174h258q32 0 56 24t24 56v80q0 7-2 15t-4 15L794-168q-9 20-30 34t-44 14Zm-360-80h360l120-280v-80H480l54-220-174 174v406Zm0-406v406-406Zm-80-34v80H160v360h120v80H80v-520h200Z\"/></svg>",
            "message" => "Your password has been updated.",
  				],
  			]);

  		else:

        account_log( $current["id"], "password_update_failed" );

  			echo $template->render( 'user/account.twig', [
  				"account" => $current,
  				"toast" => [
            "section" => "password",
  					"type" => "error",
            "icon" => "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"currentColor\"><path d=\"M508.5-291.5Q520-303 520-320t-11.5-28.5Q497-360 480-360t-28.5 11.5Q440-337 440-320t11.5 28.5Q463-280 480-280t28.5-11.5ZM440-440h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z\"/></svg>",
  					"message" => "Please check your current password and try again.",
  				],
  				"form" => [
  					"error" => $validator->errors( ),
  				],
  			]);

  		endif;

  	});

    $router->post( '/delete', function( ) {

  		global $database, $template;

  		if( !user( ) ):

  			header( "Location: /login/" );

  			return;

  		endif;

  		$user_key = $_COOKIE["user_key"] ?? null;
  		$current = $database->get( 'user', [ "user_id(id)", "user_email(email)", "user_password(password)" ], [ "user_key" => $user_key ] );

  		$validator = new Valitron\Validator( $_POST );

  		$validator->rule( "required", "user_password_delete" );

  		$validator->labels([
  			"user_password_delete" => "Current Password",
  		]);

  		if( $validator->validate( ) && password_verify( $_POST["user_password_delete"], $current["password"] ) ):

        account_log( $current["id"], "account_deleted", $current["email"] );

  			$database->update( 'user', [
  				"user_email" => null,
  				"user_password" => password_hash( bin2hex( random_bytes( 32 ) ), PASSWORD_DEFAULT ),
  				"user_key" => generate_key( ),
  				"user_reset_key" => null,
  				"user_reset_expires" => null,
  				"user_deleted" => time( ),
  			], [
  				"user_id" => $current["id"],
  			]);

  			// Adjust "session" / "user_id" below to match your actual session table + column names
  			$database->delete( 'session', [ "user_id" => $current["id"] ] );

  			setcookie( "user_key", "", time( ) - 3600, "/" );
  			setcookie( "session_key", "", time( ) - 3600, "/" );

  			header( "Location: /" );

  		else:

        account_log( $current["id"], "account_delete_failed", "wrong current password" );

  			echo $template->render( 'user/account.twig', [
  				"account" => $current,
  				"toast" => [
            "section" => "delete",
  					"type" => "error",
            "icon" => "<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\" fill=\"currentColor\"><path d=\"M508.5-291.5Q520-303 520-320t-11.5-28.5Q497-360 480-360t-28.5 11.5Q440-337 440-320t11.5 28.5Q463-280 480-280t28.5-11.5ZM440-440h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z\"/></svg>",
  					"message" => "That password doesn’t look right. Give it another go?",
  				],
  				"form" => [
  					"error" => $validator->errors( ),
  				],
  			]);

  		endif;

  	});

  });

  // Admin
  $router->mount( '/admin', function( ) use ( $router ) {

    $router->get( '/', function( ) {

      global $template;

      echo $template->render( 'admin/homepage.twig' );

    });

  });

  // Help
  $router->mount( '/help', function( ) use ( $router ) {

    $router->get( '/([a-z0-9_-]+)', function( $help_slug ) {

      global $database, $template;

      $help = $database->get( 'help', [ 'help_name', 'help_page' ], [ 'help_slug' => $help_slug ]);

      if( !$help ):

        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

        return;

      endif;

      echo $template->render( 'help/page.twig', compact( 'help' ) );

    });

    $router->get( '/', function( ) {

      global $template;

      echo $template->render( 'help/homepage.twig' );

    });

  });

  // Login
  $router->mount( '/login', function( ) use ( $router ) {

    $router->get( '/', function( ) {

      if( user( ) ):

        header( 'Location: /account/' );

      else:

        global $template;

        echo $template->render( 'user/login.twig' );

      endif;

    });

    $router->post( '/', function ( ) {

  		global $database;
  		global $template;

  		if( user( ) ):

   			header( 'Location: /account/' );

  		else:

   			$validator = new Valitron\Validator( $_POST);

   			$validator->rule( 'required', [ 'user_email', 'user_password' ] );

   			$validator->rule( 'email', 'user_email' );

   			$validator->labels([
  				'user_email' => 'Email Address',
  				'user_password' => 'Password',
   			]);

   			if( $validator->validate( ) ):

  				if( $database->has( 'user', [ 'user_email' => $_POST['user_email'] ] ) ):

   					$user = $database->get( 'user', [ 'user_id(id)', 'user_password(password)' ], [ 'user_email' => $_POST['user_email'] ] );

   					if( password_verify( $_POST["user_password"], $user["password"] ) ):

  						$user = $database->get( 'user', [ 'user_id(id)', 'user_key(key)' ], [ 'user_email' => $_POST['user_email'] ] );

  						$session_key = generate_key( );

  						setcookie( 'user_key', $user['key'], time( ) + 21600, '/' );
  						setcookie( 'session_key', $session_key, time( ) + 21600, '/' );

  						$database->insert( 'session', [
   							'user_id' => $user['id'],
   							'session_key' => $session_key,
   							'session_created' => time( ),
  						]);

  						header( 'Location: /' );

   					else:

  						unset( $_POST['user_password'] );

  						echo $template->render( 'user/login.twig', [
   							'form' => [
  								'value' => $_POST,
   							],
   							'toast' => [
  								'type' => 'warning',
                  'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M508.5-291.5Q520-303 520-320t-11.5-28.5Q497-360 480-360t-28.5 11.5Q440-337 440-320t11.5 28.5Q463-280 480-280t28.5-11.5ZM440-440h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>',
  								'message' => 'Oops! That password doesn\'t look right. Give it another go?',
                  'actions' => [
                    [
                      'href' => '/password/forgot/',
                      'label' => 'Forgotten your password?'
                    ]
                  ]
   							],
  						]);

   					endif;

  				else:

   					unset( $_POST["user_password"] );

   					echo $template->render( 'user/login.twig', [
  						'form' => [
   					  'value' => $_POST,
  						],
  						'toast' => [
   							'type' => 'error',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M508.5-291.5Q520-303 520-320t-11.5-28.5Q497-360 480-360t-28.5 11.5Q440-337 440-320t11.5 28.5Q463-280 480-280t28.5-11.5ZM440-440h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>',
   							'message' => 'Hmm, we couldn\'t find an account with that email. Want to double-check for typos?',
  						],
   					]);

  				endif;

   			else:

  				unset( $_POST["user_password"] );

  				echo $template->render( 'user/login.twig', [
   					'form' => [
  						'error' => $validator->errors( ),
  						'value' => $_POST,
   					],
  				]);

   			endif;

  		endif;

   	});

  });

  // Logout
  $router->get( '/logout', function ( ) {

  	global $database;

  	if( user( ) ):

  		$user_key = $_COOKIE["user_key"] ?? null;
  		$session_key = $_COOKIE["session_key"] ?? null;

  		$user = $database->get( 'user', [ 'user_id(id)' ], [ 'user_key' => $user_key ] );

  		if( $database->has( 'session', [ 'user_id' => $user['id'], 'session_key' => $session_key ] ) ):

  			$database->delete( 'session', [ 'user_id' => $user['id'], 'session_key' => $session_key ] );

  		endif;

  		setcookie( 'user_key', '', time( ) - 600, '/' );
  		setcookie( 'session_key', '', time( ) - 600, '/' );

  	endif;

  	header( 'Location: /' );

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

      if( user( ) ):

        header( 'Location: /account/' );

      else:

        global $template;

        echo $template->render( 'user/register.twig' );

      endif;

    });

    $router->post( '/', function ( ) {

      if( user( ) ):

        header( 'Location: /account/' );

      else:

    		global $config, $database, $template;

    		if( empty( $_POST['user_name'] ) ):

    			$_POST['user_name'] = '';

    		endif;

    		if( empty( $_POST['user_email'] ) ):

    			$_POST['user_email'] = '';

    		endif;

    		$validator = new Valitron\Validator( $_POST );

    		$validator->rule( 'required', [ 'user_email', 'user_password' ] );

    		$validator->rule( 'email', 'user_email' );

    		// Does email address already exist?

    		$validator->rule(function ( $field, $value, $params, $fields ) {

    				global $database;

    				if( $database->has( 'user', [ 'user_email' => $_POST['user_email'] ] ) ):

    					return false;

    				else:

    					return true;

    				endif;

    			}, 'user_email' )->message( 'An account with that email address exists. Would you like to <a href=\'/login/\'>log in</a>?' );

    		$validator->rule( 'lengthMin', 'user_password', 6);

    		$validator->labels([
    			'user_email' => 'Email Address',
    			'user_password' => 'Password',
    		]);

    		if( $validator->validate( ) && $config->get( 'site.register' ) == 'true' ):

    			$user_key = generate_key( );

    			$database->insert( 'user', [
    				'user_key' => $user_key,
    				'user_email' => $_POST['user_email'],
    				'user_password' => password_hash( $_POST['user_password'], PASSWORD_DEFAULT, ),
    				'user_created' => time( ),
    				'user_updated' => time( ),
    			]);

    			$user_id = $database->id( );

    			$session_key = generate_key( );

    			setcookie( 'user_key', $user_key, time( ) + 21600, '/' );
    			setcookie( 'session_key', $session_key, time( ) + 21600, '/' );

    			$database->insert( 'session', [
    				'user_id' => $user_id,
    				'session_key' => $session_key,
    				'session_created' => time( ),
    			]);

    			header( 'Location: /' );

    		else:

    			echo $template->render( 'user/register.twig', [
    				'form' => [
    					'error' => $validator->errors( ),
    					'value' => $_POST,
    				],
    			]);

    		endif;

      endif;

  	});

  });

  // About

  $router->get( '/about', function( ) {

    global $template;

    echo $template->render( 'about.twig' );

  });

  $router->get( '/', function( ) {

    global $template;

    echo $template->render( 'homepage.twig' );

  });

  $router->run( );

?>
