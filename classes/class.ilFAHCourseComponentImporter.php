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
			$this->logger->debug('Handling course: ' . (string) $group_element->description->short);
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
		if(preg_match('/[0-9]{2}\.[0-9]{3}\/[0-9]{3}\/[0-9]{4}_R/', $a_id))
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
		$writer->xmlStartTag('Settings');
		$writer->xmlStartTag('Availability');
		$writer->xmlElement('Unlimited');
		$writer->xmlEndTag('Availability');
		$writer->xmlEndTag('Settings');
		$writer->xmlEndTag('Course');
		
		if($do_create)
		{
			$parent_id = $this->lookupParentId($crs_info['parent_id'], 'cat');
			if(!$parent_id)
			{
				$this->logger->notice($crs_info['parent_id'].' is not imported. Ignoring course update.');
				return false;
			}
			
			$new_ref_id = $this->copyTemplateCourse($crs_info, $parent_id);
			if($new_ref_id)
			{
				// update course data
				$this->soap->call(
						'updateCourse',
						array(
							$this->soap_session,
							$new_ref_id,
							$writer->xmlDumpMem(false)
						)
				);
				return true;
			}
			// create default course by xml
			$this->logger->info('Calling create for: '. $writer->xmlDumpMem(false));
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
	
	/**
	 * Copy template course
	 * @param type $crs_info
	 * @param type $parent_id
	 * throws ilSaxParserException
	 */
	protected function copyTemplateCourse($crs_info, $parent_id)
	{
		$template_id = ilFAHMappings::getInstance()->getTemplateForTitle($crs_info['title']);
		if(!$template_id)
		{
			$this->logger->info('No mapping found for crs title: ' . $crs_info['title'].', using default course template');
			$template_id = $this->settings->getDefaultCourse();
		}
		if(!$template_id)
		{
			$this->logger->fatal('No default course template found');
			return false;
		}
		
		
		$this->logger->info('Found mapping template course '. $template_id.' for title: ' . $crs_info['title']);
		
		$copy_writer = new ilXmlWriter();
		$copy_writer->xmlStartTag(
			'Settings', 
			array(
				'source_id' => $template_id,
				'target_id' => $parent_id,
				'default_action' => 'COPY'
			)
		);
		
		$node_data = $GLOBALS['DIC']->repositoryTree()->getNodeData($template_id);
		foreach($GLOBALS['DIC']->repositoryTree()->getSubTree($node_data,false) as $node)
		{
			$copy_writer->xmlElement(
				'Option',
				array(
					'id' => $node,
					'action' => 'COPY'
				)
			);
		}
		
		$copy_writer->xmlEndTag('Settings');
		$this->logger->dump($copy_writer->xmlDumpMem(true));
		
		include_once './webservice/soap/classes/class.ilCopyWizardSettingsXMLParser.php';
		$xml_parser = new ilCopyWizardSettingsXMLParser($copy_writer->xmlDumpMem(false));
		try {
			$xml_parser->startParsing();
		} 
		catch (ilSaxParserException $se)
		{
			$this->logger->error($se->getMessage());
			throw $se;
		}

		$options = $xml_parser->getOptions();
		
		$this->logger->dump($options);
		
		$source_object = ilObjectFactory::getInstanceByRefId($template_id);
		if($source_object instanceof ilContainer) 
		{
			$session_id = $GLOBALS['ilAuthSession']->getId();
			$client_id = IL_CLIENT_ID;
			
			// call container clone
			$ret = $source_object->cloneAllObject(
				$session_id,
				$client_id,
				$source_object->getType(),
				$parent_id,
				$template_id,
				$options, 
				false
			);
			
			
			
			if(is_array($ret))
			{
				return $ret['ref_id'];
			}
		}
		return 0;
	}
}
?>