<?php

class ktPDO
{
	private $PDO = NULL;
	
	private $log_active = FALSE;
	private $log_file = 'query.log';
	
	private static $instance = NULL;
	private static $sql_type = 'mysql';
	private static $sql_host = 'localhost';
	private static $sql_base = 'default';
	private static $sql_user = 'root';
	private static $sql_password = '';
	
	// constructeur privé
	private function __construct()
	{
		$this->PDO = new PDO(self::$sql_type . ':host=' . self::$sql_host . ';dbname=' . self::$sql_base, self::$sql_user, self::$sql_password);
	}
	
	// fonction statique d'instanciation
	public static function get()
	{
		if (is_null(self::$instance))
			self::$instance = new self;
			
		return self::$instance;
	}
	
	// défini le type d'accès SQL
	public static function setType($type = '')
	{
		self::$sql_type = $type;
	}
	
	// défini l'hôte SQL
	public static function setHost($host = '')
	{
		self::$sql_host = $host;
	}
	
	// défini le nom de la base
	public static function setBase($base = '')
	{
		self::$sql_base = $base;
	}
	
	// défini l'utilisateur
	public static function setUser($user = '')
	{
		self::$sql_user = $user;
	}
	
	// défini le mot de passe
	public static function setPassword($password = '')
	{
		self::$sql_password = $password;
	}
	
	// empeche le clonage
	public function __clone()
	{
		trigger_error('This instance of PDO use a Singleton', E_USER_ERROR);
	}
	
	public function setLog($state)
	{
		if ($state == TRUE)
			$this->log_active = TRUE;
		else
			$this->log_active = FALSE;
	}
	
	private function log($string)
	{
		$content = '';
		
		if (file_exists($this->log_file))
			$content = file_get_contents($this->log_file);
		
		$string = date('d\.m\.Y - H\:i') . ' || ' . $string;
		$content = $string . "\n" . $content;
		
		file_put_contents($this->log_file, $content);
	}
	
	// methode magique PDO
	public function __call($method, $arguments)
	{
		if ($this->log_active)
			$this->log($method .' @ '. $arguments[0]);

		return $this->PDO->$method($arguments[0]);
	}
}