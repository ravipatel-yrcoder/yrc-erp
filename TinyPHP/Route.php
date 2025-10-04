<?php
class TinyPHP_Route {

    //put your code here

    protected  $routePattern = '';
    protected  $routeHandler = '';
    protected  $type = '';

    public function __construct($route, $handler = array()) {
        $this->routePattern = $route;
        $this->routeHandler = $handler;  //handler defines controller and action;
        $this->type ='static';
    }

    public function getRoutePattern() {
        return $this->routePattern;
    }
    
    public function getRouteHandler() {
        return $this->routeHandler;
    }

    public function getRouteType() {
        return $this->type;
    }

}

?>
