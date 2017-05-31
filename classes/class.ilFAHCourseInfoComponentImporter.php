<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHCourseInfoComponentImporter extends ilFAHComponentImporter
{
	
	/**
	 * Import from file
	 * @param type $a_file
	 * @throws Exception
	 */
	public function import($a_file)
	{
		$this->logger->info('Starting import parsing for course information.');
		
		try {
			$this->init($a_file);
			$this->parseXml();
			$this->terminate();
		} 
		catch (Exception $ex) {
			$this->terminate();
			throw $ex;
		}
	}
	
	/**
	 * Parse xml and update/create categories
	 */
	protected function parseXml()
	{
		$this->logger->debug('Parsing Lehrgänge.xml');
	}
	
	
	
}
?>