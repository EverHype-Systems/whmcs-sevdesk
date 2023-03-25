<?php
require_once __DIR__ . '/../../../init.php';


use Illuminate\Database\Capsule\Manager as Capsule;

function germanDate($date)
{
    $fd = new DateTime($date);
    return $fd->format('d.m.Y');
}

function isCountryEU($eu, $countryCode)
{
    foreach ($eu as $key => $value) {
        if ($key == $countryCode) {
            return true;
        }
    }
    return false;
}

function getTaxRateEU($eu, $countryCode)
{
    return $eu[$countryCode];
}

class WHMCSClientHelper
{
    public static function isEU($clientID)
    {
        return isCountryEU(WHMCSTaxHelper::getEuList(), self::getCountry($clientID));
    }

    public static function getCountry($clientID)
    {
        return Capsule::table('tblclients')->where('id', $clientID)->first()->country;
    }

    public static function isCustomerBusiness($clientID)
    {
        $business = Capsule::table('tblclients')->where('id', $clientID)->first()->country;
        return (!empty($business) && strlen($business) > 3);
    }
}


class sevDeskModuleAccessHelper
{
    public static function isModuleReady()
    {
        if (self::getEverHypeKey() == null or strlen(self::getEverHypeKey()) > 0) {
            return false;
        } elseif (self::getsevDeskKey() == null or strlen(self::getsevDeskKey()) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public static function getEverHypeKey()
    {
        return Capsule::table('tbladdonmodules')->where('module', 'sevdesk')->where('setting', 'everhype_license_key')->first()->value;
    }

    public static function getsevDeskKey()
    {
        return Capsule::table('tbladdonmodules')->where('module', 'sevdesk')->where('setting', 'everhype_sevdesk_key')->first()->value;
    }

    public static function getStartInvoice()
    {
        return Capsule::table('tbladdonmodules')->where('module', 'sevdesk')->where('setting', 'everhype_start_invoice')->first()->value;
    }

    public static function getInvoiceStatusUpload()
    {
        return Capsule::table('tbladdonmodules')->where('module', 'sevdesk')->where('setting', 'everhype_invoice_all')->first()->value;
    }
}

class WHMCSTaxHelper
{
    public static function getTaxType()
    {
        if (self::isCustomerSmallBusiness()) {
            return 'gross';
        } else {
            if (self::isTaxIncluded()) {
                return 'gross';
            } else {
                return 'net';
            }
        }
    }

    public static function isCustomerSmallBusiness()
    {
        # Die Steuereinstellungen hierfür müssen somit ausgeschaltet sein => Einstellung darf nicht auf "ON" stehen
        return "on" != Capsule::table('tblconfiguration')->where('setting', 'TaxEnabled')->first()->value;
    }

    public static function isTaxIncluded()
    {
        # Inclusive => inkl. MwSt.
        # Exclusive => zzgl. MwSt.
        return 'Inclusive' == Capsule::table('tblconfiguration')->where('setting', 'TaxType')->first()->value;
    }

    public static function getEuList()
    {
        return [
            'BE' => 21,
            'BG' => 20,
            'CZ' => 21,
            'DK' => 25,
            'DE' => 19,
            'EE' => 20,
            'GR' => 23,
            'ES' => 21,
            'FR' => 20,
            'HR' => 25,
            'IE' => 23,
            'IT' => 22,
            'CY' => 19,
            'LV' => 21,
            'LT' => 21,
            'LU' => 17,
            'HU' => 27,
            'MT' => 18,
            'NL' => 21,
            'AT' => 20,
            'PL' => 23,
            'PT' => 23,
            'RO' => 19,
            'SI' => 22,
            'SK' => 20,
            'FI' => 24,
            'SE' => 25
        ];
    }
}

class DatabaseHelper
{
    public static function getInvoicesByStatus($status)
    {
        return Capsule::table('tblinvoices')
            ->where('status', $status)
            ->get()->toArray();
    }

    public static function getInvoiceItemsByInvoice($invoiceid)
    {
        return Capsule::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceid)
            ->get()->toArray();
    }

    public static function getInvoiceData($invoiceid)
    {
        return Capsule::table('tblinvoices')
            ->where('id', $invoiceid)
            ->first();
    }

    public static function getWHMCSURL()
    {
        return rtrim(Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->first()->value,
                '/') . '/';
    }
}

class InvoicesHelper
{
    public static function getUnintegratedInvoices()
    {
        $unintegrated = [];
        $paidInvoices = self::getPaidInvoices();
        foreach ($paidInvoices as $invoice) {
            if (!self::isInvoiceIntegrated($invoice->id)) {
                array_push($unintegrated, $invoice);
            }
        }
        return $unintegrated;
    }

    public static function getPaidInvoices()
    {
        $invoices = [];
        if (sevDeskModuleAccessHelper::getInvoiceStatusUpload() == 'on') {
            foreach (DatabaseHelper::getInvoicesByStatus('Unpaid') as $invoice) {
                array_push($invoices, $invoice);
            }
            foreach (DatabaseHelper::getInvoicesByStatus('Cancelled') as $invoice) {
                array_push($invoices, $invoice);
            }
            foreach (DatabaseHelper::getInvoicesByStatus('Refunded') as $invoice) {
                array_push($invoices, $invoice);
            }
            foreach (DatabaseHelper::getInvoicesByStatus('Overdue') as $invoice) {
                array_push($invoices, $invoice);
            }
            foreach (DatabaseHelper::getInvoicesByStatus('Collections') as $invoice) {
                array_push($invoices, $invoice);
            }
            foreach (DatabaseHelper::getInvoicesByStatus('Payment Pending') as $invoice) {
                array_push($invoices, $invoice);
            }
        }

        foreach (DatabaseHelper::getInvoicesByStatus('Paid') as $invoice) {
            array_push($invoices, $invoice);
        }

        return $invoices;
    }

    public static function isInvoiceIntegrated($invoiceid)
    {
        return 1 == Capsule::table('everhype_sevdesk_invoices')->where('invoiceid', $invoiceid)->count();
    }

    public static function getInvoiceItems($invoiceid)
    {
        return DatabaseHelper::getInvoiceItemsByInvoice($invoiceid);
    }

    public static function getInvoiceNum($invoiceid)
    {
        return Capsule::table('tblinvoices')->where('id', $invoiceid)->first()->invoicenum;
    }

    public static function getsevdeskUserByClient($userid)
    {
        return Capsule::table('everhype_sevdesk_contacts')->where('userId', $userid)->first()->sevdesk_id;
    }

    public static function getDirName($date)
    {
        $dt = strtotime($date);
        return __DIR__ . '/invoices/' . date('Y', $dt) . '/' . date('m');
    }
}

class sevdeskInvoice
{
    private $invoiceid;
    private $invoiceitems;
    private $invoicenum;
    private $data;

    /**
     * @param $invoiceid
     */
    public function __construct($invoiceid)
    {
        $this->invoiceid = $invoiceid;
        $this->invoicenum = InvoicesHelper::getInvoiceNum($this->invoiceid);
        $this->invoiceitems = [];
        $this->data = DatabaseHelper::getInvoiceData($invoiceid);
        $this->fetch_invoiceitems();
    }

    private function fetch_invoiceitems()
    {
        $this->invoiceitems = InvoicesHelper::getInvoiceItems($this->invoiceid);
    }

    public function isPaid()
    {
        if (sevDeskModuleAccessHelper::getInvoiceStatusUpload() == 'on') {
            return true;
        }
        return "Paid" == Capsule::table('tblinvoices')->where('id', $this->invoiceid)->first()->status;
    }

    public function isIntegrated()
    {
        return InvoicesHelper::isInvoiceIntegrated($this->invoiceid);
    }

    public function integrateInvoice()
    {
        # we need the pdf later.
        # We first create the customer & trigger the integration with lexoffice.
        # Trigger Client update
        localAPI('UpdateClient', [
                "clientid" => $this->data->userid,
                "status" => "Active"
            ]
        );
        sleep(1);

        $this->savePDF();

        $voucherFileCurl = curl_init();

        curl_setopt_array($voucherFileCurl, array(
            CURLOPT_URL => 'https://my.sevdesk.de/api/v1/Voucher/Factory/uploadTempFile',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile(
                    InvoicesHelper::getDirName($this->data->date) . '/' . $this->invoiceid . '.pdf'
                )
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . sevDeskModuleAccessHelper::getsevDeskKey(),
            ],
        ));
        # true => to an array
        $voucherFileResponse = json_decode(curl_exec($voucherFileCurl), true);
        $statusCode = curl_getinfo($voucherFileCurl, CURLINFO_HTTP_CODE);
        if ($statusCode != 200 && $statusCode != 201 && $statusCode != 202) {
            return;
        }

        $filename = $voucherFileResponse['objects']['filename'];


        $fields = $this->generateSevDeskData();
        $fields['filename'] = $filename;
        # go through all fields
        foreach ($fields['voucherItems'] as $item) {
            if ($item['amount'] < 0) {
                return false;
            }
        }

        $integrateCurl = curl_init();
        curl_setopt_array($integrateCurl, array(
            CURLOPT_URL => 'https://my.sevdesk.de/api/v1/Voucher/Factory/saveVoucher',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($fields),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . sevDeskModuleAccessHelper::getsevDeskKey(),
                'Content-Type: application/json'
            ],
        ));

        # True => to an array
        $voucherResponse = json_decode(curl_exec($integrateCurl), true);
        $statusCode = curl_getinfo($integrateCurl, CURLINFO_HTTP_CODE);
        # we do not continue
        if ($statusCode != 200 && $statusCode != 201) {
            throw new Exception('Could not create voucher. Please check. Error: ' . json_encode($voucherResponse));
        }

        $this->markAsIntegrated($voucherResponse['objects']['voucher']['id']);
        return true;
    }

    private function savePDF()
    {

        $dir = InvoicesHelper::getDirName($this->data->date);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $fileName = $dir . '/' . $this->invoiceid . '.pdf';
        $file = fopen($fileName, "w+");
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_FILE => $file,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_URL => DatabaseHelper::getWHMCSURL() . 'PDFInvoiceSevdesk.php?user=' . sevdeskModuleAccessHelper::getsevDeskKey() . '&pass=' . sevdeskModuleAccessHelper::getEverHypeKey() . '&id=' . $this->invoiceid
        ]);
        curl_exec($ch);
        curl_close($ch);
        fclose($file);
    }

    private function generateSevDeskData()
    {
        $voucherItems = $this->generateVoucherItems();
        $fields = [
            "voucher" => [
                'voucherDate' => germanDate($this->data->date),
                'supplier' => [
                    'id' => InvoicesHelper::getsevdeskUserByClient($this->data->userid),
                    'objectName' => 'Contact'
                ],
                'description' => (!empty($this->invoicenum)) ? $this->invoicenum : strval($this->invoiceid),
                'paymentDeadline' => germanDate($this->data->duedate),
                'deliveryDate' => germanDate($this->data->duedate),
                'status' => 100, # OPEN
                'creditDebit' => 'D', # Debit
                'voucherType' => 'VOU',
                "mapAll" => "true",
                'curreny' => 'EUR',
                'hidden' => false,
                "objectName" => "Voucher"
            ],
            "voucherPosSave" => $voucherItems
        ];

        # Check for taxType
        if (WHMCSTaxHelper::isCustomerSmallBusiness() or WHMCSClientHelper::getCountry($this->data->userid) == 'DE') {
            $fields['taxType'] = 'default';
        } else {
            # Check if customer is EU or not
            if (WHMCSClientHelper::isCustomerBusiness($this->data->userid) && WHMCSClientHelper::isEU($this->data->userid)) {
                $fields['taxType'] = 'eu';
            } elseif (WHMCSClientHelper::isEU($this->data->userid)) {
                $fields['taxType'] = 'default';
            } else {
                $fields['taxType'] = 'noteu';
            }
        }

        return $fields;
    }

    private function generateVoucherItems()
    {
        $voucherItems = [];
        foreach ($this->invoiceitems as $item) {
            $item->amount = floatval($item->amount);
            $itemarr = [
                'accountingType' => [
                    'id' => 26,
                    'objectName' => 'AccountingType'
                ],
                'taxRate' => (WHMCSTaxHelper::isCustomerSmallBusiness()) ? 0 : floatval($this->data->taxrate),
                'isAsset' => false,
                'assetMemoValue' => false,
                'isGwg' => false,
                'isPercentage' => true,

                'net' => !((WHMCSTaxHelper::isTaxIncluded())),
                'mapAll' => true,
                'comment' => $item->description,
                'objectName' => 'VoucherPos',
                'specialTaxCase' => false,
                'taxRateDisabled' => WHMCSTaxHelper::isCustomerSmallBusiness(),
            ];

            if (WHMCSTaxHelper::isTaxIncluded()) {
                $itemarr['sumGross'] = $item->amount;
                $itemarr['sumNet'] = $item->amount - $this->calculateVoucherItemTaxAmount($item->amount);
                $itemarr['sum'] = $item->amount - $this->calculateVoucherItemTaxAmount($item->amount);
                $itemarr['vpAmount'] = $item->amount;
            } else {
                $itemarr['sumNet'] = $item->amount;
                $itemarr['sumGross'] = $item->amount + $this->calculateVoucherItemTaxAmount($item->amount);
                $itemarr['sum'] = $item->amount;
                $itemarr['vpAmount'] = $item->amount;
            }

            $itemarr['vpAmount'] = $itemarr['sumGross'];

            $itemarr['sumTax'] = $this->calculateVoucherItemTaxAmount($item->amount);
            #var_dump($itemarr);
            array_push($voucherItems, $itemarr);
        }
        return $voucherItems;
    }

    private function calculateVoucherItemTaxAmount($amount)
    {
        if (WHMCSTaxHelper::isCustomerSmallBusiness()) {
            # Kleinunternehmer => keine Steuer
            return floatval(0);
        } else {
            if (WHMCSTaxHelper::isTaxIncluded()) {
                # Steuer ist im Preis inbegriffen.
                return round(
                    $amount - ($amount / (1 + $this->data->taxrate / 100)),
                    2,
                    PHP_ROUND_HALF_UP);
            } else {
                # Steuern kommen zzgl. auf den Artikelpreis
                return round(
                    ($amount * (1 + $this->data->taxrate / 100)) - $amount,
                    2,
                    PHP_ROUND_HALF_UP);
            }
        }
    }

    private function markAsIntegrated($sevdeskid)
    {
        try {
            Capsule::table('everhype_sevdesk_invoices')->insert(
                [
                    'invoiceid' => $this->invoiceid,
                    'sevdesk_id' => $sevdeskid,
                    'uploaded_at' => date("Y-m-d H:i:s", time())
                ]
            );
            Capsule::commit();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function calculateVoucherTotalGrossAmount($voucherItems)
    {
        $total = floatval(0);
        $total_tax = floatval(0);
        foreach ($voucherItems as $item) {
            $total += $item['amount'];
            $total_tax += $item['taxAmount'];
        }
        if (WHMCSTaxHelper::isTaxIncluded() or WHMCSTaxHelper::isCustomerSmallBusiness()) {
            return round($total, 2, PHP_ROUND_HALF_UP);
        } else {
            return round($total + $total_tax, 2, PHP_ROUND_HALF_UP);
        }
    }

    private function calculateVoucherTotalTaxAmount($voucherItems)
    {
        $total_tax = floatval(0);
        foreach ($voucherItems as $item) {
            $total_tax += $item['taxAmount'];
        }
        return $total_tax;
    }
}

class sevdeskClient
{
    private $vars;
    /**
     * @var mixed
     */
    private $userID;

    /**
     * @param $vars
     */
    public function __construct($vars)
    {
        $this->vars = $vars;
        $this->userID = $this->getUserIDFromVars();
    }

    public function getUserIDFromVars()
    {
        # we have to search it now
        if (array_key_exists('userid', $this->vars)) {
            return $this->vars['userid'];
        } elseif (array_key_exists('user_id', $this->vars)) {
            return $this->vars['user_id'];
        } elseif (array_key_exists('clientid', $this->vars)) {
            return $this->vars['clientid'];
        } elseif (array_key_exists('client_id', $this->vars)) {
            return $this->vars['client_id'];
        } else {
            throw new Exception('COULD NOT FETCH USER ID');
        }
    }

    public function integrate()
    {

        if ($this->isClientIntegrated()) {
            $this->updateContact();
        } else {
            $this->createContact();
        }

    }

    public function isClientIntegrated()
    {
        return 1 == Capsule::table('everhype_sevdesk_contacts')->where('userid', $this->userID)->count();
    }

    private function updateContact()
    {
        $fields = $this->getFields();
        $updateCurl = curl_init();
        curl_setopt_array($updateCurl, array(
            CURLOPT_URL => 'https://my.sevdesk.de/api/v1/Contact/' . $this->getSevdeskID(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($fields),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . sevDeskModuleAccessHelper::getsevDeskKey(),
                'Content-Type: application/json'
            ],
        ));

        $updateResponse = json_decode(curl_exec($updateCurl));
        $statusCode = curl_getinfo($updateCurl, CURLINFO_HTTP_CODE);

        if ($statusCode != 200 && $statusCode != 201 && $statusCode != 202 && $statusCode != 204) {
            throw new Exception('Could not update user. Error => ' . json_encode($updateResponse));
        }
    }

    private function getFields()
    {
        $fields = [
            'customerNumber' => 'WHMCS-' . $this->userID,
            'surename' => $this->vars['firstname'],
            'familyname' => $this->vars['lastname'],
            'category' => [
                'id' => 3,
                'objectName' => 'Category'
            ],
            'description' => "Importiert aus WHMCS. Kundenummer #" . $this->userID,
        ];

        return $fields;
    }

    private function getSevdeskID()
    {
        return Capsule::table('everhype_sevdesk_contacts')->where('userid', $this->userID)->first()->sevdesk_id;
    }

    private function createContact()
    {
        $fields = $this->getFields();
        $createCurl = curl_init();
        curl_setopt_array($createCurl, array(
            CURLOPT_URL => 'https://my.sevdesk.de/api/v1/Contact',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($fields),

            CURLOPT_HTTPHEADER => [
                'Authorization: ' . sevDeskModuleAccessHelper::getsevDeskKey(),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ));
        $result = curl_exec($createCurl);
        $createResponse = json_decode($result, true);
        $statusCode = curl_getinfo($createCurl, CURLINFO_HTTP_CODE);

        if ($statusCode != 200 && $statusCode != 201 && $statusCode != 202) {
            throw new Exception('Could not create user. Error => ' . json_encode($createResponse));
        }

        # everything is ok
        Capsule::table('everhype_sevdesk_contacts')->insert([
            'userid' => $this->userID,
            'sevdesk_id' => $createResponse['objects']['id'],
        ]);
        Capsule::commit();
    }

    private function get($name)
    {
        return $this->vars[$name];
    }

}


class CronHelper
{
    public static function doDailyCron()
    {
        $start_from = sevDeskModuleAccessHelper::getStartInvoice();
        if ($start_from == null || strlen($start_from) == 0) {
            $start_from = 0;
        }

        $paidInvoices = InvoicesHelper::getPaidInvoices();
        # drop all paid invoices which are already integrated
        foreach ($paidInvoices as $invoice) {
            $invoiceModel = new sevdeskInvoice($invoice->id);
            if (!$invoiceModel->isIntegrated() && $invoiceModel->isPaid()) {
                try {
                    if ($invoice->id >= $start_from) {
                        echo "Rechnung wird nun syncronsiert -> #" . $invoice->id . PHP_EOL;
                        $invoiceModel->integrateInvoice();
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }
    }
}

?>
