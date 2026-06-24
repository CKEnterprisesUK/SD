<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalSetting extends Model
{
    protected $fillable = [
        'portal_name',
        'company_name',
        'company_address',
        'company_number',
        'vat_number',
        'logo_path',
        'primary_colour',
        'accounts_email',
        'payment_terms_days',
        'invoice_wording',
        'pdf_footer',
    ];

    public static function current(): self
    {
        return self::firstOrCreate([], [
            'portal_name' => 'SiteDesk',
            'primary_colour' => '#1d70b8',
            'payment_terms_days' => 7,
            'pdf_footer' => 'Powered by SiteDesk — A CK Enterprises Product',
        ]);
    }
}