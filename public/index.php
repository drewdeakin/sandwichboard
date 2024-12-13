<?php

    session_start();

    date_default_timezone_set( 'Pacific/Auckland' );

    ini_set( 'display_errors', 1 );
    ini_set( 'display_startup_errors', 1 );
    error_reporting( E_ALL );

    // Composer

    require_once( dirname( __DIR__, 1 ) . '/vendor/autoload.php' );

    // Config

    $config = new \Noodlehaus\Config( dirname( __DIR__, 1 ) . '/config.json' );

    // Template

    $loader = new \Twig\Loader\FilesystemLoader( dirname( __DIR__, 1 ) . '/templates' );

    $template = new \Twig\Environment( $loader, [ "debug" => false, "cache" => dirname(__DIR__, 1) . "/cache", "auto_reload" => true, ] );

    // Database

    $database = new \Medoo\Medoo([
        "type" => "mysql",
        "host" => $config->get( 'mysql.host' ),
        "database" => $config->get( 'mysql.database' ),
        "username" => $config->get( 'mysql.username' ),
        "password" => $config->get( 'mysql.password' ),
        "port" => $config->get( 'mysql.port' ) ?? NULL,
        "charset" => "utf8",
        "error" => PDO::ERRMODE_EXCEPTION
    ]);

    // Router

    $router = new \Bramus\Router\Router( );

    // Functions

    function auth( ) {

        global $config;

        $login = $config->get( 'auth.username' );
        $password = $config->get( 'auth.password' );

        $auth = false;

        if( isset( $_SERVER['PHP_AUTH_USER'] ) && ( $_SERVER['PHP_AUTH_PW'] == $password ) && ( strtolower( $_SERVER['PHP_AUTH_USER'] ) == $login ) ):

            $auth = true;

            return true;

        endif;
        
        if(!$auth):

            header( 'WWW-Authenticate: Basic realm="Backend"' );

            header( $_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized' );

            exit(0);

        endif;

    }

    // Admin

    $router->mount( '/admin', function( ) use ( $router ) {

        // Places

        $router->mount( '/places', function( ) use ( $router ) {

            // Insert

            $router->mount( '/insert', function( ) use ( $router ) {

                $router->get( '/', function( ) {

                    if( auth( ) ):

                        global $database;
                        global $template;

                        $regions = $database->select( 'region', [ "region_id(id)", "region_name(name)" ] );

                        echo $template->render( 'admin/places/insert.html', [
                            "regions" => $regions,
                        ]);
                    
                    else:

                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                    
                    endif;

                });

                $router->post( '/', function( ) {

                    if( auth( ) ):

                        global $database;

                        $slug = new \Ausi\SlugGenerator\SlugGenerator( );

                        if( empty( $_POST['suburb_id'] ) ): $_POST['suburb_id'] = NULL; endif;

                        $database->insert( 'place', [
                            "region_id" => $_POST['region_id'],
                            "city_id" => $_POST['city_id'],
                            "suburb_id" => $_POST['suburb_id'],
                            "place_name" => $_POST['place_name'],
                            "place_slug" => $slug->generate( $_POST['place_name'] ),
                            "place_created" => time( ),
                            "place_updated" => time( ),
                        ]);

                        $place_id = $database->id();

                        $_SESSION['toast'] = [
                            "type" => "success",
                            "message" => "'" . $_POST['place_name'] . "' has been created."
                        ];

                        header( 'Location: /admin/places/' . $place_id );

                    else:

                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                    
                    endif;

                });

            });

            // Place

            $router->mount( '/(\d+)', function( ) use ( $router ) {

                // Menu

                $router->mount( '/menu', function( ) use ( $router ) {
                    
                    $router->mount( '/insert', function( ) use ( $router ) {

                        $router->get( '/', function( $place_id ) {

                            if( auth( ) ):

                                global $database;
                                global $template;
            
                                if( $database->has( 'place', [ "place_id" => $place_id ] ) ):
        
                                    echo $template->render( 'admin/places/menu/insert.html', [
                                        "place" => [
                                            "id" => $place_id,
                                        ],
                                    ]);
        
                                else:
        
                                    header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
            
                                endif;

                            else:

                                header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                            
                            endif;

                        });

                        $router->post( '/', function( $place_id ) {

                            if( auth( ) ):

                                global $database;
            
                                if( $database->has( 'place', [ "place_id" => $place_id ] ) ):

                                    $slug = new \Ausi\SlugGenerator\SlugGenerator( );

                                    $database->insert( 'menu', [
                                        "place_id" => $place_id,
                                        "menu_name" => $_POST['menu_name'],
                                        "menu_slug" => $slug->generate( $_POST['menu_name'] ),
                                        "menu_created" => time( ),
                                        "menu_updated" => time( ),
                                    ]);

                                    header( 'Content-Type: application/json' );

                                    $data['success'] = true;
        
                                    echo json_encode( $data );
        
                                else:
        
                                    header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
            
                                endif;

                            else:

                                header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                            
                            endif;

                        });

                    });

                    $router->mount( '/(\d+)', function( ) use ( $router ) {

                        // Item

                        $router->mount( '/item', function( ) use ( $router ) {

                            $router->mount( '/insert', function( ) use ( $router ) {

                                $router->get( '/', function( $place_id, $menu_id ) {

                                    if( auth( ) ):

                                        global $database;
                                        global $template;
                    
                                        if( $database->has( 'menu', [ "place_id" => $place_id, "menu_id" => $menu_id ] ) ):

                                            $sections = $database->select( 'menu_section', [ "section_id(id)", "section_name(name)" ], [ "menu_id" => $menu_id, ] );
                
                                            echo $template->render( 'admin/places/menu/item/insert.html', [
                                                "menu" => [
                                                    "id" => $menu_id,
                                                ],
                                                "place" => [
                                                    "id" => $place_id,
                                                ],
                                                "sections" => $sections,
                                            ]);
                
                                        else:
                
                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                    
                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                                $router->post( '/', function( $place_id, $menu_id ) {

                                    if( auth( ) ):

                                        global $database;
                    
                                        if( $database->has( 'menu', [ "place_id" => $place_id, "menu_id" => $menu_id ] ) ):

                                            $database->insert( 'menu_item', [
                                                "section_id" => $_POST['section_id'],
                                                "item_name" => $_POST['item_name'],
                                                "item_price" => $_POST['item_price'],
                                                "item_created" => time( ),
                                                "item_updated" => time( ),
                                            ]);

                                            header( 'Content-Type: application/json' );

                                            $data['success'] = true;
                
                                            echo json_encode( $data );
                
                                        else:
                
                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                    
                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                            });

                            $router->mount( '/(\d+)', function( ) use ( $router ) {

                                $router->get( '/delete', function( $place_id, $menu_id, $item_id ) {

                                    if( auth( ) ):

                                        global $database;

                                        if( $database->has( 'menu_item', [ "item_id" => $item_id ] ) ):

                                            $database->delete( 'menu_item', [ "item_id" => $item_id ] );

                                            header( 'Location: /admin/places/' . $place_id . '/menu/' . $menu_id );

                                        else:

                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                                $router->get( '/', function( $place_id, $menu_id, $item_id ) {

                                    if( auth( ) ):

                                        global $database;
                                        global $template;

                                        if( $database->has( 'menu_item', [ "item_id" => $item_id ] ) ):

                                            $item = $database->get( 'menu_item', [ "section_id", "item_name", "item_price", "item_description" ], [ "item_id" => $item_id ] );

                                            $sections = $database->select( 'menu_section', [ "section_id(id)", "section_name(name)" ], [ "menu_id" => $menu_id, ] );
                
                                            echo $template->render( 'admin/places/menu/item/update.html', [
                                                "form" => [
                                                    "value" => $item,
                                                ],
                                                "item" => [
                                                    "id" => $item_id,
                                                ],
                                                "menu" => [
                                                    "id" => $menu_id,
                                                ],
                                                "place" => [
                                                    "id" => $place_id,
                                                ],
                                                "sections" => $sections,
                                            ]);

                                        else:

                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                                $router->post( '/', function( $place_id, $menu_id, $item_id ) {

                                    if( auth( ) ):

                                        global $database;

                                        if( $database->has( 'menu_item', [ "item_id" => $item_id ] ) ):

                                            $database->update( 'menu_item', [
                                                "section_id" => $_POST['section_id'],
                                                "item_name" => $_POST['item_name'],
                                                "item_price" => $_POST['item_price'],
                                                "item_description" => $_POST['item_description'],
                                                "item_updated" => time( ),
                                            ], [
                                                "item_id" => $item_id
                                            ]);

                                            header( 'Content-Type: application/json' );

                                            $data['success'] = true;
                
                                            echo json_encode( $data );

                                        else:

                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                            });

                        });

                        // Section

                        $router->mount( '/section', function( ) use ( $router ) {

                            $router->mount( '/insert', function( ) use ( $router ) {

                                $router->get( '/', function( $place_id, $menu_id ) {

                                    if( auth( ) ):

                                        global $database;
                                        global $template;
                    
                                        if( $database->has( 'menu', [ "place_id" => $place_id, "menu_id" => $menu_id ] ) ):
                
                                            echo $template->render( 'admin/places/menu/section/insert.html', [
                                                "menu" => [
                                                    "id" => $menu_id,
                                                ],
                                                "place" => [
                                                    "id" => $place_id,
                                                ],
                                            ]);
                
                                        else:
                
                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                    
                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                                $router->post( '/', function( $place_id, $menu_id ) {

                                    if( auth( ) ):

                                        global $database;
                    
                                        if( $database->has( 'menu', [ "place_id" => $place_id, "menu_id" => $menu_id ] ) ):

                                            $slug = new \Ausi\SlugGenerator\SlugGenerator( );

                                            $database->insert( 'menu_section', [
                                                "menu_id" => $menu_id,
                                                "section_name" => $_POST['section_name'],
                                                "section_slug" => $slug->generate( $_POST['section_name'] ),
                                                "section_created" => time( ),
                                                "section_updated" => time( ),
                                            ]);

                                            header( 'Content-Type: application/json' );

                                            $data['success'] = true;
                
                                            echo json_encode( $data );
                
                                        else:
                
                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                    
                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                            });

                            $router->mount( '/(\d+)', function( ) use ( $router ) {

                                $router->get( '/delete', function( $place_id, $menu_id, $section_id ) {

                                    if( auth( ) ):

                                        global $database;

                                        if( $database->has( 'menu_section', [ "section_id" => $section_id ] ) ):

                                            $database->delete( 'menu_item', [ "section_id" => $section_id ] );
                                            $database->delete( 'menu_section', [ "section_id" => $section_id ] );

                                            header( 'Location: /admin/places/' . $place_id . '/menu/' . $menu_id );

                                        else:

                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                                $router->get( '/', function( $place_id, $menu_id, $section_id ) {

                                    if( auth( ) ):

                                        global $database;
                                        global $template;

                                        if( $database->has( 'menu_section', [ "section_id" => $section_id ] ) ):

                                            $section = $database->get( 'menu_section', [ "section_name" ], [ "section_id" => $section_id ] );
                
                                            echo $template->render( 'admin/places/menu/section/update.html', [
                                                "form" => [
                                                    "value" => $section,
                                                ],
                                                "section" => [
                                                    "id" => $section_id,
                                                    "name" => $section['section_name'],
                                                ],
                                                "menu" => [
                                                    "id" => $menu_id,
                                                ],
                                                "place" => [
                                                    "id" => $place_id,
                                                ],
                                            ]);

                                        else:

                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;

                                });

                                $router->post( '/', function( $place_id, $menu_id, $section_id ) {

                                    if( auth( ) ):

                                        global $database;

                                        if( $database->has( 'menu_section', [ "section_id" => $section_id ] ) ):

                                            $database->update( 'menu_section', [
                                                "section_name" => $_POST['section_name'],
                                                "section_updated" => time( ),
                                            ], [
                                                "section_id" => $section_id
                                            ]);

                                            header( 'Content-Type: application/json' );

                                            $data['success'] = true;
                
                                            echo json_encode( $data );

                                        else:

                                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

                                        endif;

                                    else:

                                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                                    
                                    endif;  

                                });

                            });

                        });

                        $router->get( '/', function( $place_id, $menu_id ) {

                            if( auth( ) ):

                                global $database;
                                global $template;
            
                                if( $database->has( 'menu', [ "place_id" => $place_id, "menu_id" => $menu_id ] ) ):

                                    $place = $database->get( 'place', [ "place_name" ], [ "place_id" => $place_id ] );

                                    $menu = $database->get( 'menu', [ "menu_name" ], [ "menu_id" => $menu_id ] );

                                    $sections = $database->select( 'menu_section', [ "section_id(id)", "section_name(name)" ], [ "menu_id" => $menu_id, ] );

                                    foreach( $sections as $key => $value ):

                                        $sections[$key]["items"] = $database->select( 'menu_item', [ "item_id(id)", "item_name(name)", "item_price(price)", "item_description(description)" ], [ "section_id" => $value['id'] ] );

                                    endforeach;
    
                                    echo $template->render( 'admin/places/menu/update.html', [
                                        "menu" => [
                                            "id" => $menu_id,
                                            "name" => $menu['menu_name'],
                                        ],
                                        "place" => [
                                            "id" => $place_id,
                                            "name" => $place['place_name'],
                                        ],
                                        "sections" => $sections,
                                    ]);

                                else:

                                    header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
            
                                endif;

                            else:

                                header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                            
                            endif;

                        });

                    });

                    $router->get( '/', function( $place_id ) {

                        if( auth( ) ):

                            global $database;
                            global $template;
        
                            if( $database->has( 'place', [ "place_id" => $place_id ] ) ):

                                $place = $database->get( 'place', [ "place_name" ], [ "place_id" => $place_id ] );

                                $menus = $database->select( 'menu', [ "menu_id(id)", "menu_name(name)" ], [ "place_id" => $place_id ] );

                                echo $template->render( 'admin/places/menu/homepage.html', [
                                    "menus" => $menus,
                                    "place" => [
                                        "id" => $place_id,
                                        "name" => $place['place_name'],
                                    ],
                                ]);

                            else:

                                header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
        
                            endif;

                        else:

                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                        
                        endif;

                    });

                });

                $router->get( '/', function( $place_id ) {

                    if( auth( ) ):

                        global $database;
                        global $template;

                        if( $database->has( 'place', [ "place_id" => $place_id ] ) ):

                            if( !isset( $_SESSION['toast'] ) ): $_SESSION['toast'] = NULL; endif;

                            $place = $database->get( 'place', [ "region_id", "city_id", "suburb_id", "place_name" ], [ "place_id" => $place_id ] );

                            $regions = $database->select( 'region', [ "region_id(id)", "region_name(name)" ] );
                            $cities = $database->select( 'city', [ "city_id(id)", "city_name(name)" ], [ "region_id" => $place['region_id'] ] );
                            $suburbs = $database->select( 'suburb', [ "suburb_id(id)", "suburb_name(name)" ], [ "city_id" => $place['city_id'] ] );

                            echo $template->render( 'admin/places/update.html', [
                                "cities" => $cities,
                                "form" => [
                                    "value" => $place,
                                ],
                                "place" => [
                                    "id" => $place_id,
                                    "name" => $place['place_name']
                                ],
                                "regions" => $regions,
                                "suburbs" => $suburbs,
                                "toast" => $_SESSION['toast'],
                            ]);

                            unset( $_SESSION['toast'] );

                        else:

                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

                        endif;

                    else:

                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                    
                    endif;

                });

                $router->post( '/', function( $place_id ) {

                    if( auth( ) ):

                        global $database;
                        global $template;

                        if( $database->has( 'place', [ "place_id" => $place_id ] ) ):

                            $slug = new \Ausi\SlugGenerator\SlugGenerator( );

                            $database->update( 'place', [
                                "region_id" => $_POST['region_id'],
                                "place_name" => $_POST['place_name'],
                                "place_slug" => $slug->generate( $_POST['place_name'] ),
                                "place_updated" => time( ),
                            ], [
                                "place_id" => $place_id
                            ]);

                            $regions = $database->select( 'region', [ "region_id(id)", "region_name(name)" ] );
                            $cities = $database->select( 'city', [ "city_id(id)", "city_name(name)" ], [ "region_id" => $_POST['region_id'] ] );
                            $suburbs = $database->select( 'suburb', [ "suburb_id(id)", "suburb_name(name)" ], [ "city_id" => $_POST['city_id'] ] );

                            echo $template->render( 'admin/places/update.html', [
                                "cities" => $cities,
                                "form" => [
                                    "value" => $_POST
                                ],
                                "place" => [
                                    "id" => $place_id,
                                ],
                                "regions" => $regions,
                                "suburbs" => $suburbs,
                                "toast" => [
                                    "type" => "success",
                                    "message" => "'" . $_POST['place_name'] . "' has been updated."
                                ]
                            ]);

                        else:

                            header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

                        endif;

                    else:

                        header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
                    
                    endif;

                });

            });

            /* Homepage */

            $router->get( '/', function( ) {

                if( auth( ) ):

                    global $database;
                    global $template;

                    $places = $database->select( 'place', [ "place_id(id)", "place_name(name)" ] );

                    echo $template->render( 'admin/places/homepage.html', [
                        "places" => $places,
                    ]);

                else:

                    header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
    
                endif;

            });

        });

        /* Homepage */

        $router->get( '/', function( ) {

            if( auth( ) ):

                global $template;

                echo $template->render( 'admin/homepage.html' );

            else:

                header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );

            endif;

        });

    });

    // API

    $router->mount( '/api', function( ) use ( $router ) {

        // City

        $router->mount( '/city', function( ) use ( $router ) {

            $router->get( '/', function( ) {

                global $database;

                if( isset( $_GET['region'] ) && is_numeric( $_GET['region'] ) ):

                    $where = [ 'region_id' => $_GET['region'] ];

                else:

                    $where = [];

                endif;

                $cities = $database->select( 'city', [ "city_id(id)", "city_name(name)" ], $where );

                header( 'Content-Type: application/json' );

                echo json_encode( $cities );

            });

        });

        // Suburb

        $router->mount( '/suburb', function( ) use ( $router ) {

            $router->get( '/', function( ) {

                global $database;

                if( isset( $_GET['city'] ) && is_numeric( $_GET['city'] ) ):

                    $where = [ 'city_id' => $_GET['city'] ];

                else:

                    $where = [];

                endif;

                $surburbs = $database->select( 'suburb', [ "suburb_id(id)", "suburb_name(name)" ], $where );

                header( 'Content-Type: application/json' );

                echo json_encode( $surburbs );

            });

        });

    });

    /* Homepage */

    $router->get( '/', function( ) {

        global $template;

        echo $template->render( 'homepage.html' );

    });

    $router->run( );

?>