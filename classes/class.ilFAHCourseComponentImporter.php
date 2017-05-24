<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilFAHCourseComponentImporter extends ilFAHComponentImporter
{
	
	/**
	 * Import from file
	 * @param type $a_file
	 * @throws Exception
	 */
	public function import($a_file)
	{
		$this->logger->info('Starting import parsing for courses');
		
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
		foreach($this->root->group as $group_element)
		{
			$this->logger->debug('Handling group: ' . (string) $group_element->description->short);
			if(!$this->isCourse((string) $group_element->sourcedid->id))
			{
				$this->logger->debug('Ignoring ' . (string) $group_element->description->short .' for course import.');
				continue;
			}
			
			
			$course_info = [];
			$course_info['title'] = (string) $group_element->description->short;
			$course_info['parent_id'] = (string) $group_element->relationship->sourcedid->id;
			$course_info['id'] = (string) $group_element->sourcedid->id;
			
			try {
				$this->refreshCourse($course_info);
			}
			catch(Exception $e) {
				$this->logger->error('Course structure update failed with message: ' . $e->getMessage());
				$this->logger->dump($course_info, ilLogLevel::ERROR);
				throw $e;
			}
		}
	}
	
	/**
	 * Check if object is category
	 * @param type $a_title
	 */
	protected function isCourse($a_id)
	{
		if(preg_match('/_TN$/', $a_id))
		{
			$this->logger->debug($a_id .' seems to be a group');
			return false;
		}
		if(preg_match('/_DOZ$/', $a_id))
		{
			$this->logger->debug($a_id .' seems to be a group');
			return false;
		}
		if(preg_match('/[0-9]{2}\.[0-9]{3}/', $a_id))
		{
			$this->logger->debug($a_id . ' matches course pattern.');
			return true;
		}
		return false;
	}
	
	/**
	 * refresh category
	 */
	protected function refreshCourse($crs_info)
	{
		include_once './Services/Xml/classes/class.ilXmlWriter.php';
		$writer = new ilXmlWriter();
		$writer->xmlStartTag(
			'Course', 
			array(
				'importId' => $crs_info['id']
			)
		);
		
		
		$obj_id = $this->lookupObjId($crs_info['id'], 'crs');
		if($obj_id)
		{
			$do_create = false;
		}
		else
		{
			$do_create = true;
		}

		// Meta data
		$writer->xmlStartTag('MetaData');
		$writer->xmlStartTag(
			'General',
			array(
				'Structure' => 'Hierarchical'
			)
		);
		$writer->xmlElement(
			'Title',
			array(
				'Language' => 'de'
			),
			$crs_info['title']
		);
		$writer->xmlEndTag('General');
		$writer->xmlEndTag('MetaData');
		$writer->xmlEndTag('Course');
		
		if($do_create)
		{
			$this->logger->info('Calling create for: '. $writer->xmlDumpMem(false));
			
			$parent_id = $this->lookupParentId($crs_info['parent_id'], 'cat');
			$this->soap->call(
					'addCourse',
					array(
						$this->soap_session,
						$parent_id,
						$writer->xmlDumpMem(false)
					)
			);
			
		}
		else
		{
			$this->logger->info('Calling update for: '. $writer->xmlDumpMem(false));

			$ref_id = $this->lookupReferenceId($obj_id);
			if(!$ref_id)
			{
				$this->logger->error('Cannot find reference id for object_id: ' . $obj_id);
				return false;
			}
			$this->soap->call(
					'updateCourse',
					array(
						$this->soap_session,
						$ref_id,
						$writer->xmlDumpMem(false)
					)
			);
			
		}
	}
}
?>