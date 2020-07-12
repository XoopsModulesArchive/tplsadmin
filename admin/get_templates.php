<?php
// ------------------------------------------------------------------------- //
//                          get_tplsvarsinfo.php                             //
//                    - XOOPS templates admin module -                       //
//                          GIJOE <http://www.peak.ne.jp/>                   //
// ------------------------------------------------------------------------- //

include_once( '../../../include/cp_header.php' ) ;


if( ! empty( $_POST['download_zip'] ) ) {
	require_once XOOPS_ROOT_PATH.'/class/zipdownloader.php' ;
	$downloader = new XoopsZipDownloader();
	$do_download = true ;
} else if( ! empty( $_POST['download_tgz'] ) ) {
	require_once XOOPS_ROOT_PATH.'/class/tardownloader.php' ;
	$downloader = new XoopsTarDownloader();
	$do_download = true ;
}
if( empty( $do_download ) ) exit ;


$tplset = @$_POST['tplset'] ;
if( ! preg_match( '/^[0-9A-Za-z_-]{1,16}$/' , $tplset ) ) {
	die( _TPLSADMIN_ERR_INVALIDTPLSET ) ;
}
$trs = $xoopsDB->query( "SELECT distinct tpl_file,tpl_source,tpl_lastmodified FROM ".$xoopsDB->prefix("tplfile")." NATURAL LEFT JOIN ".$xoopsDB->prefix("tplsource")." WHERE tpl_tplset='".addslashes($tplset)."' ORDER BY tpl_file" ) ;
if( $xoopsDB->getRowsNum( $trs ) <= 0 ) die( _TPLSADMIN_ERR_INVALIDTPLSET ) ;


while( list( $tpl_file , $tpl_source , $tpl_lastmodified ) = $xoopsDB->fetchRow( $trs ) ) {
	$downloader->addFileData( $tpl_source , $tplset.'/'.$tpl_file , $tpl_lastmodified ) ;
}
echo $downloader->download( 'template_'.$tplset , true ) ;

?>