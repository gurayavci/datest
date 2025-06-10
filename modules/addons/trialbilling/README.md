# Trial Billing WHMCS Addon

This addon provides a 7-day free trial for all products except domain registrations.
When a client orders a product, the initial invoice is zeroed and marked as paid.
After 7 days, the service is suspended and a new invoice is generated for the original price.

## Installation
1. Copy the `trialbilling` folder to your WHMCS `modules/addons/` directory.
2. From the WHMCS admin area, navigate to **Setup > Addon Modules** and activate *Trial Billing*.
3. Configure the addon permissions if required.

## How It Works
- At order time, all non-domain items in the invoice are set to zero and marked paid.
- The original price and service ID are stored in the `mod_trialbilling` table.
- A daily cron checks for expired trials. After 7 days it creates an invoice for the original amount and suspends the service.

## File Map
```
modules/
└─ addons/
   └─ trialbilling/
      ├─ trialbilling.php     # Addon module definition
      ├─ hooks.php            # Hooks implementing trial logic
      └─ README.md           # This documentation
```
