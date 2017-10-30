<?php

/*
 * Whmcs Garanti Bankası Payment Callback 
*/



# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");


$gatewaymodule = "garanti";
$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) die("Module Not Activated");
    // Gelen MD Status Kontrolleri     
    $strMDStatus = $_POST["mdstatus"];
    if($strMDStatus == "5"){
        echo "Doğrulama yapılamıyor";
    }if($strMDStatus == "7"){
        echo "Sistem Hatası";
    }if($strMDStatus == "8"){
        echo "Bilinmeyen Kart No";
    }if($strMDStatus == "0"){
        echo "Doğrulama Başarısız, 3-D Secure imzası geçersiz.";
    }

    //Sonuçlar değerlendiriliyor

    if ($strMDStatus == "1" || $strMDStatus == "2" || $strMDStatus == "3" || $strMDStatus == "4") 

    {

        $strMode = $_POST['mode'];
        $strVersion = $_POST['apiversion'];
        $strTerminalID = $_POST['clientid'];
        $strTerminalID_ = "0".$_POST['clientid'];
        $strProvisionPassword = $GATEWAY["Password"]; 
        $strProvUserID = $_POST['terminalprovuserid'];
        $strUserID = $_POST['terminaluserid'];
        $strMerchantID = $_POST['terminalmerchantid'];
        $strIPAddress = $_POST['customeripaddress'];
        $strEmailAddress = $_POST['customeremailaddress'];
        $strOrderID = $_POST['orderid'];
        $strNumber = ""; //Kart bilgilerinin boş gitmesi gerekiyor
        $strExpireDate = ""; //Kart bilgilerinin boş gitmesi gerekiyor
        $strCVV2 = ""; //Kart bilgilerinin boş gitmesi gerekiyor
        $strAmount = $_POST['txnamount'];
        $strCurrencyCode = $_POST['txncurrencycode'];
        $strInstallmentCount = $_POST['txninstallmentcount'];
        $strCardholderPresentCode = "13"; //3D Model işlemde bu değer 13 olmalı
        $strType = $_POST['txntype'];
        $strMotoInd = "N";
        $strAuthenticationCode = $_POST['cavv'];
        $strSecurityLevel = $_POST['eci'];
        $strTxnID = $_POST['xid'];
        $strMD = $_POST['md'];
        $SecurityData = strtoupper(sha1($strProvisionPassword.$strTerminalID_));
        $HashData = strtoupper(sha1($strOrderID.$strTerminalID.$strAmount.$SecurityData)); //Hash işlemi yapılıyor.
        if ($strMode == "TEST") {
            $strHostAddress = "https://sanalposprovtest.garanti.com.tr/VPServlet"; //Provizyon'un xml ile gönderileceği adres
        } else {
            $strHostAddress = "https://sanalposprov.garanti.com.tr/VPServlet"; //Provizyon'un xml ile gönderileceği adres
        }

        //Provizyon post şablonu
        $strXML = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>

                    <GVPSRequest>

                    <Mode>$strMode</Mode>

                    <Version>$strVersion</Version>

                    <ChannelCode></ChannelCode>

                    <Terminal><ProvUserID>$strProvUserID</ProvUserID><HashData>$HashData</HashData><UserID>$strUserID</UserID><ID>$strTerminalID</ID><MerchantID>$strMerchantID</MerchantID></Terminal>

                    <Customer><IPAddress>$strIPAddress</IPAddress><EmailAddress>$strEmailAddress</EmailAddress></Customer>

                    <Card><Number></Number><ExpireDate></ExpireDate><CVV2></CVV2></Card>

                    <Order><OrderID>$strOrderID</OrderID><GroupID></GroupID><AddressList><Address><Type>B</Type><Name></Name><LastName></LastName><Company></Company><Text></Text><District></District><City></City><PostalCode></PostalCode><Country></Country><PhoneNumber></PhoneNumber></Address></AddressList></Order><Transaction><Type>$strType</Type><InstallmentCnt>$strInstallmentCount</InstallmentCnt><Amount>$strAmount</Amount><CurrencyCode>$strCurrencyCode</CurrencyCode><CardholderPresentCode>$strCardholderPresentCode</CardholderPresentCode><MotoInd>$strMotoInd</MotoInd><Secure3D><AuthenticationCode>$strAuthenticationCode</AuthenticationCode><SecurityLevel>$strSecurityLevel</SecurityLevel><TxnID>$strTxnID</TxnID><Md>$strMD</Md></Secure3D>

                    </Transaction>

                    </GVPSRequest>";



        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $strHostAddress);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1) ;
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=".$strXML);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $results = curl_exec($ch);
        curl_close($ch);


$cevap = new SimpleXMLElement($results);

$kayit = '
ProvUserID : '.$cevap->Terminal->ProvUserID.'
UserID :'.$cevap->Terminal->UserID.'
ID : '.$cevap->Terminal->ID.'</ID>
MerchantID : '.$cevap->Terminal->MerchantID.'
Kullanıcı Bilgileri : 
IP Adresi :'.$cevap->Customer->IPAddress.'
Email Adresi : '.$cevap->Customer->EmailAddress.'
Ödeme Bilgileri
OrderID : '.$cevap->Order->OrderID.'
Transaction Bilgileri :
Source : '.$cevap->Transaction->Response->Source.'
Code : '.$cevap->Transaction->Response->Code.'
ReasonCode : '.$cevap->Transaction->Response->ReasonCode.'
Mesaj : '.$cevap->Transaction->Response->Message.'
Sistem Hata Mesajı : '.$cevap->Transaction->Response->SysErrMsg.'
RetrefNum : '.$cevap->Transaction->RetrefNum.'
BatchNum : '.$cevap->Transaction->BatchNum.'
SequenceNum : '.$cevap->Transaction->SequenceNum.'
ProvDate : '.$cevap->Transaction->ProvDate.'
CardNumberMasked : '.$cevap->Transaction->CardNumberMasked.'
CardHolderName : '.$cevap->Transaction->CardHolderName.'
CardType : '.$cevap->Transaction->CardType.'
HashData : '.$cevap->Transaction->HashData.'
XML : '.$results;

$ReasonCode = $cevap->Transaction->Response->ReasonCode;

$invoiceid = checkCbInvoiceID($strOrderID,$GATEWAY["name"]);
    checkCbTransID($transid);
    if (00 == (int)$ReasonCode) {
        addInvoicePayment($strOrderID,$authcode,$StrAmount,"0",$gatewaymodule);
        logTransaction($GATEWAY["name"],$kayit,"Successful");
        echo "<script>alert('Ödeme Tamamlandı');</script>";
        callback3DSecureRedirect($strOrderID, true);

    } else {
        logTransaction($GATEWAY["name"],$kayit,"Unsuccessful");
        echo "Ödeme işlemi tamamlanamadı<br/>";
        echo 'Hata : '.$cevap->Transaction->Response->ErrorMsg.' - '.$cevap->Transaction->Response->SysErrMsg;
        callback3DSecureRedirect($strOrderID, false);

    }
}
