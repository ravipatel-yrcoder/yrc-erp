<?php
return [
    "module" => "admin",
    "prefix" => "admin",
    "routes" => [

        /* Features */
        "features" => [
            [
                "pattern" => "/features",
                "name"    => "admin-features",
                "action"  => "index",
            ],
            [
                "pattern" => "/features/form-context",
                "name"    => "admin-features-ctx",
                "action"  => "formContext",
            ],
            [
                "pattern" => "/features/store",
                "name"    => "admin-features-store",
                "action"  => "store",
            ],
            [
                "pattern" => "/features/:id/update",
                "name"    => "admin-features-update",
                "action"  => "update",
            ],
            [
                "pattern" => "/features/:id/delete",
                "name"    => "admin-features-delete",
                "action"  => "delete",
            ],
        ],

    ],
];
