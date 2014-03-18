<?php
/*
Plugin Name: CSV Importer
Plugin URI: URI
Description: Import WooCommerce elements, posts, pages, comments, custom fields, categories, tags and more from a CSV export file.
Author: bi0xid, mecus
Author URI: http://mecus.es/
Version: 0.7
Text Domain: csv-importer
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/


function utf8($fn) { 
      return mb_convert_encoding($fn, 'UTF-8', 
          mb_detect_encoding($fn, 'UTF-8, ISO-8859-1', true)); 
} 

function xls_importer_lite_option_page() { 

	$ruta_archivo = $_POST['file'];
	$ruta_imagen = $_POST['uri'];
	$ruta_archivo_import = $_POST['file_conf'];
	$ruta_imagen_import = $_POST['uri_conf'];

	if ( ( $ruta_archivo != '' ) && ( $ruta_imagen != '' ) ){
	
		$gestor = file($ruta_archivo);
		$n = 0;
	
		foreach ( $gestor as $linea ){
		
			$tratar = utf8($linea);
			list($none, $none, $titulo, $slug, $pvp, $catid, $catnicename, $none, $none, $titseo, $descseo, $seokey, $sku, $contenido, $excerpt, $rebaja, $inicio, $fin) = explode(";", $tratar);

			if ( $n == 0 ) {
						echo "<h3>Importador CVS to WooCommerce</h3>
						Éstas son las correspondencias con tu archivo:<br />
						3 - C => Título : <strong>$titulo</strong><br />
						4 - D => Slug : <strong>$slug</strong><br />
						5 - E => PVP : <strong>$pvp</strong><br />
						6 - F => Categoría (ID) : <strong>$catid</strong><br />
						7 - G => Categoría (nicename) : <strong>$catnicename</strong><br />
						10 - J => Título SEO : <strong>$titseo</strong><br />
						11 - K => Descripción SEO : <strong>$descseo</strong><br />
						12 - L => Palabras claves SEO : <strong>$seokey</strong><br />
						13 - M => SKU : <strong>$sku</strong><br />
						14 - N => Contenido : <strong>$contenido</strong><br />
						15 - O => Excerpt : <strong>$excerpt</strong><br />
						16 - P => Precio de rebajas : <strong>$rebaja</strong><br />
						17 - Q => Inicio rebajas (AAAA-MM-DD) : <strong>$inicio</strong><br />
						18 - R => Fin rebajas (AAAA-MM-DD) : <strong>$fin</strong><br />
						Directorio de imágenes : <strong>$ruta_imagen</strong><br />";

				echo "<br />Si la información superior es correcta, pincha en Continuar y realizaremos la importación (los campos vacíos no se importarán)";
				?>
					<form action="" method="post"
					enctype="multipart/form-data">
					<input type="hidden" name="file_conf" id="file" value="<?php echo $ruta_archivo; ?>">
					<input type="hidden" name="uri_conf" id="uri" value="<?php echo $ruta_imagen; ?>"><br />
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="submit" value="Continuar">

				<?php

			}

			$n++;
		}
	


}


	if ( ( $ruta_archivo_import != '' ) && ( $ruta_imagen_import != '' ) ){
	
		$gestor = file($ruta_archivo_import);
		$n = 0;
		global $current_user;
		get_currentuserinfo();
		$logged_in_user = $current_user->ID;
		$upload_overrides = array( 'test_form' => FALSE );
		
		foreach ( $gestor as $linea ){
			if ( $n > 0 ) {
				$tratar = utf8($linea);
				list($none, $none, $titulo, $slug, $pvp, $catid, $catnicename, $none, $none, $titseo, $descseo, $none, $sku, $contenido, $excerpt, $rebaja, $inicio, $fin) = explode(";", $tratar);
				$inicio = strtotime($inicio .  '12:00:00' );
				$fin = strtotime($fin .  '12:00:00' );
				$titulo = str_replace('""', '"', $titulo);
				$titulo = str_replace('>"', '>', $titulo);
				$titulo = str_replace('"<', '<', $titulo);

				$titseo = str_replace('""', '"', $titseo);
				$titseo = str_replace('>"', '>', $titseo);
				$titseo = str_replace('"<', '<', $titseo);

				$descseo = str_replace('""', '"', $descseo);
				$descseo = str_replace('>"', '>', $descseo);
				$descseo = str_replace('"<', '<', $descseo);

				$contenido = str_replace('""', '"', $contenido);
				$contenido = str_replace('>"', '>', $contenido);
				$contenido = str_replace('"<', '<', $contenido);

				$excerpt = str_replace('""', '"', $excerpt);
				$excerpt = str_replace('>"', '>', $excerpt);
				$excerpt = str_replace('"<', '<', $excerpt);


			$post = array(
			  'comment_status' => 'closed',
			  'ping_status'    => 'closed',
			  'post_content'   => $contenido, 
			  'post_excerpt'   => $excerpt,
			  'post_name'      => $slug,
			  'post_status'    => 'publish',
			  'post_title'     => $titulo,
			  'post_type'      => 'product'
			);  

			$post_id = wp_insert_post( $post, $wp_error );

			global $wpdb;
			$term_taxonomy_id = $wpdb->get_var("SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE term_id = '$catid'");
			$insertar_categoria = $wpdb->query("INSERT INTO wp_term_relationships (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('$post_id', '$term_taxonomy_id', '0')");

			$uploaddir = wp_upload_dir();


			// Filename M
			$filenameM = $ruta_imagen_import.$sku.'M.jpg';
			$filename = $sku.'M.jpg';
			
			$uploadfile = $uploaddir['path'] . '/' . $filename;
			
			$contents= file_get_contents($filenameM);
			$savefile = fopen($uploadfile, 'w');
			fwrite($savefile, $contents);
			fclose($savefile);
			
			$wp_filetype = wp_check_filetype(basename($filename), null );
			
			$attachment = array(
			    'post_mime_type' => $wp_filetype['type'],
			    'post_title' => $filename,
			    'post_content' => '',
			    'post_status' => 'inherit',
  			   'post_author' => $logged_in_user,
  			   'post_parent' => $post_id
			);
			
			$attach_idM = wp_insert_attachment( $attachment, $uploadfile, $post_id );
			
			$imagenew = get_post( $attach_idM );
			$fullsizepath = get_attached_file( $imagenew->ID );
			$attach_data = wp_generate_attachment_metadata( $attach_idM, $fullsizepath );
			wp_update_attachment_metadata( $attach_idM, $attach_data );


			// Filename F
			$filenameF = $ruta_imagen_import.$sku.'F.jpg';
			$filename = $sku.'F.jpg';
			
			$uploadfile = $uploaddir['path'] . '/' . $filename;
			
			$contents= file_get_contents($filenameF);
			$savefile = fopen($uploadfile, 'w');
			fwrite($savefile, $contents);
			fclose($savefile);
			
			$wp_filetype = wp_check_filetype(basename($filename), null );
			
			$attachment = array(
			    'post_mime_type' => $wp_filetype['type'],
			    'post_title' => $filename,
			    'post_content' => '',
			    'post_status' => 'inherit',
  			   'post_author' => $logged_in_user,
  			   'post_parent' => $post_id
			);
			
			$attach_idF = wp_insert_attachment( $attachment, $uploadfile, $post_id );
			
			$imagenew = get_post( $attach_idF );
			$fullsizepath = get_attached_file( $imagenew->ID );
			$attach_data = wp_generate_attachment_metadata( $attach_idF, $fullsizepath );
			wp_update_attachment_metadata( $attach_idF, $attach_data );


			// Lilename L
			$filenameL = $ruta_imagen_import.$sku.'L.jpg';
			$filename = $sku.'L.jpg';
			
			$uploadfile = $uploaddir['path'] . '/' . $filename;
			
			$contents= file_get_contents($filenameL);
			$savefile = fopen($uploadfile, 'w');
			fwrite($savefile, $contents);
			fclose($savefile);
			
			$wp_filetype = wp_check_filetype(basename($filename), null );
			
			$attachment = array(
			    'post_mime_type' => $wp_filetype['type'],
			    'post_title' => $filename,
			    'post_content' => '',
			    'post_status' => 'inherit',
  			   'post_author' => $logged_in_user,
  			   'post_parent' => $post_id
			);
			
			$attach_idL = wp_insert_attachment( $attachment, $uploadfile, $post_id );
			
			$imagenew = get_post( $attach_idL );
			$fullsizepath = get_attached_file( $imagenew->ID );
			$attach_data = wp_generate_attachment_metadata( $attach_idL, $fullsizepath );
			wp_update_attachment_metadata( $attach_idL, $attach_data );

  			
  			update_post_meta( $post_id, "_product_image_gallery","$attach_idL,$attach_idF" );
  			update_post_meta( $post_id, '_regular_price', $pvp );
  			update_post_meta( $post_id, '_sale_price', $rebaja );
  			update_post_meta( $post_id, '_sale_price_dates_from', $inicio );
  			update_post_meta( $post_id, '_sale_price_dates_to', $fin );
  			update_post_meta( $post_id, '_price', $pvp );
  			update_post_meta( $post_id, '_yoast_wpseo_focuskw', $seokey );
  			update_post_meta( $post_id, '_yoast_wpseo_title', $titseo );
  			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $descseo );
  			update_post_meta( $post_id, '_thumbnail_id', $attach_idM );
  			update_post_meta( $post_id, '_sku', $sku );
	
			}
			$n++;
		}

		echo "<div style='font-weight:bold;color:green;border:5px solid green;padding:15px;display:block;text-align:center;font-size:2em;margin: 40px auto;'>Importación finalizada correctamente.</div>";

	}




?>	




<?php if ( ( $ruta_archivo == '' ) || ( $ruta_imagen == '' ) ){ ?>

<div class="wrap" style="width:800px;">
		<big><big>CSV to WooCommerce</big></big>
		<br /><br />

		<form action="" method="post"
		enctype="multipart/form-data">
		<label for="file">Ruta del archivo:</label>
		<input type="text" name="file" id="file"><br />
		<label for="uri">Ruta del directorio de imágenes:</label>
		<input type="text" name="uri" id="uri"><br />
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="submit" value="Comenzar importación">
		</form>

		<br /><br />
		Columnas a importar:<br />
			3 - C => Título<br />
			4 - D => Slug<br />
			5 - E => PVP<br />
			6 - F => Categoría (ID)<br />
			7 - G => Categoría (nicename)<br />
			10 - J => Título SEO<br />
			11 - K => Descripción SEO<br />
			12 - L => Palabras claves SEO<br />
			13 - M => SKU<br />
			14 - N => Contenido<br />
			15 - O => Excerpt<br />
			16 - P => Precio de rebajas<br />
			17 - Q => Inicio rebajas (AAAA-MM-DD)<br />
			18 - R => Fin rebajas (AAAA-MM-DD)<br />
		
	</div>
<?php
}

} // xls_importer_lite_option_page()

function xls_importer_lite_menu() {
	add_options_page( 'Importador CSV', 'Importador CSV', 9, __FILE__, 'xls_importer_lite_option_page' );
} // xls_importer_lite_menu()

function xls_importer_lite() {
	$xls_importer_lite_image = get_option( 'xls_importer_lite_image' );
    
	if(empty($xls_importer_lite_image)) {

		$custom_logo = $custom_image;

	} else {

		$custom_logo = $xls_importer_lite_image;
	}
?>
  
  	<style type="text/css">
		.login h1 a {
			background:url('<?php echo $custom_logo; ?>') no-repeat top center;
			width:326px;
			height:67px;
			text-indent:-9999px;
			overflow:hidden;
			padding-bottom:25px;
			display:block;
		}
	</style>
<?php

} //xls_importer_lite()


function custom_upload_mimes ( $existing_mimes=array() ) {
 
// Add file extension 'extension' with mime type 'mime/type'
$existing_mimes['extension'] = 'mime/type';
 
// add as many as you like e.g. 

$existing_mimes['doc'] = 'application/msword'; 
$existing_mimes['csv'] = 'text/csv'; 

// remove items here if desired ...
 
// and return the new full result
return $existing_mimes;
}

 

add_filter( 'upload_mimes', 'custom_upload_mimes', 1, 1);
add_action( 'admin_menu', 'xls_importer_lite_menu' );
add_action( 'login_form', 'xls_importer_lite' );
