<#1>
<?php

if(!$ilDB->tableExists('cron_crnhk_fahi_map'))
{
	$ilDB->createTable('cron_crnhk_fahi_map',
		array(
			'mapping_id'	=>	
				array(
					'type'		=> 'integer',
					'length'	=> 4,
					'default'	=> 0,
					'notnull'	=> true
				),
			'prefix'	=>
				array(
					'type'		=> 'text',
					'length'	=> 64,
					'notnull'	=> false
				),
			'template'	=>
				array(
					'type'		=> 'integer',
					'length'	=> 4,
					'notnull'	=> true
				)
		)
	);
	$ilDB->addPrimaryKey('cron_crnhk_fahi_map', array('mapping_id'));
	$ilDB->createSequence('cron_crnhk_fahi_map');
}
?>
<#2>
<?php
if(!$ilDB->tableExists('cron_crnhk_fahi_info'))
{
	$ilDB->createTable('cron_crnhk_fahi_info',
		array(
			'info_id'	=>	
				array(
					'type'		=> 'integer',
					'length'	=> 4,
					'default'	=> 0,
					'notnull'	=> true
				),
			'obj_id'	=>
				array(
					'type'		=> 'integer',
					'length'	=> 4,
					'notnull'	=> true
				),
			'keyword'	=>
				array(
					'type'		=> 'text',
					'length'	=> 64,
					'notnull'	=> false
				),
			'value'	=>
				array(
					'type'		=> 'text',
					'length'	=> 255,
					'notnull'	=> false
				)

		)
	);
	$ilDB->addPrimaryKey('cron_crnhk_fahi_map', array('mapping_id'));
	$ilDB->createSequence('cron_crnhk_fahi_map');
}
?>