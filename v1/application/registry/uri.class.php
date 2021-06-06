<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Page
 *
 * @author Chris
 */
class URI {

    private static $instance;
    private $base;
    private $page;
    private $args;
    private $extensions = array();

    function __construct() {
        
    }

    public function setBase($base) {
        $this->base = $base;
    }

    public function getBase() {
        return $this->base;
    }

    public function getPage() {
        if (isset($this->page)) {
            return $this->page;
        }

        if (isset($_GET['_route'])) {
            $page = explode('/', $_GET['_route']);


            $pagea = explode('.', $page[0]);
            if (count($pagea) > 1) {
                if ($pagea[1] == 'html' || $pagea[1] == 'htm') {
                    $this->page = $pagea[0];
                }
            } else {
                $this->page = $pagea[0];
            }
        } else {
            $this->page = 'index';
        }
        return $this->page;
    }

    public function setPage($page) {
        $this->page = $page;
    }

    public function getArguments() {
        $this->args = array();
        $page = explode('/', $_GET['_route']);
        $len = count($page);
        for ($i = 1; $i < $len; $i++) {
            $argm = explode('.', $page[$i]);
            if (count($argm) > 1) {
                $this->args[] = $argm[0];

                if (count($argm) > 2) {
                    $this->extensions[] = $argm[1];
                    $this->extensions[] = $argm[2];
                } else {
                    $this->extensions[] = $argm[1];
                }
            } else {
                $this->args[] = $page[$i];
            }
        }
        return $this->args;
    }

    public function getExtension() {
        return $this->extensions[count($this->extensions) - 1];
    }

    public function getExtensions() {
        if (count($this->extensions) > 1) {
            $ex = $this->extensions[0];
            for ($i = 1; $i < count($this->extensions); $i++) {
                $ex .= '.'.$this->extensions[$i];
            }
            return $ex;
        } else {
            return $this->extensions[0];
        }
    }

    public static function getInstance() {



        // this implements the 'singleton' design pattern.





        if (!isset(self::$instance)) {

            $c = __CLASS__;

            self::$instance = new $c();
        } // if



        return self::$instance;
    }

// getInstance
}

?>
