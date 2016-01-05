<?php
/**
 * WHMCS Garanti Bankası Modülü
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


function garanti_MetaData()
{
    return array(
        'DisplayName' => 'Garanti Bankası',
        'APIVersion' => '1.0', // Use API Version 1.1
        'DisableLocalCredtCardInput' => false,
        'TokenisedStorage' => false,
    );
}

function garanti_config()
{
    return $dene = array(

        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Garanti Bankası',
        ),
            'MerchantID' => array(
            'FriendlyName' => 'İşyeri Numarası',
            'Type' => 'text',
            'Size' => '9',
            'Default' => '',
            'Description' => 'Örn : 5621234',
        ),
	    'TerminalIDS' => array(
            'FriendlyName' => 'Terminal Numarası',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '',
            'Description' => 'Örn : 111995',
        ),
            'TerminalProvID' => array(
            'FriendlyName' => 'Provizyon Kullanıcı Adı',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Örn : PROVAUT, PROVOOS',
        ),
            'TerminalUserID' => array(
            'FriendlyName' => 'Terminal Kullanıcı Adı',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Örn : 7ZKYM2YX',
        ),
            'Password' => array(
            'FriendlyName' => 'Şifre',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Örn : aaAA5533ss',
        ),
            '3dPassword' => array(
            'FriendlyName' => '3D Secure Şifresi',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Örn : 616c69476f6e656e32303435616c69476f6e656e32303435',
        ),
            'TestMode' => array(
            'FriendlyName' => 'Test Modu', 
            'Type' => 'yesno', 
            'Description' => 'Test modunu açmak için aktif ediniz', 
		)  
        
    );
    
    
}

/**
 * 3D onay süreci
 */
function garanti_3dsecure($params)
{

    // Banka Bilgileri
    $MerchantID = $params['MerchantID'];
    $TerminalIDS = $params['TerminalIDS'];
    $TerminalIDS_ = '0'.$params['TerminalIDS'];
    $TerminalProvID = $params['TerminalProvID'];
    $TerminalUserID = $params['TerminalUserID'];
    $Password = $params['Password'];
    $ThreeDSecurePassword = $params['3dPassword'];
    $accountId = $params['accountID'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Credit Card Parameters
    $cardType = $params['cardtype'];
    $cardNumber = $params['cardnum'];
    $cardExpiry = $params['cardexp'];
    $cardStart = $params['cardstart'];
    $cardIssueNumber = $params['cardissuenum'];
    $cardCvv = $params['cccvv'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $systemUrl = $params['systemurl'];
    $moduleDisplayName = $params['name'];
    $langPayNow = $params['langpaynow'];
    $moduleName = $params['paymentmethod'];
    $IPAddress = $_SERVER['REMOTE_ADDR'];
    $txntimestamp = date('YmdHis');

    // 
    if ("on" == $params['TestMode']) {
         $url = 'https://sanalposprovtest.garanti.com.tr/servlet/gt3dengine';
         $callbackurl = $params['systemurl'].'/modules/gateways/callback/garanti.php';
         $Mode = "TEST";
    } else {
         $url = "https://sanalposprov.garanti.com.tr/servlet/gt3dengine";
         $callbackurl = $params['systemurl'].'/modules/gateways/callback/garanti.php';
         $Mode = "PROD";
    }
   
    
    /* Para birimi uyarlama */
    $amount = (float) str_replace(',', "." , (string)$params['amount']);
    $amount = (float) $amount * 100;
    /* Para birimi uyarlama */
    
    $InstallmentCnt = (isset($params['InstallmentCnt']) && 1 < (int) $params['InstallmentCnt']) ? $params['InstallmentCnt']:"";
    
    
    
    /* Şifrelenecek Kısım */
    $SecurityData = strtoupper(sha1($Password.$TerminalIDS_));
    $HashData = strtoupper(sha1($TerminalIDS.$invoiceId.$amount.$callbackurl.$callbackurl.'sales'.''.$ThreeDSecurePassword.$SecurityData));
    /* Şifrelenecek Kısım */
    
    /* Form oluşturma */
    $postfields = array(
	'cardnumber' => $cardNumber,
        'cardexpiredatemonth' => substr($cardExpiry, 0, 2),
        'cardexpiredateyear' => substr($cardExpiry, 2, 2),
        'cardcvv2' => $cardCvv,
	'secure3dsecuritylevel' => '3d',
	'mode' => $Mode,
	'apiversion' => 'v0.01',
	'terminalid' => $TerminalIDS,
	'terminalprovuserid' => $TerminalProvID,
	'terminaluserid' => $TerminalUserID,
	'terminalmerchantid' => $MerchantID,
        'txntype' => 'sales',
        'txnamount' => $amount,
	'txncurrencycode' => '949',	
        'txninstallmentcount' => $InstallmentCnt,
        'orderid' => $invoiceId,
        'successurl' => $callbackurl,
        'errorurl' => $callbackurl,
        'customeremailaddress' => $email,
        'customeripaddress' => $IPAddress,
        'secure3dhash' => $HashData,
        'txntimestamp' => $txntimestamp,
        'lang' => 'tr',
    );

    $htmlOutput = '<form action="'.$url.'" method="post">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
    }
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
    
    /* Form Bitiş */
}
