<?php
// ------------------------------------------------------------------------- //
//                          compilehookadmin.php                             //
//                    - XOOPS templates admin module -                       //
//                          GIJOE <http://www.peak.ne.jp/>                   //
// ------------------------------------------------------------------------- //

include_once( '../../../include/cp_header.php' ) ;

include_once "../include/gtickets.php" ;


//
// DEFINITIONS
//

$compile_hooks = array(

	'enclosebycomment' => array(
		'pre' => '<!-- begin tplsadmin %s -->' ,
		'post' => '<!-- end tplsadmin %s -->' ,
		'success_msg' => _TPLSADMIN_FMT_MSG_ENCLOSEBYCOMMENT ,
		'dt' => _TPLSADMIN_DT_ENCLOSEBYCOMMENT ,
		'dd' => _TPLSADMIN_DD_ENCLOSEBYCOMMENT ,
		'conf_msg' => _TPLSADMIN_CNF_ENCLOSEBYCOMMENT ,
		'skip_theme' => true ,
	) ,

	'enclosebybordereddiv' => array(
		'pre' => '<div class="tplsadmin_frame" style="border:1px solid black;word-wrap:break-word;">' ,
		'post' => '<br /><a href="'.XOOPS_URL.'/modules/tplsadmin/admin/mytplsform.php?tpl_file=%1$s" style="color:red;">Edit:<br />%1$s</a></div>' ,
		'success_msg' => _TPLSADMIN_FMT_MSG_ENCLOSEBYBORDEREDDIV ,
		'dt' => _TPLSADMIN_DT_ENCLOSEBYBORDEREDDIV ,
		'dd' => _TPLSADMIN_DD_ENCLOSEBYBORDEREDDIV ,
		'conf_msg' => _TPLSADMIN_CNF_ENCLOSEBYBORDEREDDIV ,
		'skip_theme' => true ,
	) ,

	'hooksavevars' => array(
		'pre' => '<?php include_once "'.XOOPS_ROOT_PATH.'/modules/tplsadmin/include/compilehook.inc.php" ; tplsadmin_save_tplsvars(\'%s\',$this) ; ?>' ,
		'post' => '' ,
		'success_msg' => _TPLSADMIN_FMT_MSG_HOOKSAVEVARS ,
		'dt' => _TPLSADMIN_DT_HOOKSAVEVARS ,
		'dd' => _TPLSADMIN_DD_HOOKSAVEVARS ,
		'conf_msg' => _TPLSADMIN_CNF_HOOKSAVEVARS ,
		'skip_theme' => false ,
	) ,

	'removehooks' => array(
		'pre' => '' ,
		'post' => '' ,
		'success_msg' => _TPLSADMIN_FMT_MSG_REMOVEHOOKS ,
		'dt' => _TPLSADMIN_DT_REMOVEHOOKS ,
		'dd' => _TPLSADMIN_DD_REMOVEHOOKS ,
		'conf_msg' => _TPLSADMIN_CNF_REMOVEHOOKS ,
		'skip_theme' => false ,
	) ,

) ;


//
// EXECUTE STAGE
//

// clearing files in templates_c/
if( ! empty( $_POST['clearcache'] ) || ! empty( $_POST['cleartplsvars'] ) ) {
	// Ticket Check
	if ( ! $xoopsGTicket->check() ) {
		redirect_header(XOOPS_URL.'/',3,$xoopsGTicket->getErrors());
	}

	if( $handler = opendir( XOOPS_COMPILE_PATH . '/' ) ) {
		while( ( $file = readdir( $handler ) ) !== false ) {
	
			if( ! empty( $_POST['clearcache'] ) ) {
				// judging template cache '*.html.php'
				if( substr( $file , -9 ) !== '.html.php' ) continue ;
			} else {
				// judging tplsvars cache 'tplsvars_*'
				if( substr( $file , 0 , 9 ) !== 'tplsvars_' ) continue ;
			}
	
			$file_path = XOOPS_COMPILE_PATH . '/' . $file ;
			@unlink( $file_path ) ;
		}
		redirect_header( 'compilehookadmin.php' , 1 , _TPLSADMIN_MSG_CLEARCACHE ) ;
		exit ;
	} else {
		die( 'XOOPS_COMPILE_PATH cannot be opened' ) ;
	}
}

// append hooking commands
foreach( $compile_hooks as $command => $compile_hook ) {
	if( ! empty( $_POST[$command] ) ) {
		// Ticket Check
		if ( ! $xoopsGTicket->check() ) {
			redirect_header(XOOPS_URL.'/',3,$xoopsGTicket->getErrors());
		}
	
		if( $handler = opendir( XOOPS_COMPILE_PATH . '/' ) ) {
			$file_count = 0 ;
			while( ( $file = readdir( $handler ) ) !== false ) {
		
				// skip /. /.. and hidden files
				if( $file{0} == '.' ) continue ;

				// skip if the extension is not .php
				if( substr( $file , -4 ) != '.php' ) continue ;

				// skip theme.html when comment-mode or div-mode
				if( $compile_hook['skip_theme'] && substr( $file , -15 ) == '%theme.html.php' ) $skip_mode = true ;
				else $skip_mode = false ;

				$file_path = XOOPS_COMPILE_PATH . '/' . $file ;
				$file_bodies = file( $file_path ) ;

				// remove lines inserted by compilehookadmin
				if( strstr( $file_bodies[0] , 'tplsadmin' ) ) {
					array_shift( $file_bodies ) ;
				}
				if( strstr( $file_bodies[count($file_bodies)-1] , 'tplsadmin' ) ) {
					array_pop( $file_bodies ) ;
					$file_bodies[count($file_bodies)-1] = rtrim( $file_bodies[count($file_bodies)-1] ) ;
				}
		
				// get the name of the source template from Smarty's comment
				if( preg_match( '/compiled from (\S+)/' , $file_bodies[1] , $regs ) ) {
					$tpl_name = $regs[1] ;
				} else {
					$tpl_name = '__FILE__' ;
				}
		
				$fw = fopen( $file_path , 'w' ) ;
		
				// insert "pre" command before the compiled cache
				if( $compile_hook['pre'] && ! $skip_mode ) {
					fwrite( $fw , sprintf( $compile_hook['pre'] , htmlspecialchars( $tpl_name , ENT_QUOTES ) ) . "\r\n" ) ;
				}
		
				// rest of template cache
				foreach( $file_bodies as $line ) {
					fwrite( $fw , $line ) ;
				}

				// insert "post" command after the compiled cache
				if( $compile_hook['post'] && ! $skip_mode ) {
					fwrite( $fw , "\r\n" . sprintf( $compile_hook['post'] , htmlspecialchars( $tpl_name , ENT_QUOTES ) ) ) ;
				}
		
				fclose( $fw ) ;
	
				$file_count ++ ;
			}

			if( $file_count > 0 ) {
				redirect_header( 'compilehookadmin.php' , 3 , sprintf( $compile_hook['success_msg'] , $file_count ) ) ;
				exit ;
			} else {
				redirect_header( 'compilehookadmin.php' , 3 , _TPLSADMIN_MSG_CREATECOMPILECACHEFIRST ) ;
				exit ;
			}

		} else {
			die( 'XOOPS_COMPILE_PATH cannot be opened' ) ;
		}
	}
}



//
// EXAMINE STAGE
//

// count template vars & compiled caches
$compiledcache_num = 0 ;
$tplsvars_num = 0 ;
if( $handler = opendir( XOOPS_COMPILE_PATH . '/' ) ) {
	while( ( $file = readdir( $handler ) ) !== false ) {
		if( strncmp( $file , 'tplsvars_' , 9 ) === 0 ) $tplsvars_num ++ ;
		else if( substr( $file , -9 ) === '.html.php' ) $compiledcache_num ++ ;
	}
}

// get tplsets
$sql = "SELECT tplset_name,COUNT(distinct tpl_file) FROM ".$xoopsDB->prefix("tplset")." LEFT JOIN ".$xoopsDB->prefix("tplfile")." ON tplset_name=tpl_tplset GROUP BY tpl_tplset ORDER BY tpl_tplset='default' DESC,tpl_tplset" ;
$srs = $xoopsDB->query($sql);
$tplset_options = "<option value=''>----</option>\n" ;
while( list( $tplset , $tpl_count ) = $xoopsDB->fetchRow( $srs ) ) {
	$tplset4disp = htmlspecialchars( $tplset , ENT_QUOTES ) ;
	$tplset_options .= "<option value='$tplset4disp'>$tplset4disp ($tpl_count)</option>\n" ;
}




//
// FORM STAGE
//

xoops_cp_header() ;
include( './mymenu.php' ) ;

echo "
	<form action='' method='post' class='odd' style='margin: 40px;'>
\n" ;

foreach( $compile_hooks as $command => $compile_hook ) {
	echo "
		<p>
			<dl>
				<dt>
					{$compile_hook['dt']}
					<input type='submit' name='$command' id='$command' value='"._GO."' onclick='return confirm(\"{$compile_hook['conf_msg']}\");' />
				</dt>
				<dd>
					{$compile_hook['dd']}
				</dd>
			</dl>
		</p>
	\n" ;
}

echo "
		<p>
			"._TPLSADMIN_NUMCAP_COMPILEDCACHES.": $compiledcache_num
			<input type='submit' name='clearcache' value='"._DELETE."' onclick='return confirm(\""._TPLSADMIN_CNF_DELETEOK."\");' />

		</p>
		<p>
			"._TPLSADMIN_NUMCAP_TPLSVARS.": $tplsvars_num
			<input type='submit' name='cleartplsvars' value='"._DELETE."' onclick='return confirm(\""._TPLSADMIN_CNF_DELETEOK."\");' />

		</p>
		".$xoopsGTicket->getTicketHtml( __LINE__ )."
	</form>

	<form action='get_tplsvarsinfo.php' method='post' class='odd' style='margin: 40px;' target='_blank'>
		<p>
			<dl>
				<dt>
					"._TPLSADMIN_DT_GETTPLSVARSINFO_DW."
				</dt>
				<dd>
					"._TPLSADMIN_DD_GETTPLSVARSINFO_DW."
					<br />
					<input type='submit' name='as_dw_extension_zip' value='zip' />
					<input type='submit' name='as_dw_extension_tgz' value='tar.gz' />
				</dd>
			</dl>
		</p>
	</form>

	<form action='get_templates.php' method='post' class='odd' style='margin: 40px;' target='_blank'>
		<p>
			<dl>
				<dt>
					"._TPLSADMIN_DT_GETTEMPLATES."
				</dt>
				<dd>
					"._TPLSADMIN_DD_GETTEMPLATES."
					<br />
					<select name='tplset'>$tplset_options</select>
					<input type='submit' name='download_zip' value='zip' />
					<input type='submit' name='download_tgz' value='tar.gz' />
				</dd>
			</dl>
		</p>
	</form>

	<form action='put_templates.php' method='post' enctype='multipart/form-data' class='odd' style='margin: 40px;'>
		<p>
			<dl>
				<dt>
					"._TPLSADMIN_DT_PUTTEMPLATES."
				</dt>
				<dd>
					"._TPLSADMIN_DD_PUTTEMPLATES."
					<br />
					<select name='tplset'>$tplset_options</select>
					<input type='file' name='tplset_archive' size='60' />
					<input type='submit' value='"._SUBMIT."' />
				</dd>
			</dl>
		</p>
	</form>
\n" ;


xoops_cp_footer() ;
?>
