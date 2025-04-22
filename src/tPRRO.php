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

function getMoneyInfo($money_arr) {
    $cash = $card = 0;
    foreach ($money_arr as $money) {
        switch ($money['type']) {
            case 0:
                $cash = $money['sum_p'] - $money['sum_m'];
                break;
            case 2:
                $card = $money['sum_p'] - $money['sum_m'];
                break;
        }
    }
    return [
        'cash'  => $cash,
        'card'  => $card
    ];
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
    var $cash = 0.0;
    var $card = 0.0;
    var $dm_unavailable_msg = 'DM ����������';
    var $dm_isoffline = false;
    var $dm_dtype = 0;
    var $dm_date = '';
    var $dm_time = '';
    var $dm_fisID = '';
    var $check_is_fiscal = true;
    var $dm_error_res = 0;
    var $dm_errortxt = '';
    var $tovlist;
    // var $dm_x_report_close_code = 1092;

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
            "source"    => "LAB_API",
            "device"    => $this->prro_name,
            "type"      => 1,
            "need_pf_img"   => 1, //exclude
            "need_pf_pdf"   => 1, //exclude
            "need_pf_txt"   => 1, //exclude
            "need_pf_doccmd"   => 1, //exclude
            "fiscal"    => []
        ];
        $this->ready = true;
    }

    function getTagID() {
        return sha1($this->dm_fisID) . date('YmdHis');
    }

    function checkEndOfPayDate($billing_arr) {
        $bill_date = date_create_from_format('YmdHis', $billing_arr['paid_date_to']);
        $msg = '';
        if ($bill_date == false) {
            $msg .= ' �����! ���� ��������� ������ �� ��� �� �����������.'; 
        } elseif ($bill_date->getTimestamp() <= time()) {
            $msg .= ' �����! ���� ��������� ������ �� ��� �����������.'; 
        }
        if ($msg != '') {
            return $msg;
        }
    }

    function getStatusPRRO($res_json) {
        $dm_dt = date_create_from_format('YmdHis', $res_json['dt']);
        if ($dm_dt != false) {
            $this->dm_date = $dm_dt->format('d-m-Y');
            $this->dm_time = $dm_dt->format('H:i:s');
        } else {
            $this->dm_date = '';
            $this->dm_time = '';
        }
        $this->dm_fisID = $res_json['info']['fisid'];
        $this->dm_isoffline = $res_json['info']['isoffline'];
        $this->dm_dtype = $res_json['info']['dtype'];
        // $this->cash = $res_json['info']['safe'];
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
        return 'Unknown error';
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
        $msg = '';
        $raw_json = $this->SendCmd('', json_encode($this->dm_request_data));
        if (($msg = $this->get_ErrorTxt($raw_json)) === '' && ($res_json = json_decode($raw_json, true)) !== null) {
            $this->cash = $res_json['info']['safe'];
            $this->getStatusPRRO($res_json);
            if ($this->dm_dtype == 1 && $this->check_is_fiscal) {
                return $this->checkEndOfPayDate($res_json['info']['billing']);
            }
            return;
        }
        return $msg;
    }

    function CloseDay()
    {
        $mess = $this->GetStateKassa($kassir, $smena, $KASSACASH, $karta, $dt);
        $mess .= $this->InOutCash(1, $KASSACASH);
        if ($mess != '' ) return $mess;
        // $mess .= $this->XReport();
        // $mess.=$this->CtrlCheckReport();
        // $mess.=$this->CtrlCheckReport();
        if (!$this->ZReport()) return false;
        // if ($mess != '' ) return $mess;
        // if ($mess != '' ) return 'qwerty';
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
        return $this->getErrorTxt($this->SendCmd('', json_encode($this->dm_request_data)), null);
    }

    function checkStatusPRRO()
    {
        $this->dm_request_data['fiscal'] = [
            "task"  => 18,
        ];
        $res = $this->SendCmd('', json_encode($this->dm_request_data));
        // $this->getErrorTxt($this->SendCmd('', json_encode($this->dm_request_data)), '');
        if ($res === false) {
            $this->dm_error_res = 1;
            $this->dm_errortxt = $this->dm_unavailable_msg;
        } elseif (($errortxt = $this->getErrorTxt($res, '')) != '') {
            $this->dm_error_res = 1;
            $this->dm_errortxt = $errortxt;
        } else {
            $this->dm_error_res = 0;
            $this->dm_errortxt = '';
            if (isset($raw_json) && ($res_json = json_decode($raw_json, true)) !== null) {
                $this->getStatusPRRO($res_json);
                if ($this->dm_dtype == 1 && $this->check_is_fiscal) {
                    return $this->checkEndOfPayDate($res_json['info']['billing']);
                }
            }
            return $res;
        }
        return false;
        // if (isset($raw_json) && ($res_json = json_decode($raw_json, true)) !== null) {
        //     if (isset($res_json['res']) && $res_json['res'] != 0) {
        //         return recursiveConvertEncoding($res_json['errortxt']);
        //     }


        // return $this->SendCmd('', json_encode($this->dm_request_data));
 
        // if ($this->dm_dtype == 1 && $this->check_is_fiscal) {
        //     return $this->checkEndOfPayDate($res_json['info']['billing']);
        // }
    }

function Z3Report()
{
}

    function get_ErrorTxt($raw_json, $retSuccess = '') {
        if (isset($raw_json) && ($res_json = json_decode($raw_json, true)) !== null) {
            $this->dm_error_res = isset($res_json['res']) ? $res_json['res'] : 1;
            if ($this->dm_error_res == 0) {
                $this->dm_errortxt = '';
            } elseif (isset($res_json['errortxt'])) {
                $this->dm_errortxt = recursiveConvertEncoding($res_json['errortxt']);
            } else {
                $this->dm_errortxt = '������� �������� � errortxt. ��� �������: ' . $this->dm_error_res;
            }
        } else {
            $this->dm_errortxt = $this->dm_unavailable_msg;
        }
        return $this->dm_errortxt;
    }

    function XReport()
    {
        $this->dm_request_data['need_pf_pdf'] = 1; //exclude'
        $this->dm_request_data['fiscal'] = [
            "task"  => 10,
            "cashier"   => ""
        ];
        $msg = '';
        $raw_json = $this->SendCmd('', json_encode($this->dm_request_data));
        if (($msg = $this->get_ErrorTxt($raw_json)) === '' && ($res_json = json_decode($raw_json, true)) !== null) {
            $this->cash = 0.0;
            $this->card = 0.0;
            $pay_cash = $pay_card = 0.0;
            $money_cash = $money_card = 0.0;
            if (isset($res_json['info']['pays'])) {
                $money_res = getMoneyInfo($res_json['info']['pays']);
                $pay_cash = $money_res['cash'];
                $pay_card = $money_res['card'];
            }
            if (isset($res_json['info']['money'])) {
                $money_res = getMoneyInfo($res_json['info']['money']);
                $money_cash = $money_res['cash'];
                $money_card = $money_res['card'];
            }
            //  
            // $this->cash = $pay_cash + $money_cash;
            $this->cash = $res_json['info']['safe'];
            $this->card = $pay_card + $money_card;
            $this->getStatusPRRO($res_json);
            if ($this->dm_dtype == 1 && $this->check_is_fiscal) {
                return $this->checkEndOfPayDate($res_json['info']['billing']);
            }
        }
        return $msg;
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
        // $res=$this->SendCmd('/cgi/chk', '{"IO":[{"IO":{"sum":'.($flg == 0 ? $cash : -$cash).'}}]}');
        // if($res === false) return '������� ����������';
        // $ret=json_decode($res, true);
        // if(array_key_exists('err', $ret))
        // {
        //     return '������ ��������: '.print_r($res, true); //.iconv('utf8', 'cp1251', $ret['err']['e']);
        // }	
        $this->dm_request_data['fiscal'] = [
            "task"  => ($flg == 0 ? 3 : 4),
            "cash"  => [
                "type"  => 0,
                "sum"   => $cash
            ]
        ];
        return $this->getErrorTxt($this->SendCmd('', json_encode($this->dm_request_data)), null);
    }

function GetStateKassa(&$k, &$s, &$c, &$karta, &$dt)
{
    $msg = '';
    $res = $this->checkStatusPRRO();
    if ($res === false) return $this->dm_errortxt;
    $ret = json_decode($res, true);
    if (isset($res) && ($ret = json_decode($res, true)) !== null) {
        $k = '1';
        $s = ($ret['info']['shift_status'] == 0 ? '�������' : '�������');
        $dt[3] = date('d-m-Y');
        $dt[4] = date('H:i:s');
        if ($ret['info']['shift_status'] !== 0) {
            $msg = $this->XReport();
            if ($msg !== '') return $msg;
            $c = $this->cash;
            $karta = $this->card;
            $dt[1] = $this->dm_date;
            $dt[2] = $this->dm_time;
        }
    }
    return '';
}

function OplCheck($s, $type)
{
    // error_log("$s, $type");
    $xml = simplexml_load_string($s);
    if (!$xml) { $mess = '0;������ � ������������.'; return; }
    $total_sum = 0.0;
    $total_disc = 0.0;
    $check_items = [];
    $type_pay = ($type != 0 ? 0: 2);
    $check = '{"F":[';
    foreach($xml->SPECLIST->SPEC as $spec)
    {
        $check .= '{"S":{"code":'.$spec->KOD.',"qty":'.$spec->KOL.',"price":'.$spec->CENA.',"name":"'.$spec->KOD.'.'.$spec->NAME.'","tax":'.$spec->NDS.'}},';
        $disc = 0;
        if ($spec->SDISC != 0)
        {
            $disc = $spec->SDISC + 0;
        }
       $check_items[] = [
        "code"  => $spec->KOD . "",
        "name"  => $spec->KOD . '.' . $spec->NAME,
        "cnt"   => $spec->KOL + 0,
        "price" => $spec->CENA + 0,
        "disc"  => $disc,
        "disc_type" => 0,
        "cost"  => 0.0,
        "taxgrp"    => 1
       ];
       $total_sum += $spec->CENA * $spec->KOL;
       $total_disc += $disc;
       if($spec->DISC != 0)
       {
          $check .= '{"D":{"sum":-'.$spec->SDISC.'}},';
       }
     }
    $check .='{"P":{"no":'.($type != 0 ? 1:4).'}}]}';
    
    $this->dm_request_data['fiscal'] = [
        "task"  => 1,
        "cashier"   => "",
        "receipt"   => [
            "sum"   => $total_sum - $total_disc,
            "disc"  => 0,
            "disc_type" => 0,
            "rows"  => $check_items,
            "pays"  => [
                [
                    "type"  =>  $type_pay,
                    "sum"   => $total_sum - $total_disc,
                    "comment"   => mb_convert_encoding('�������� �� ������ ������', 'UTF-8','Windows-1251')
                ]
            ]
        ]
    ];
    // return "0;��������: ".$check;
    // return "0;��������: ".json_encode($this->dm_request_data); //'Windows-1251', 'UTF-8').";type - $type";
    // return "0;��������: ".$this->SendCmd('', json_encode($this->dm_request_data));
    $res_opl = $this->getErrorTxt($this->SendCmd('', json_encode($this->dm_request_data)), true);
    if ($res_opl !== true) {
        return '0;������ ��������: '.print_r($res_opl, true).'<br>'.print_r(recursiveConvertEncoding($this->dm_request_data), true);
    }
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
	$this->tovlist='';
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
    // $res = $this->checkStatusPRRO();
    // if ($res === false) return $this->dm_unavailable_msg;
    // $ret = json_decode($res, true);
    $res = $this->checkStatusPRRO();
    if ($res === false) return $this->dm_errortxt;
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
	return '��� ����������';
}
}