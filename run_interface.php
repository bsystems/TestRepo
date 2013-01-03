<?php
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8'); 

require_once("../connect/remote_connect.php");
require_once("../connect/mssql_connect.php");
require_once("../functions/import_functions.php");


class pri_interface
{
	
    // property declaration
    public $intrface_name = '';
    
	public $main_table_name = '';
    public $main_table_index = '';
    public $main_table_new_index = 0;
	
    public $intrface_data = '';
    public $config   = '';
	private $interface_filename = '';
	private $ts_hash = '';
	public $lines_total = 0;
	public $lines_ok    = 0;
	
	public $timeout = 120;
	
	
	public function get_max_id () {
		
		
		$query = mssql_query("
		SELECT MAX(".$this->main_table_index.")  FROM 
		".$this->main_table_name."
		"); 

		$row = mssql_fetch_array($query, MSSQL_NUM);
		
		return $row[0];
		
		
		
	}
	
	public function is_still_running () {
			// check if the interface is still running 
			// the interface is runing if the .err file does not have the hash 
			
			if (!file_exists($this->interface_filename.".err")) return true;
			
			$err_file = file_get_contents ($this->interface_filename.".err");
			$err_file = iconv("UCS-2LE","UTF-8",$err_file);
			$err_file = explode("\n",$err_file) ;
			$err_file = implode("",$err_file) ;
			if (strstr($err_file,$this->ts_hash)) return false;
			else return true;
	}
	
	
	public function calculate_err_results () {
			
			if (!file_exists($this->interface_filename.".err")) return;
			
			$err_file = file_get_contents ($this->interface_filename.".err");
			$err_file = iconv("UCS-2LE","UTF-8",$err_file);
			
			
			$err_file1 = explode("\n",$err_file) ;
			$this->lines_total=count($err_file1);
			
			
			$err_file = explode("\r\t",$err_file) ;
			$this->lines_ok=count($err_file);
			
			

			
	}
	
	
	
	
	public function Run() {
	
		try {
		
		// GET THE INTERFACE FILENAME FOR DATA STORE AND FOR .ERR FILE 
		

		$query = mssql_query("
		SELECT T\$EXEC FROM  ".$this->config['dbaseprefix']."system.dbo.T\$EXEC
		WHERE ENAME like '$this->intrface_name'
		"); 


		$row = mssql_fetch_array($query, MSSQL_NUM);
		if (!$row[0]>0) throw new Exception('Mimshak Lo Kayam');
		$num=$row[0];
		
		
		$query = mssql_query("
		SELECT  TITLE FROM  ".$this->config['dbaseprefix']."system.dbo.REPTITLE
		WHERE T\$EXEC=$num
		");
		
		
		$row = mssql_fetch_array($query, MSSQL_NUM);
		$this->interface_filename=strrev($row[0]);
		
		
		// if (!file_exists($this->interface_filename))  throw new Exception('Mimshak File Not Found ' .$this->interface_filename );
		
		// DELETE THE .err FILE 
		
		 if (file_exists($this->interface_filename.".err")) {
			
				 if (!unlink ($this->interface_filename.".err") )  throw new Exception('could not delete Mimshak File .err file  ' .$this->interface_filename.".err" );
				 
		 }
		 
		 // GENERATE TIMESTAMP HASH 
		 $this->ts_hash = md5(time());
		 //EDIT THE LAST LINE
		 $lines=explode("\r\n",$this->intrface_data);
		 $lines[count($lines)-2].="\t\t\t\t\t\t\t\t\t\t\t$this->ts_hash";
		 // WRITE THE mimshak file 
		 $lines=implode("\r\n" ,  $lines );

		 $fh = fopen($this->interface_filename, 'w')  ; //or {throw new Exception('could not write Mimshak File .err file  ' .$this->interface_filename );}
		 fwrite($fh,  $lines);
		 fclose($fh);
		
		 /// RUN THE INTERFACE 
		 
	 	 shell_exec ($this->config['tabula_path'].'\bin.95\winrun "" tabula '.$this->config['tabula_password'].' '.$this->config['tabula_path'].'\system\prep '.$this->config['dbasename_noprefix'].' '.$this->config['tabula_path'].'\bin.95\WINACTIV  -I  '.$this->intrface_name);
		
		
		$start_time=time();
		
		while ( time()-$start_time<$this->timeout && $this->is_still_running() ) {
				
				if ($this->main_table_new_index==0) {
						// check if first row is in
						$this->calculate_err_results();
						if ($this->lines_ok >0 ) {$this->main_table_new_index =  $this->get_max_id ();}
						
				}
				
				
				sleep(1);
		
		}
		
		
		$this->calculate_err_results();
       if ($this->lines_ok >0 ) {$this->main_table_new_index =  $this->get_max_id ();}
						
		if (time()-$start_time>=$this->timeout ) {
				
				throw new Exception('Oh No !!! we got a timeout ');
		
		}
		
		

		} catch (Exception $e) {
			
			
			
		echo 'Caught exception: ',  $e->getMessage(), "\n";
		
		
		}
	
	}
	
	
}


$interface1 = new pri_interface();
$interface1->config=$config;
$interface1->intrface_name='BRNS_ORDERS';
$interface1->main_table_name='ORDERS';
$interface1->main_table_index='ORD';

$interface1->intrface_data=
'1	22/10/12	4	ISALE10357	ISALE_ORDER	1		07
2	c:\xampp\order_sign\10357_sign.pdf
5	166	45.000	06/11/12	900.00	0.00
5	164	34.000	06/11/12	930.00	0.00
5	98	1.000	06/11/12	47.00	0.00
5	98	1.000	06/11/12	47.00	0.00
5	98	1.000	06/11/12	47.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
5	166	45.000	06/11/12	900.00	0.00
';

$interface1->Run();

echo "TOTAL -> ".$interface1->lines_total."\r\n";
echo "OK -> ".$interface1->lines_ok."\r\n";
echo "MAXID -> ".$interface1->main_table_new_index."\r\n";

