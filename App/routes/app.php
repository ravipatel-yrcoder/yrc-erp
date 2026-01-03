<?php
/**
 * Sample
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
            ],
        ]
    ]
];
* */
return [
    "module" => "app",
    "prefix" => "",
    "routes" => [
        "front" => [
            [
                "pattern" => "/",
                "action" => "home",
            ],
            [
                "pattern" => "/about-us",
                "action" => "aboutus",
            ],
            [
                "pattern" => "/contact-us",
                "action" => "contactus",
            ],
        ],
        "auth" => [
            [
                "pattern" => "/login",
                "action" => "login",
            ],
            [
                "pattern" => "/register",
                "action" => "register",
            ],
            [
                "pattern" => "/forgot-password",
                "action" => "forgotpassword",
            ],
            [
                "pattern" => "/reset-password",
                "action" => "resetpassword",
            ],
        ],
        "productmasters" => [
            [
                "pattern" => "/product-masters",
                "action" => "index",
            ],
        ],
        "products" => [
            [
                "pattern" => "/products",
                "action" => "index",
            ],
        ],
        "prodcategories" => [
            [
                "pattern" => "/product-categories",
                "action" => "index",
            ],
        ],
        "invsettings" => [
            [
                "pattern" => "/settings/inventory",
                "action" => "index",
            ]
        ],
        "locations" => [
            [
                "pattern" => "/company/locations",
                "action" => "index",
            ],
        ],
        /* Start - Inventory module */
        "invproducts" => [
            [
                "pattern" => "/inv/products/:id/stock-locations",
                "name" => "prod-stock-locations",
                "action" => "stockLocations",
            ]
        ],
        /* End - Inventory module */
        
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