<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * General settings for fah importer
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHImporterSettings
{
	/**
	 * @var ilFAHImporterSettings
	 */
	private static $instance = null;
	
	/**
	 * @var ilLogger
	 */
	protected $logger = null;
	
	/**
	 * @var ilSetting
	 */
	protected $storage = null;
	
	private $level = 100;
	private $lock = false;
	private $user = '';
	private $pass = '';
	private $cron_interval = 5;
	private $cron_last_execution = 0;
	private $import_directory = '';
	
	
	/**
	 * Constructor
	 * @global type $DIC
	 */
	protected function __construct()
	{
		global $DIC;
		
		$this->logger = $DIC->logger()->fahi();
		
		include_once './Services/Administration/classes/class.ilSetting.php';
		$this->storage = new ilSetting('fahi');
		
		$this->read();
	}
	
	/**
	 * @return ilFAHImporterSettings
	 */
	public static function getInstance()
	{
		if(self::$instance) {
			return self::$instance;
		}
		return self::$instance = new self();
	}
	
	/**
	 * @return ilSetting
	 */
	protected function getStorage()
	{
		return $this->storage;
	}
	
	/**
	 * Get log level
	 * @return int
	 */
	public function getLogLevel()
	{
		return $this->level;
	}
	
	/**
	 * Set log level
	 * @param int level
	 */
	public function setLogLevel($a_level)
	{
		$this->level = $a_level;
	}
	
	public function enableLock($a_lock)
	{
		$this->lock = $a_lock;
	}
	
	public function isLocked()
	{
		return $this->lock;
	}
	
	public function setSoapUser($a_user)
	{
		$this->user = $a_user;
	}
	
	public function getSoapUser()
	{
		return $this->user;
	}
	
	public function setSoapPass($a_pass)
	{
		$this->pass = $a_pass;
	}
	
	public function getSoapPass()
	{
		return $this->pass;
	}
	
	public function setCronInterval($a_int)
	{
		$this->cron_interval = $a_int;
	}
	
	public function getCronInterval()
	{
		return $this->cron_interval;
	}
	
	public function getLastCronExecution()
	{
		return $this->cron_last_execution;
	}
	
	
	
	
	public function setImportDirectory($a_mp)
	{
		$this->import_directory = $a_mp;
	}	
	
	public function getImportDirectory()
	{
		return $this->import_directory;
	}
	
	public function setBackupDir($a_dir)
	{
		$this->backup_dir = $a_dir;
	}
	
	public function getBackupDir()
	{
		return $this->backup_dir;
	}
	
	
	public function updateLastCronExecution()
	{
		$this->getStorage()->set('cron_last_execution',time());
	}
	

	/**
	 * Update settings
	 */
	public function update()
	{
		$this->getStorage()->set('log_level', $this->getLogLevel());
		$this->getStorage()->set('lock',(int) $this->isLocked());
		$this->getStorage()->set('import_directory', $this->getImportDirectory());
		$this->getStorage()->set('backup_dir',$this->getBackupDir());
		$this->getStorage()->set('soap_user',$this->getSoapUser());
		$this->getStorage()->set('soap_pass',$this->getSoapPass());
		$this->getStorage()->set('cron_interval',$this->getCronInterval());
		
	}
	
	/**
	 * Read settings
	 */
	protected function read()
	{
		$this->setLogLevel($this->getStorage()->get('log_level', $this->level));
		$this->enableLock($this->getStorage()->get('lock',$this->isLocked()));
		$this->setImportDirectory($this->getStorage()->get('import_directory', $this->import_directory));
		$this->setBackupDir($this->getStorage()->get('backup_dir',$this->getBackupDir()));
		$this->setSoapUser($this->getStorage()->get('soap_user', $this->getSoapUser()));
		$this->setSoapPass($this->getStorage()->get('soap_pass', $this->getSoapPass()));
		$this->setCronInterval($this->getStorage()->get('cron_interval',$this->getCronInterval()));
		$this->cron_last_execution = $this->getStorage()->get('cron_last_execution',0);
		
	}
}
?>