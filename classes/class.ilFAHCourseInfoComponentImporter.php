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
		foreach($this->root->xpath('//termine') as $termin)
		{
			$this->logger->debug('Handling: ' . $termin['id'] .' ' . $termin['bezeichnung']);
			// import id is stored in "kennzeichen"
			$import_id = $termin->kennziffer['id'];
			$this->logger->info('Importing import_id: ' . $import_id);
			// create course_id
			$import_id = $import_id.'_R';
			
			if(!strlen($import_id))
			{
				$this->logger->notice('Found "termin" without import_id_: ' . $termin['id'] .' ' . $termin['bezeichnung']);
				continue;
			}
			
			$obj_id = $this->lookupObjId($import_id, 'crs');
			$ref_id = $this->lookupReferenceId($obj_id);
			
			if(!$ref_id)
			{
				$this->logger->notice('Cannot find course for: '.$import_id);
				continue;
			}
			
			// delete old entries
			ilFAHCourseInfo::deleteByImportId($ref_id);	
			
			// create new meta data entry
			$info = new ilFAHCourseInfo();
			$info->setImportId($ref_id);
			$info->setKeyword('Zielgruppe');
			$info->setValue((string) $termin->zielgruppe);
			$info->create();
			
			$info = new ilFAHCourseInfo();
			$info->setImportId($ref_id);
			$info->setKeyword('Inhalte');
			$info->setValue((string) $termin->inhalte);
			$info->create();

			$info = new ilFAHCourseInfo();
			$info->setImportId($ref_id);
			$info->setKeyword('Lernziel');
			$info->setValue((string) $termin->lernziel);
			$info->create();
		}
		
	}
	
	
	
}
?>