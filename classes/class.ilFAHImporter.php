<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHImporter
{
	private static $instance = null;

	const BASE_FILENAME_PREFIX = 'ilias';
	const BASE_FILENAME_PREFIX_LEHR = 'Lehr';
	
	const IMPORT_ALL = 1;
	const IMPORT_SELECTED = 2;
	
	const TYPE_CAT = 'cat';
	const TYPE_USR = 'usr';
	const TYPE_CRS = 'crs';
	const TYPE_GRP = 'grp';
	const TYPE_MEM = 'mem';
	const TYPE_CRS_INFO = 'crsinfo';
	
	/**
	 * @var ilLogger
	 */
	private $logger = null;
	
	/**
	 * @var ilFAHImporterSettings
	 */
	private $settings = null;
	
	private $types = [];
	
	private $doBackup  = false;
	
	/**
	 * singelton constructor
	 */
	private function __construct()
	{
		$this->settings = ilFAHImporterSettings::getInstance();
		$this->logger = ilLoggerFactory::getLogger('fahi');
	}
	
	/**
	 * singleton constructor
	 * @return ilFAHImporter
	 */
	public static function getInstance()
	{
		if(self::$instance) {
			return self::$instance;
		}
		return self::$instance = new self();
	}
	
	public function enableBackup($a_stat)
	{
		$this->doBackup = $a_stat;
	}
	
	/**
	 * Add import type
	 * @param string $a_type
	 */
	public function addType($a_type)
	{
		$this->types[] = $a_type;
	}
	
	/**
	 * Get types
	 */
	public function getTypes()
	{
		return $this->types;
	}

	/**
	 * Import
	 * @throws ilFAHImportException
	 */
	public function import()
	{
		$this->logger->info('Starting fronter import');
		
		// Checking for import lock
		if($this->settings->isLocked())
		{
			throw new ilFAHImportException(ilFAHImporterPlugin::getInstance()->txt('err_import_locked'));
		}

		$this->setLock();
		$input = $this->loadImportFiles();
		
		if(!$input)
		{
			$this->releaseLock();
			throw new ilFAHImportException(ilFAHImporterPlugin::getInstance()->txt('err_import_no_input'));
		}
		
		foreach($input as $input_file)
		{
			try {
				if($this->isCourseInfoFile($input_file))
				{
					$info_importer = new ilFAHCourseInfoComponentImporter();
					$info_importer->import($input_file);
				}
				else
				{
					$this->handleImportForFile($input_file);
				}
			}
			catch(Exception $e) {
				$this->releaseLock();
				throw new ilFAHImportException($e->getMessage());
			}
		}
		
		// move to backup directory
		if($this->doBackup)
		{
			$this->logger->debug('Starting backup of files.');
			$this->moveToBackup($input);
		}
		else
		{
			$this->logger->debug('Backup disabled');
		}
		$this->releaseLock();
	}
	
	
	/**
	 * move file to backup dir 
	 */
	protected function moveToBackup($files)
	{
		foreach((array) $files as $file)
		{
			$this->logger->debug('Files for backup: ' . $file);
			if(!$this->isCourseInfoFile($file))
			{
				$this->logger->info('Moving file from ' . $file .' to ' . $this->settings->getBackupDir().'/'.basename($file));
				rename($file, $this->settings->getBackupDir().'/'.basename($file));
			}
			else
			{
				$this->logger->debug($file.' is a course info file.');
			}
		}
		$this->logger->debug('Backup completed');
		return true;
	}


	/**
	 * Execute type importer
	 * @param type $a_file
	 * @return boolean
	 * @throws ilFAHImportException
	 */
	protected function handleImportForFile($a_file)
	{
		try {
			foreach($this->types as $type)
			{
				switch($type) {
					case self::TYPE_USR:
						$user_importer = new ilFAHUserComponentImporter();
						$user_importer->import($a_file);
						break;
					
					case self::TYPE_CAT:
						$category_importer = new ilFAHCategoryComponentImporter();
						$category_importer->import($a_file);
						break;
					
					case self::TYPE_CRS:
						$course_importer = new ilFAHCourseComponentImporter();
						$course_importer->import($a_file);
						break;

					case self::TYPE_GRP:
						$group_importer = new ilFAHGroupComponentImporter();
						$group_importer->import($a_file);
						break;
					
					case self::TYPE_MEM:
						$membership_importer = new ilFAHMembershipComponentImporter();
						$membership_importer->import($a_file);
						break;
					
					case self::TYPE_CRS_INFO:
						$this->logger->debug('Ignoring course info import for generic xml.');
						break;
				}
			}
			return true;
		} 
		catch (Exception $ex) {
			throw new ilFAHImportException($ex->getMessage());
		}
	}
	
	/**
	 * Find input files
	 * @return array
	 */
	protected function loadImportFiles()
	{
		$files = [];
		foreach(new DirectoryIterator($this->settings->getImportDirectory()) as $file)
		{
			$this->logger->debug('Found file: ' . $file->getFilename());
			if(
				(substr($file->getFilename(), 0, 5) == self::BASE_FILENAME_PREFIX) &&
				(strcmp($file->getExtension(),'xml') === 0)
			)
			{
				if(preg_match('/ilias[0-9]{8}.xml/', $file->getFilename()))
				{
					$this->logger->info('File: '. $file->getFilename().' is a valid input file');
					$files[] = $this->settings->getImportDirectory().'/'.$file->getFilename();
				}
				else
				{
					$this->logger->info('File: '. $file->getFilename().' is a NOT valid input file');
				}
			}
			if(substr($file, 0, 4) == self::BASE_FILENAME_PREFIX_LEHR)
			{
				$this->logger->info('File: '. $file->getFilename().' is a valid course info file');
				$files[] = $this->settings->getImportDirectory().'/'.$file->getFilename();
			}
		}
		asort($files);
		return $files;
	}
	
	/**
	 * Is course info file
	 * @param type $a_file
	 */
	protected function isCourseInfoFile($a_file)
	{
		$basename = basename($a_file);
		if(substr($basename, 0,4) == self::BASE_FILENAME_PREFIX_LEHR)
		{
			return true;
		}
		return false;
	}

	/**
	 * Set import lock
	 */
	protected function setLock()
	{
		$this->logger->info('Setting import lock.');
		$this->settings->enableLock(true);
		$this->settings->update();
	}
	
	/**
	 * Release import lock
	 */
	protected function releaseLock()
	{
		$this->logger->info('Releasing import lock.');
		$this->settings->enableLock(false);
		$this->settings->update();
	}
}
?>