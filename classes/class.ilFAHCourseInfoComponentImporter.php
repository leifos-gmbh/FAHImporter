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
		include_once './Services/Object/classes/class.ilObjectFactory.php';
		$object_factory = new ilObjectFactory();
		
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
				$this->logger->notice('Cannot find reference for: '.$import_id);
				continue;
			}
			
			// delete old entries
			ilFAHCourseInfo::deleteByImportId($ref_id);

			$course = $object_factory->getInstanceByRefId($ref_id);
			if(!$course instanceof ilObjCourse)
			{
				$this->logger->warning('Cannot create course instance for: ' . $import_id);
				continue;
			}
			// update description
			if(strlen((string) $termin['bezeichnung']))
			{
				$course->setDescription((string) $termin['bezeichnung']);
				$course->update();
			}
			
			
			$info_string = [];
			foreach($termin->terminteil as $single_appointment)
			{
				// date of appointment
				$date_begin = (string) $single_appointment->beginndatum;
				$date_arr = explode('.', $date_begin);

				$dt['year'] = $date_arr[2];
				$dt['mon'] = $date_arr[1];
				$dt['mday'] = $date_arr[0];

				$date_begin = (string) $single_appointment->beginnuhrzeit;
				$date_arr = explode(':', $date_begin);

				$dt['hours'] = $date_arr[0];
				$dt['minutes'] = $date_arr[1];

				$begin = new ilDateTime($dt, IL_CAL_FKT_GETDATE);

				// date of appointment
				$date_begin = (string) $single_appointment->endedatum;
				$date_arr = explode('.', $date_begin);

				$dt['year'] = $date_arr[2];
				$dt['mon'] = $date_arr[1];
				$dt['mday'] = $date_arr[0];

				$date_begin = (string) $single_appointment->endeuhrzeit;
				$date_arr = explode(':', $date_begin);

				$dt['hours'] = $date_arr[0];
				$dt['minutes'] = $date_arr[1];

				$end = new ilDateTime($dt, IL_CAL_FKT_GETDATE);

				ilDatePresentation::setUseRelativeDates(false);
				$dt_string = ilDatePresentation::formatPeriod($begin, $end);
				
				if(strlen($dt_string))
				{
					$info_string[] = $dt_string;
				}
				
				$location = [];
				$location[] = (string) $single_appointment->firma;
				$location[] = (string) $single_appointment->strasse;
				$location[] = (string) $single_appointment->ort['plz'] .' '. (string) $single_appointment->ort;
				$location[] = (string) $single_appointment->firmaweb;

				$has_location = false;
				foreach($location as $entry)
				{
					if(strlen($entry))
					{
						$has_location = true;
						$info_string[] = $entry;
					}
				}
				if($has_location)
				{
					$info_string[] = '<br />';
				}
			}
			
			if(count((array) $info_string))
			{
				$info = new ilFAHCourseInfo();
				$info->setImportId($ref_id);
				$info->setKeyword('Seminartermine');
				$info->setValue(implode('<br />', (array) $info_string));
				$info->create();
			}

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