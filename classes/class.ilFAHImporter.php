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

	const BASE_FILENAME_PREFIX = 'fronter';
	
	const IMPORT_ALL = 1;
	const IMPORT_SELECTED = 2;
	
	const TYPE_CAT = 'cat';
	const TYPE_USR = 'usr';
	const TYPE_CRS = 'crs';
	const TYPE_GRP = 'grp';
	const TYPE_MEM = 'mem';
	
	/**
	 * @var ilLogger
	 */
	private $logger = null;
	
	/**
	 * @var ilFAHImporterSettings
	 */
	private $settings = null;
	
	private $types = [];
	
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
				$this->handleImportForFile($input_file);
			}
			catch(Exception $e) {
				$this->releaseLock();
				throw new ilFAHImportException($e->getMessage());
			}
		}
		
		$this->releaseLock();
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
			if(substr($file->getFilename(), 0, 7) == self::BASE_FILENAME_PREFIX)
			{
				$this->logger->info('File: '. $file->getFilename().' is a valid input file');
				$files[] = $this->settings->getImportDirectory().'/'.$file->getFilename();
			}
		}
		asort($files);
		return $files;
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