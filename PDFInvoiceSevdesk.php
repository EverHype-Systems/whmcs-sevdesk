<?php
include __DIR__ . '/init.php';
include __DIR__ . '/includes/invoicefunctions.php';

use WHMCS\Auth;
use Illuminate\Database\Capsule\Manager as Capsule;

$user = filter_var($_GET['user'], FILTER_SANITIZE_STRING);
$pass = filter_var($_GET['pass'], FILTER_SANITIZE_STRING);
$id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

$lexAPI = Capsule::table('tbladdonmodules')->where('module', 'sevdesk')->where('setting', 'everhype_sevdesk_key')->first()->value;
$everhypeAPI = Capsule::table('tbladdonmodules')->where('module', 'sevdesk')->where('setting', 'everhype_license_key')->first()->value;

if ($user == $lexAPI && $pass == $everhypeAPI) {
    if ($id > 0) {
        $pdfdata = pdfInvoice($id);

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=Rechnung-$id.pdf");

        echo $pdfdata;
        die();
    }
} else {
    $result = [
        'code' => 403,
        'error' => 'access denied'
    ];
    echo json_encode($result, JSON_PRETTY_PRINT);
    die();
}
?>
