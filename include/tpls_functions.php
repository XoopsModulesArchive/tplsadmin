<?php

include_once XOOPS_ROOT_PATH.'/class/template.php';

function tplsadmin_import_data( $tplset , $tpl_file , $tpl_source , $lastmodified = 0 )
{
	$db =& Database::getInstance() ;

	// check the file is valid template
	list( $count ) = $db->fetchRow( $db->query( "SELECT COUNT(*) FROM ".$db->prefix("tplfile")." WHERE tpl_tplset='default' AND tpl_file='".addslashes($tpl_file)."'" ) ) ;
	if( ! $count ) return false ;

	// check the template exists in the tplset
	if( $tplset != 'default' ) {
		list( $count ) = $db->fetchRow( $db->query( "SELECT COUNT(*) FROM ".$db->prefix("tplfile")." WHERE tpl_tplset='".addslashes($tplset)."' AND tpl_file='".addslashes($tpl_file)."'" ) ) ;
		if( $count <= 0 ) {
			// copy from 'default' to the tplset
			$result = $db->query( "SELECT * FROM ".$db->prefix("tplfile")." WHERE tpl_tplset='default' AND tpl_file='".addslashes($tpl_file)."'" ) ;
			while( $row = $db->fetchArray( $result ) ) {

				$db->queryF( "INSERT INTO ".$db->prefix("tplfile")." SET tpl_refid='".addslashes($row['tpl_refid'])."',tpl_module='".addslashes($row['tpl_module'])."',tpl_tplset='".addslashes($tplset)."',tpl_file='".addslashes($tpl_file)."',tpl_desc='".addslashes($row['tpl_desc'])."',tpl_type='".addslashes($row['tpl_type'])."'" ) ;
				$tpl_id = $db->getInsertId() ;
				$db->queryF( "INSERT INTO ".$db->prefix("tplsource")." SET tpl_id='$tpl_id', tpl_source=''" ) ;
			}
		}
	}

	// UPDATE just tpl_lastmodified and tpl_source
	$drs = $db->query( "SELECT tpl_id FROM ".$db->prefix("tplfile")." WHERE tpl_tplset='".addslashes($tplset)."' AND tpl_file='".addslashes($tpl_file)."'" ) ;
	while( list( $tpl_id ) = $db->fetchRow( $drs ) ) {
		$db->queryF( "UPDATE ".$db->prefix("tplfile")." SET tpl_lastmodified='".addslashes($lastmodified)."',tpl_lastimported=UNIX_TIMESTAMP() WHERE tpl_id='$tpl_id'" ) ;
		$db->queryF( "UPDATE ".$db->prefix("tplsource")." SET tpl_source='".addslashes($tpl_source)."' WHERE tpl_id='$tpl_id'" ) ;
		xoops_template_touch( $tpl_id ) ;
	}

	return true ;
}

?>