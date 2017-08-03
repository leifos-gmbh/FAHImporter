<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Cron/classes/class.ilCronJob.php";

/**
 * fah import plugin
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 *
 */
class ilFAHImporterCronJob extends ilCronJob
{
	protected $plugin; // [ilCronHookPlugin]
	
	/**
	 * Get id
	 * @return int
	 */
	public function getId()
	{
		return ilFAHImporterPlugin::getInstance()->getId();
	}
	
	public function getTitle()
	{	
		return ilFAHImporterPlugin::PNAME;
	}
	
	public function getDescription()
	{
		return ilFAHImporterPlugin::getInstance()->txt('cron_job_info');
	}
	
	public function getDefaultScheduleType()
	{
		return self::SCHEDULE_TYPE_IN_MINUTES;
	}
	
	public function getDefaultScheduleValue()
	{
		return parent::SCHEDULE_TYPE_IN_HOURS;
	}
	
	public function hasAutoActivation()
	{
		return false;
	}
	
	public function hasFlexibleSchedule()
	{
		return true;
	}
	
	public function hasCustomSettings() 
	{
		return false;
	}
	
	public function run()
	{
		$result = new ilCronJobResult();
		
		$import = ilFAHImporter::getInstance();
		$import->enableBackup(true);
		
		$import->addType(ilFAHImporter::TYPE_USR);
		$import->addType(ilFAHImporter::TYPE_USR);
		$import->addType(ilFAHImporter::TYPE_CAT);
		$import->addType(ilFAHImporter::TYPE_CRS);
		$import->addType(ilFAHImporter::TYPE_GRP);
		$import->addType(ilFAHImporter::TYPE_MEM);
		//$import->addType(ilFAHImporter::TYPE_CRS_INFO);
		try 
		{
			$import->import();
			$result->setStatus(ilCronJobResult::STATUS_OK);
		}
		catch(ilException $e)
		{
			$GLOBALS['DIC']->logger()->fahi()->error('Cron job failed with message:' . $e->getMessage());
			$result->setStatus(ilCronJobResult::STATUS_CRASHED);
			$result->setMessage($e->getMessage());
		}
		return $result;
	}

	/**
	 * get viplab plugin
	 * @return \ilFAHImporterPlugin
	 */
	public function getPlugin()
	{
		return ilFAHImporterPlugin::getInstance();
	}

}

?>