<?php
return [
    "module" => "app", // it must match with the module name in /config/modules.php
    "prefix" => "app", // when its added make sure to not add that in pattern
    "routes" => [
        "front" => [
            [
                "pattern" => "/",
                "action" => "home", // must be lowercase
                "name" => "homepage", // optional
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
            [
                "pattern" => "/about-us",
                "action" => "aboutus", // must be lowercase                
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
            [
                "pattern" => "/contact-us",
                "action" => "contactus", // must be lowercase                
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
        ],
        "auth" => [
            [
                "pattern" => "/login",
                "action" => "login", // must be lowercase                
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
            [
                "pattern" => "/register",
                "action" => "register", // must be lowercase                
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
            [
                "pattern" => "/forgot-password",
                "action" => "forgotpassword", // must be lowercase                
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
            [
                "pattern" => "/reset-password",
                "action" => "resetpassword", // must be lowercase                
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
        ],
        /*"dashboard" => [
            [
                "pattern" => "/dashboard",
                "action" => "dashboard", // must be lowercase
                "name" => "dashboardpage", // optional
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
        ]*/
    ]    
];