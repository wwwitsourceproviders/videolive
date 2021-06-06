<?php

class Page404 {

    /**
     * Array of objects
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
    private $URI;
    private $Token;

    /**
     * Construct our site object
     */
    public function __construct(Registry $registry) {
        require_once(BASE . '/application/libraries/services/user.class.php');
        require_once(BASE . '/application/libraries/services/helperclasses/serviceexception.class.php');
        require_once(BASE . '/application/libraries/services/helperclasses/affiliates.class.php');
        require_once(BASE . '/application/libraries/services/classes/generalPublic.class.php');
        require_once(BASE . '/application/libraries/services/classes/guest.class.php');
        require_once(BASE . '/application/libraries/services/classes/student.guest.class.php');
        require_once(BASE . '/application/libraries/services/classes/student.class.php');
        require_once(BASE . '/application/libraries/services/classes/instructor.class.php');
        require_once(BASE . '/application/libraries/services/classes/administrator.guest.class.php');
        require_once(BASE . '/application/libraries/services/classes/administrator.class.php');
        $this->registry = $registry;
        $this->Token = new Token(185);
        $this->URI = URI::getInstance();

        $this->load();
    }

    /*
      Load the home page
     */

    public function load() {
        header('HTTP/1.0 404 Not Found', true, 404);
        $this->registry->getObject('page')->buildTemplate('404.html');
        $edit = array(
            'config_base' => $GLOBALS['config_base'],
            'config_base_url' => $GLOBALS['config_base_left'],
            'StyleBase' => $GLOBALS['config_base'] . '/css/pages/404.css',
            'PageScript' => 'pages/404.js',
            'timestamp' => time(),
            'title'=>'404',
            'token' => $this->Token->retrieveToken()
        );
        $this->registry->getObject('page')->CustomizeMany($edit);
        $this->registry->getObject('page')->AppendCode(
                '<script type=\'text/javascript\'>'
                . '</script>'
        );
        echo $this->registry->getObject('page')->getContent();
        exit();
    }

}

?>
