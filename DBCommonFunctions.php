<?php
    //require_once('kite.php');
   class DBCommonFunctions{

	   //class properties
	   public static $db;
	   public static $statment;
	   public $host;
	   public $db_name;
	   public $password;
	   public $username;

	   //class methods
	   //class contructor
	   public function __construct(){
			//instantiate PDO obj;
			try{
				    $kite = KITE::getInstances('kite');
					$this->host = 'localhost';//$kite->DB->HOST;
					$this->db_name ='saintgebriel';//$kite->DB->DB_NAME;
					$this->username ='root'; //$kite->DB->USERNAME;

					//if(self::$db=null)
					self::$db= new PDO("mysql:host=$this->host;dbname=$this->db_name;charset=utf8",$this->username,$this->password,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>FALSE));
			       // var_dump(self::$db);
			}catch(PDOException $ex){
				echo $ex->getMessage();
			}
          
		}
       //common data base fuctions
	   // a funtion for geting all datas of a given table
		public function getAllData($t_name){
			$sql="SELECT * FROM  {$t_name}" ;
			$result=$this->tbquery($sql);
			return $result;

		}
	   //get any tables fields information
		public function getTableFields($t_name){
			 $sql="show columns from  {$t_name}";
			 return $this->tbquery($sql);
		}
	   //get all tables in a given database
		public function getAllTables($db_Name = null){
			if($db_Name=null) $db_Name=$this->db_name;
			$sql="show tables from ".$this->db_name;


			return  (array)$this->tbquery($sql);
		}
	   //search by a pk or given searching column
    public function findById($tbName,$id){
			$sql="select * from {$tbName} where id='".$id."'"; // "id" is the searching or primary Key searching key

			return  (array)$this->find_Byquery($sql);
		}
	private function tbquery($sql){
			self::$statment=self::$db->query($sql);
		    $row=self::$statment->fetchAll(PDO::FETCH_ASSOC);
			return $row;
		}
	private function instantiate($record){
		  $class_name=get_called_class();
		  $object =new $class_name;
          foreach($record as $attribute=>$value){
			if ($object->has_attrib($attribute))
			{
				$object->$attribute=$value;
			}
		 }
        return $object;
	    }
	private function has_attrib($attribute){
		$object_vars=$this->attributs();
		return array_key_exists($attribute,$object_vars);
	}
	 public  function find_Byquery($sql){
			try{
				self::$statment=self::$db->query($sql);
		        $row=self::$statment->fetchAll(PDO::FETCH_ASSOC);
		        return $row;
			    $object_array=array();
       	        foreach($row as $attribute=>$value){
			   $object_array[]= $this->instantiate($row);
		       }
			return $object_array;

			}catch(PDOException $ex){
				echo $ex->getMessage();
			}

		}
		//a single find output
		public  function fetchOne($sql){
			//find it
			self::$statment=self::$db->query($sql);
		        $row=self::$statment->fetch(PDO::FETCH_ASSOC);
			return	is_array($row)? $row :false;
		}

	    protected function attributs($values){
		//returns an array of attribte key
		 $attributes=array();
		 foreach($values as $fields=>$field) {
		 		if($fields!='tbName' or $fields!='save' or $fields!='cancel')
		 	     {
			        $attributes[$fields]=$field;
		         }


		}
		return $attributes;
	}
    public function getpk($tbName){
        if(trim($tbName)!==''){
            $sql= "SHOW KEYS FROM ".$tbName." WHERE Key_name = 'PRIMARY'";
            return $this->find_Byquery($sql);
        } else return false;
    }
    public function getcmf($tbName){
        if(trim($tbName)!==''){
            $sql="SHOW full columns FROM ".$tbName." where Comment like 's%'";
            return $this->find_Byquery($sql);
        } else return false;
        
    }
// sanitizer function
	public function sanitized_attribtes($values,$opt=false)
	{
		//global $database;
		$clean_attributes=array();
		if($values['tbName']=='basic' and $opt==false){
            {$clean_attributes['id']=$this->generatePK();
               
            }
			//$basket=kite::getinstances('basket');
			$_SESSION['newId']=$clean_attributes['id'];
		}
		foreach($this->attributs($values) as $key=>$value){
			if ($key!="tbName" and $key!="save")
			{
			$clean_attributes[$key]=$value;
			}
		}


		return $clean_attributes;

	}
	private static function check_table($tb_name=""){
		return (!$tb_name="")?true:false;
	}

    public function create($values){
		try {
			if(self::check_table(trim($values['tbName']))){
		          $attibutes=$this->sanitized_attribtes($values);

		//perform the query.
		$sql="INSERT INTO {$values['tbName']}(";
		//$sql.="username, password, first_name, last_name";
		$sql.=join(", ",array_keys($attibutes));
		$sql.=") VALUES('";
		$sql.= join("', '",array_values($attibutes));
		$sql.="')";
       // echo $sql;
        try{
            if (self::$db->exec($sql)){
			//$this->id=$database->insert_id();
			return true;
		    }else{
			return false;
		    }
        }catch(PDOException $ex){
		   echo "Error : ocured".$ex->getMessage();
	    }
		
		
		}
	}catch(PDOException $ex){
		echo "Error : ocured".$ex->getMessage();
	}
	}

    public static function called_class(){
		return get_called_class();
	}
	public function delete($tb_name,$key,$value)
	{


		$sql="DELETE FROM ".$tb_name;
		$sql.=" WHERE ".$key."='{$value}'";
		if (self::$db->exec($sql))
		{
			//$this->id=$database->insert_id();
			return true;
		}
		else{
			return false;
		}
	}//end of delete fuction.
	 
	//update
	public function update($data,$pks){
		global $database;
		if(self::check_table($data['tbName']))
		{
		//perform the query.
		$attibutes=$this->sanitized_attribtes($data,true);
		$attribte_pair=array();
		foreach($attibutes as $key=>$value)
		{
			$attribte_pair[]="{$key}='{$value}'";
		}
            
        //echo "UPDATE ".$data['tbName']." SET ";
		$sql="UPDATE ".$data['tbName']." SET ";
		$sql.= join(", ",$attribte_pair);
		$sql.=" WHERE ".$pks['pk']."='".$pks['pkvalue']."'";
         
		//sself::$db->query($sql);
            try{
                if (self::$db->exec($sql)){
                     echo 'saved';
                    return true;
                    
                } 
                else {
                    echo 'not saved';
                   return false; 
                    
                }
            }
           catch(PDOException $ex){
		       echo "Error : ocured".$ex->getMessage();
               return false;
	       }
		 
		}
	}
    
     public function generatePK(){
       
       $sql="select id from generalpk order by timestamp DESC limit 1";
       $result=$this->find_Byquery($sql);
       $id=(int)$result['0']['id']+1;
      
       $sql="insert into generalpk(id) value({$id})";
         if(self::$db->exec($sql))  return 'SGSS-'.$id;
         else return null;
   }

   }//end of class
  
  // $database= new DBCommonFunctions();
?>
