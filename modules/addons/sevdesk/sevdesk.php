<?php

if (!defined("WHMCS")) die("This file cannot be accessed directly");
use Illuminate\Database\Capsule\Manager as Capsule;


function sevdesk_config() {
    return [
        "name" => "sevDesk Export",
        "description" => "Dieses Modul exportiert alle bezahlten Rechnungen in sevdesk. Bitte beachten Sie, dass die Zuordnungen der Zahlungen manuell in sevDesk vorgenommen werden muss (GoBD).",
        "version" => 1.2,
        "author" => "EverHype Systems GbR",
        "language" => "german",
        "fields" => [
            "everhype_sevdesk_key" => [
                "FriendlyName" => "sevdesk Public API - Key",
                "Type" => "text",
                "Size" => "50",
                "Description" => "<br>Dieser Schlüssel dient der Authentifizierung innerhalb der API von sevdesk.
                <br>Die Lebensdauer des API Keys beträgt 24 Monate.<br>Sie können den Key bei Bedarf in der Benutzerverwaltung ersetzen.",
                "Default" => "",
            ],
            "everhype_license_key" => [
                "FriendlyName" => "Secret Key",
                "Type" => "text",
                "Size" => "50",
                "Description" => "<br>Dieser Schlüssel dient zur Absicherung von Schnittstellen. Nutzen Sie bitte hierfür eine uuid4 oder Ihren Lizenzschlüssel von EverHype",
            ],
            "everhype_start_invoice" => [
                "FriendlyName" => "Start - Rechnungsnummer",
                "Type" => "text",
                "Size" => "50",
                "Description" => "<br>Bitte beachten Sie, dass die Eingabe dieser Nummer bedeutet, dass <i>nur</i> Rechnungen, die eine größere Nummer als die oben definierte Nummer exportiert werden.<br>Alle Nummern, die drunter liegen, werden ignoriert.",
                "Default" => "0",
            ],
            "everhype_invoice_all" => [
                "FriendlyName" => "Sollen alle Rechnungen syncronisiert werden?",
                "Type" => "yesno",
                "Size" => "25",
                "Description" => "<br>Bitte beachten Sie, dass durch diese Auswahl alle Rechnungen, die nicht im Draft-/Entwurfstatus sind, für die Syncronisation in Frage kommen. Sollte diese Option nicht aktiviert sein, so werden lediglich bereits bezahlte Rechnungen hochgeladen.",
            ],
        ]
    ];
}

function sevdesk_activate (){
    try {
        if (!Capsule::schema()->hasTable('everhype_sevdesk_invoices')) {
            Capsule::schema()->create('everhype_sevdesk_invoices', function ($table) {
                $table->increments('id')->unique();
                $table->integer('invoiceid')->unique();
                $table->integer('sevdesk_id')->unique();
                $table->datetime('uploaded_at')->default('0000-00-00 00:00:00');
            });
        }
        if (!Capsule::schema()->hasTable('everhype_sevdesk_contacts')) {
            Capsule::schema()->create('everhype_sevdesk_contacts', function ($table) {
                $table->increments('id')->unique();
                $table->integer('userid')->unique();
                $table->integer('sevdesk_id')->unique();
            });
        }
        return [
            'status' => "success",
            'description' => 'sevdesk Export erfolgreich initalisiert.',
        ];
    } catch (Exception $e){
        return [
            'status' => "error",
            'description' => 'Unable to create sevdesk databases: ' . $e->getMessage(),
        ];
    }
}
?>