<?php

class Registry{
/**
*Array of objects
*/
private $objects;
private $app;
/**
*Array of settings
*/
private $settings;
    public function __constuct(){
    }
/**
*Create a new object and store it in the registry
*@param String $object the object file prefix
*@param String $key pair for the object
*@return void
*/
public function createAndStoreSingletonObject($object,$key){
    require_once($object.'.class.php');
    $this->objects[ $key ] = $object::getInstance($this);
}
public function createAndStoreObject($object,$key){
    require_once($object.'.class.php');
    $this->objects[ $key ] = new $object( $this );
}
/**
*Store Setting
*@param String $setting the setting data
*@param String $key the key pair for the settings array
*@return void
*/
public function storeSetting($setting,$key){
    $this->settings[$key]=$setting;
}
/**
*
*Get a setting from the registries store
*@param String $key the settings array key
*@return String the setting data
*/
public function getSetting($key){
    return $this->settings[$key];
}
/**
*
*Get an object from the registries store
*@param String $key the objects array key
*@return Object
*/
public function getObject($key){
    return $this->objects[$key];
}
}
?>
