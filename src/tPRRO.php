<?php
require_once 'config.php';

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

    function __construct($param) {
        $this->dm_address = $param['ipaddr'];
        $this->prro_name = $param['prro_name'];
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

    function CheckConfig()
    {
        if(!$this->dm_address) $mess = 'Не указан ip адрес'; {
            return $mess;	
        }
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
            return 'Error:' . $err;
            // return false;
        }
        return $mess;
    }

    function OpenDay()
    {
        // $res=$this->GetData('/cgi/state');
        // if($res === false) return 'Принтер недоступен';
        // $ret=json_decode($res, true);
        // if($ret['IsWrk'] != 0) return; 
        // if($this->SendCmd('/cgi/chk', '{}') === false) return 'Принтер недоступен';
        $this->dm_request_data['fiscal'] = [
            "task"  => 0
        ];
        return $this->SendCmd('', json_encode($this->dm_request_data));
    }

    function CloseDay()
    {
        // $mess = $this->GetStateKassa($kassir, $smena, $KASSACASH, $karta, $dt);
        // $mess .= $this->InOutCash(1, $KASSACASH);
        // if($mess != '' ) return $mess;
        // $mess.=$this->XReport();
        // $mess.=$this->CtrlCheckReport();
        // $mess.=$this->CtrlCheckReport();
        // if(!$this->ZReport())  return false;
        // if($mess != '' ) return $mess;
        $this->dm_request_data['fiscal'] = [
            "task"  => 11
        ];
        return $this->SendCmd('', json_encode($this->dm_request_data));
    }
}