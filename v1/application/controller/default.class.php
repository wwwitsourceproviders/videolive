<?php

/*
  Validator
 */

class Controller {

    /**
     * Array of objects
      By default this controller is called
     */
    private $objects;
    private $token;
    /**
     * Array of settings
     */

    /**
     * Reference to the registry object
     */
    private $registry;
    private $app;
    //Allowed pages
    private $pages = array(
        'api',
        'audio',
        'video',
        'index'
    );

    /**
     * Construct our site object
     */
    public function __construct(Slim\App $app) {
        $this->app = $app;
        $container = $this->app->getContainer();
        $this->registry = $container['register'];
        /* $this->app = $app;
          $this->URI = URI::getInstance();
          $page = strtolower($this->URI->getPage());
          $this->$page();
          exit(); */
        //$user = $this->registry->getObject('api')->getUser();
        //if ($user->getProfile()[1]['token'] != 'UWtwaVpXcENRM2QwV0ZObFdXcG9XblZDZGxaTmFXNVhRbFpVYW1kblZteHFXbEJL') {
        //    exit();
        //}
        $this->URI = URI::getInstance();
        $page = strtolower($this->URI->getPage());
        if ($page == 'index') {
            $this->index();
        } else if (in_array($page, $this->pages)) {
            $this->$page();
        } else if ($page == '404') {
            $this->page404();
        } else {
            header('Location: ' . $GLOBALS['config_base_left'] . '/404.htm');
        }
    }

    public function index() {
        echo '<a href=\'https://podiumacademicmanagement.com\'>PODIUM ACADEMIC MANAGEMENT VIDEO</a>';
        exit();
    }

    /*
      Audio page
     *      */

    public function audio() {
        require_once('audio.class.php');
        $audio = new Audio($this->registry);
        exit();
    }

    /*
      Video page
     *      */

    public function video() {
        require_once('video.class.php');
        $video = new Video($this->registry);
        exit();
    }

    /*
      404 page
     *      */

    public function page404() {
        echo '';
        exit();
    }

    public function api() {
        //So here the web application makes use of the same services
        $app = $this->app;
        $container = $app->getContainer();
        $container['violin'] = new Violin\Violin;
        //Setup ok
        $this->app->group('/api', function() use($app) {

            //Location of all api routes, these routes operate differently because of the access point
            require_once(BASE . '/application/controller/api/client/libraries.php');
        });
        $this->app->run();
    }

}

?>
