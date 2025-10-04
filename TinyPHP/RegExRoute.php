<?php
class TinyPHP_RegExRoute extends TinyPHP_Route {


    public function __construct($route, $handler = array()) {
        $this->routePattern = $route;
        $this->routeHandler = $handler;  //handler defines controller and action;
        $this->type ='regex';
    }



}

?>
