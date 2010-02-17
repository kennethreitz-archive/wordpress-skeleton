<?php
	$max_size = 128*2048; // 256k	
	$directory_list = array();
	
	if ( current_user_can( 'upload_files' ) ) {
		$upload_dir = compat_get_upload_dir() . '/wptouch/custom-icons';
		$dir_paths = explode( '/', $upload_dir );
		$dir = '';
		foreach ( $dir_paths as $path ) {
			$dir = $dir . "/" . $path;
			if ( !file_exists( $dir ) ) {
				@mkdir( $dir, 0755 ); 
			}			
		}
		
		if ( isset( $_FILES['submitted_file'] ) ) {
			$f = $_FILES['submitted_file'];
			if ( $f['size'] <= $max_size) {
				if ( $f['type'] == 'image/png' || $f['type'] == 'image/jpeg' || $f['type'] == 'image/gif' || $f['type'] == 'image/x-png' || $f['type'] == 'image/pjpeg' ) {	
					@move_uploaded_file( $f['tmp_name'], $upload_dir . "/" . $f['name'] );
					
					if ( !file_exists( $upload_dir . "/" . $f['name'] ) ) {
						echo __('<p style="color:red">There seems to have been an error.<p>Please try your upload again.</p>');
					} else {
						echo  __( '<p style="color:green">File has been saved!</p>');					
						echo '<p><strong>';			
						echo sprintf(__( "%sClick here to refresh the page%s and see your icon.", "wptouch" ), '<a style="text-decoration:underline" href="#" onclick="location.reload(true); return false;">','</a>');
						echo '</p></strong>';					
					}					
				} else {
					echo __( '<p style="color:orange">Sorry, only PNG, GIF and JPG images are supported.</p>', 'wptouch' );
				}
			} else echo __( '<p style="color:orange">Image too large. try something like 59x60.</p>', 'wptouch' );
		}
	} else echo __( '<p style="color:orange">Insufficient priviledges.</p><p>You need to either be an admin or have more control over your server.</p>', 'wptouch' );
?>