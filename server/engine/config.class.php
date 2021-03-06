<?php
class Config{
  public function __construct(){
    //--------- Error
    $this->show_error = true;
    //--------- banco de dados
    $this->db_host = "localhost";
    $this->db_user = "root";
    $this->db_password = "";
    $this->db_name = "cubeblack";
    //------------
  }
	public function auto(){
		global $db,$config,$user;
		$sql = file_get_contents("engine/database.sql");
		$retorno = $db->query($sql);
		if($db->errorInfo()[1] != null){
			if($config->show_error){
				return "Mysql Erro ".$db->errorInfo()[1] . ":". $db->errorInfo()[2];
			}
			return "Fail!";
		}
		return "Ok!";

	}
	public function help(){
		return <<<'EOT'
>> class Config(config)
.show_error - Bool - caso verdadeiro, os erros são exibidos
.db_host
.db_user
.db_password
.db_name

EOT;
	}
}
