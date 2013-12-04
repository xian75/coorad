<?php
class DB {
	private $dbHost;
	private $dbUsername;
	private $dbPassword; 
	private $dbName;
	private $dbPort;
	private $dbSocket;
	public $conn;

	public function __construct($dbHost, $dbUsername, $dbPassword, $dbName, $dbPort, $dbSocket){
		$this->dbHost = $dbHost;
		$this->dbUsername = $dbUsername;
		$this->dbPassword = $dbPassword;
		$this->dbName = $dbName;
		$this->dbPort = $dbPort;
		$this->dbSocket = $dbSocket;
	}
	
	public function connect() {
		$this->conn = new mysqli($this->dbHost, $this->dbUsername, $this->dbPassword, $this->dbName, $this->dbPort, $this->dbSocket) or die (mysqli_error());
	}
	
	public function getConnectionId() {
		return $this->conn->thread_id;
	}

	public function execute($sql) {
		$response = false;
		if ($this->conn) {
			if ($this->conn->connect_errno) {
				echo "Failed to connect to MySQL: (" . $db->conn->connect_errno . ") " . $db->conn->connect_error;
			}
			$response = $this->conn->query($sql);
		}
		return $response;
	}

	public function getInsertedId() {
		return $this->conn->insert_id;
	}
}
?>