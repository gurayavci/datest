<?php
if (!defined('WHMCS')) {
    exit('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

/**
 * Zero price on initial invoice for non-domain products
 * and store original price for billing after trial.
 */
add_hook('InvoiceCreationPreEmail', 1, function($vars) {
    $invoiceId = $vars['invoiceid'];
    $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceId)->get();
    $totalAdjustment = 0;
    foreach ($items as $item) {
        if ($item->type !== 'Domain' && $item->type !== 'AddFunds') {
            Capsule::table('mod_trialbilling')->insert([
                'serviceid' => $item->relid,
                'originalamount' => $item->amount,
                'expirydate' => date('Y-m-d', strtotime('+7 days')),
            ]);
            $totalAdjustment += $item->amount;
            Capsule::table('tblinvoiceitems')->where('id', $item->id)->update(['amount' => 0]);
        }
    }
    if ($totalAdjustment > 0) {
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
        $newTotal = $invoice->total - $totalAdjustment;
        Capsule::table('tblinvoices')->where('id', $invoiceId)
            ->update([
                'total' => $newTotal,
                'subtotal' => $invoice->subtotal - $totalAdjustment,
                'status' => 'Paid',
            ]);
    }
});

/**
 * Daily cron to invoice and suspend expired trial services
 */
add_hook('DailyCronJob', 1, function($vars) {
    $today = date('Y-m-d');
    $trials = Capsule::table('mod_trialbilling')->where('expirydate', '<=', $today)->get();
    foreach ($trials as $trial) {
        $service = Capsule::table('tblhosting')->where('id', $trial->serviceid)->first();
        if (!$service) {
            Capsule::table('mod_trialbilling')->where('id', $trial->id)->delete();
            continue;
        }
        // Create invoice for original amount
        localAPI('CreateInvoice', [
            'userid' => $service->userid,
            'date' => $today,
            'duedate' => $today,
            'itemdescription1' => 'Service ' . $service->id . ' trial ended',
            'itemamount1' => $trial->originalamount,
        ], 'admin');
        // Suspend the service
        localAPI('ModuleSuspend', [
            'accountid' => $service->id,
            'suspendreason' => 'Trial expired',
        ], 'admin');
        Capsule::table('mod_trialbilling')->where('id', $trial->id)->delete();
    }
});
