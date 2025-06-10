<?php
if (!defined('WHMCS')) {
    exit('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

function trialbilling_config()
{
    return [
        'name' => 'Trial Billing',
        'description' => 'Allows 7-day free trial for non-domain products and bills at full price after expiration.',
        'version' => '1.0',
        'author' => 'Example',
    ];
}

function trialbilling_activate()
{
    Capsule::schema()->create('mod_trialbilling', function ($table) {
        $table->increments('id');
        $table->integer('serviceid');
        $table->decimal('originalamount', 10, 2);
        $table->date('expirydate');
    });
    return ['status' => 'success'];
}

function trialbilling_deactivate()
{
    Capsule::schema()->dropIfExists('mod_trialbilling');
    return ['status' => 'success'];
}

function trialbilling_output($vars)
{
    echo '<p>Trial Billing module is active.</p>';
}
