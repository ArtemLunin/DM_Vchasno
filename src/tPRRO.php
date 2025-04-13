<?php
define('DM_ADDRESS', '10.255.3.184');
define('DM_PORT', '3939');
define('DM_PATH', '/dm/execute');
define('DM_PROTOCOL', 'http');

function recursiveConvertEncoding($data, $from = 'UTF-8', $to = 'Windows-1251') {
    if (is_array($data)) {
        $converted = [];
        foreach ($data as $key => $value) {
            $newKey = is_string($key) ? mb_convert_encoding($key, $to, $from) : $key;
            $converted[$newKey] = recursiveConvertEncoding($value, $from, $to);
        }
        return $converted;
    } elseif (is_string($data)) {
        return mb_convert_encoding($data, $to, $from);
    } else {
        return $data;
    }
}

class tPRRO {
    var $dm_address = '';
    var $dm_port = '3939';
    var $prro_name = '';
    var $fulladdr = '';
    var $protocol = 'http';
    var $path = '/dm/execute';
    var $ready;
    var $server = false;

    var $dm_request_data = [];

    function __construct($mode,$param) {
        $this->dm_address = DM_ADDRESS;
        $this->prro_name = $param['ipaddr'];
        if (isset($param['protocol']) && $param['protocol']) {
            $this->protocol = $param['protocol'];
        }
        if (isset($param['port']) && $param['port']) {
            $this->dm_port = $param['port'];
        }
        if (isset($param['path']) && $param['path']) {
            $this->path = $param['path'];
        }
        $this->fulladdr = $this->protocol. '://'. $this->dm_address. ':' .$this->dm_port . $this->path;

        $this->dm_request_data = [
            "ver"       => 6,
            "source"    => "API",
            "device"    => $this->prro_name,
            "type"      => 1,
            "fiscal"    => []
        ];
        $this->ready = true;
    }
    
    // function convertEncodingRecursive(array $array, $from = 'Windows-1251', $to = 'UTF-8') {
    //     array_walk_recursive($array, function (&$value) use ($from, $to) {
    //         if (is_string($value)) {
    //             $value = mb_convert_encoding($value, $to, $from);
    //         }
    //     });
    //     return $array;
    // }
    
    function getErrorTxt($raw_json, $retSuccess = '') {
        if (isset($raw_json) && ($res_json = json_decode($raw_json, true)) !== null) {
            if (isset($res_json['res']) && $res_json['res'] != 0) {
                return recursiveConvertEncoding($res_json['errortxt']);
            }
            return $retSuccess;
        }
        return "Unknown error";
    }

    function CheckConfig()
    {
        $mess = '';
        if(!$this->dm_address) 
        {
            $mess .= '�� ������ ip �����.';
        }
        if(!$this->prro_name) 
        {
            $mess .= '�� ������� ��� �����.';
        }
        return $mess;	
    }

    function SendCmd($cmd, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fulladdr);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        // curl_setopt($ch, CURLOPT_USERPWD, '1:0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $mess = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err != '') 
        {
            // return 'Error:' . $err;
            return false;
        }
        return $mess;
    }

    function OpenDay()
    {
        $this->dm_request_data['fiscal'] = [
            "task"  => 0
        ];
        return $this->getErrorTxt($this->SendCmd('', json_encode($this->dm_request_data)), null);
    }

    function CloseDay()
    {
        $mess = $this->GetStateKassa($kassir, $smena, $KASSACASH, $karta, $dt);
        // $mess .= $this->InOutCash(1, $KASSACASH);
        // if($mess != '' ) return $mess;
        // $mess.=$this->XReport();
        // $mess.=$this->CtrlCheckReport();
        // $mess.=$this->CtrlCheckReport();
        if(!$this->ZReport())  return false;
        if($mess != '' ) return $mess;
    }

    function GetConfig(&$forma, $r, $ro = false)
    {
        $forma->AddField($r,1,MakeTagSingl('��� �����', "ADDR", $this->prro_name, 30, $ro));
    }

function GetData($cmd)
{
//    $ch=curl_init();
// 	curl_setopt($ch, CURLOPT_URL, "http://".$this->fulladdr.$cmd);
// 	curl_setopt($ch, CURLOPT_HEADER, 0);
// 	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
// 	curl_setopt($ch, CURLOPT_USERPWD, '1:0');
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
//    $mess=curl_exec($ch);
//    $err=curl_error($ch);
//    curl_close($ch);
//    if($err != '') return false;
// 	return $mess;
} 

function PutData($cmd, $data)
{
//    $ch=curl_init();
// 	curl_setopt($ch, CURLOPT_URL, "http://".$this->fulladdr.$cmd);
// 	curl_setopt($ch, CURLOPT_HEADER, 1);
// 	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
// 	curl_setopt($ch, CURLOPT_USERPWD, '1:0');
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
//    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));//, 'X-HTTP-Method-Override: PUT'
//    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//    $mess=curl_exec($ch);
//    $err=curl_error($ch);
//    curl_close($ch);
//    if($err != '') return $mess .= $err;
// 	return $mess;
} 

function OpenPort()
{
   return true;
}
 
function ClosePort()
{
}

function GetLastError()
{
	return '';
}

function GetLastResult()
{
	return '';
}

function ResetError()
{
	return true;
}

function SetDate($dt)
{
   return '';
}
function SetTime($dt)
{
   return '';
}

function CheckReady()
{
   return true;
}

    function ZReport()
    {
        $this->dm_request_data['fiscal'] = [
            "task"  => 11,
            "cashier"   => ""
        ];
        // return $this->SendCmd('', json_encode($this->dm_request_data));
        return $this->getErrorTxt($this->SendCmd('', json_encode($this->dm_request_data)), true);
    }

    function getStatusPRRO()
    {
        $this->dm_request_data['fiscal'] = [
            "task"  => 18,
        ];
        return $this->SendCmd('', json_encode($this->dm_request_data));
    }

function Z3Report()
{
}

function XReport()
{
	$this->dm_request_data['fiscal'] = [
        "task"  => 10,
        "cashier"   => ""
    ];
    return $this->getErrorTxt($this->SendCmd('', json_encode($this->dm_request_data)), null);
}

function X3Report()
{
	// $this->GetData('/cgi/proc/printreport?20');
}

function CtrlCheckReport()
{
	// $this->GetData('/cgi/proc/printmmcjrn?0&0&0');
}

function FMDayReport($flg, $ds, $dp)
{
	// return $this->GetData('/cgi/proc/printfmreport?'.($flg == 1 ? 1:3).'&'.ReversDateStrPar($ds).'&'.ReversDateStrPar($dp).'&0&0');
}

function FMNumReport($flg, $ds, $dp)
{
	// return $this->GetData('/cgi/proc/printfmreport?'.($flg == 1 ? 2:4).'&2015-01-01&2015-01-01&'.$ds.'&'.$dp);
}

function InOutCash($flg, $cash)
{
}

function GetStateKassa(&$k, &$s, &$c, &$karta, &$dt)
{
    $res = $this->getStatusPRRO();
    if ($res === false) return '�� ����������';
    $ret = json_decode($res, true);
    if (isset($res) && ($ret = json_decode($res, true)) !== null) {
        $k='1';
        $s = ($ret['info']['shift_status'] == 0 ? '�������' : '�������');
        // $dt[1] = date('d-m-Y', $ret['time']);
        // $dt[2] = date('H:i:s', $ret['time']);
        $dt[3] = date('d-m-Y');
        $dt[4] = date('H:i:s');   
    }
    return '';
}

function OplCheck($s, $type)
{
    // error_log("$s, $type");
    $xml = simplexml_load_string($s);
     if (!$xml) { $mess = '0;������ � ������������.'; return; }
     $check = '{"F":[';
     foreach($xml->SPECLIST->SPEC as $spec)
     {
       $check .= '{"S":{"code":'.$spec->KOD.',"qty":'.$spec->KOL.',"price":'.$spec->CENA.',"name":"'.$spec->KOD.'.'.$spec->NAME.'","tax":'.$spec->NDS.'}},';
      if($spec->DISC != 0)
       {
//           $check .= '{"D":{"prc":-'.$spec->DISC.'}},';
          $check .= '{"D":{"sum":-'.$spec->SDISC.'}},';
       }
     }
    $check .='{"P":{"no":'.($type != 0 ? 1:4).'}}]}';
    
    return "0;�������� ".print_r($check, true); //'Windows-1251', 'UTF-8').";type - $type";
}


function OpenCheckOut()
{
}

function AddInCheck($code, $kol, $cena)
{
}

function AddDiscount($disc)
{
}

function Oplata($code)
{
}

function CancelCheck()
{
}

function ResetTovary()
{
	// $this->tovlist='';
}

function AddTovar($code, $nds, $name, $cena=0)
{
	// $this->tovlist .= '{"Code":'.$code.',"Name":"'.iconv('cp1251', 'utf-8',$name).'", "Price":'.$cena.', "Dep":1,"Grp":1,"Tax":'.$nds.',"Qty":0,"Flg":0}, ';
}

function SaveTovary()
{

}

function GetECRStatus(&$state)
{
}

function GetCenaMode()
{

}

function GetStatus()
{
    $res = $this->getStatusPRRO();
    if ($res === false) return '�� ����������';
    $ret = json_decode($res, true);
    if (isset($res) && ($ret = json_decode($res, true)) !== null) {
        include_once("../classes/thtmltable.php");
        $table = new Thtmltable();
        $table->SetTableAttr('border', '1');
        $table->SetTableAttr('cellpadding', '0');
        $table->SetTableAttr('cellspacing', '0');
        $table->SetTableAttr('bordercolor', '#B2B2B2');
        $table->SetTableAttr('width', '100%');
        $table->SetTableAttr('style', 'border-collapse: collapse');

        $atr[] = array('align','center');
        $atr[] = array('style','font-size:16');

        $r = $table->AddHeader($atr);
        $table->AddHeaderCol($r,'�');
        $table->AddHeaderCol($r,'��������');
        $table->AddHeaderCol($r,'��������');

        
        $r = $table->AddRow();
        $table->AddCol($r,'1', $atr);
        $table->AddCol($r,'�������� ��', "align=\"left\"");
        $table->AddCol($r,$ret['device'], "align=\"left\"");
        $r = $table->AddRow();
        $table->AddCol($r,'2', $atr);
        $table->AddCol($r,'FiscalID', "align=\"left\"");
        $table->AddCol($r,$ret['info']['fisid'], "align=\"left\"");
        $r = $table->AddRow();
        $table->AddCol($r,'3', $atr);
        $table->AddCol($r,'�����',"align=\"left\"");
        $table->AddCol($r,($ret['info']['shift_status'] == 0 ? '�������' : '�������'),"align=\"left\"");
        if ($ret['info']['shift_dt'] == '') {

        }
        $smena_date = date_create_from_format('YmdHis', $ret['info']['shift_dt']);
        if ($smena_date == false) {
            $smena_date = date_create_from_format('YmdHis', '19700101000000');
        }
        $r = $table->AddRow();
        $table->AddCol($r,'4', $atr);
        $table->AddCol($r,'���� ������� �����',"align=\"left\"");
        $table->AddCol($r,$smena_date->format('d-m-Y'),"align=\"left\"");
        $r = $table->AddRow();
        $table->AddCol($r,'5', $atr);
        $table->AddCol($r,'����� ������ �����',"align=\"left\"");
        $table->AddCol($r,$smena_date->format('H:i:s'),"align=\"left\"");
        return $table->Show();
    }
}

function GetFMStatus()
{
	return '';
}
}