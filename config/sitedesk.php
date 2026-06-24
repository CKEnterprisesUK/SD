<?php

return [

    'product_name' => env('SITEDESK_PRODUCT_NAME', 'SiteDesk'),

    'powered_by_text' => env(
        'SITEDESK_POWERED_BY_TEXT',
        'Powered by SiteDesk — A CK Enterprises Product'
    ),

    'roles' => [
        'admin' => 'admin',
        'contractor' => 'contractor',
    ],

    'user_statuses' => [
        'active' => 'active',
        'inactive' => 'inactive',
        'invited' => 'invited',
    ],

    'timesheet_statuses' => [
        'draft' => 'draft',
        'submitted' => 'submitted',
        'approved' => 'approved',
        'rejected' => 'rejected',
    ],

    'invoice_statuses' => [
        'draft' => 'draft',
        'issued' => 'issued',
        'paid' => 'paid',
        'void' => 'void',
    ],

];