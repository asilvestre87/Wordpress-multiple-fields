<?php

class Colores_Producto_Meta_Box {
	/**
	 * Parámetros del metabox
	 */
	private $idMetaBox 		= 'colores_producto';	//identificador del metabox
	private $titleMetaBox 	= 'Colores producto';	//título del metabox
	private $domain 		= 'farmalastic';		//dominio del metabox (idiomas)
	private $screens 		= 	array('producto'); 	//donde queremos mostrar

	/**
	 * Campos del metabox
	 */
	private $fields = array(
		array(
			'id' => 'imagen-color',
			'label' => 'Imagen color',
			'type' => 'media',
		),
	);

	/**
	 * Método constructor de la clase. Añade acciones a sus repsectivos hooks en Wordpress
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
	}

	/**
	 * A través del hook add_meta_boxes definimos la función 
	 * para añadir el meta box en los "screens" (donde hayamos indicado que queremos mostrar)
	 */
	public function add_meta_boxes() {
		foreach ( $this->screens as $screen ) {
			add_meta_box(
				$this->idMetaBox,
				__( $this->titleMetaBox, $this->domain ),
				array( $this, 'add_meta_box_callback' ),
				$screen,
				'advanced',
				'default'
			);
		}
	}

	/**
	 * Generamos el HTML para el meta box
	 * 
	 * @param object $post WordPress post object
	 */
	public function add_meta_box_callback( $post ) {
		wp_nonce_field( $this->idMetaBox.'_data', $this->idMetaBox.'_nonce' );
		$this->generate_fields( $post );
	}

	/**
	 * A través del hook admin_footer.
	 * Añadimos scripts que permiten la subida de medias
	 */
	public function admin_footer() {
		?>
		<script type="text/javascript">
			// https://codestag.com/how-to-use-wordpress-3-5-media-uploader-in-theme-options/
			jQuery(document).ready(function($){

				//set function to UPLOAD button
				if ( typeof wp.media !== 'undefined' ) {
					var _custom_media = true,
					_orig_send_attachment = wp.media.editor.send.attachment;
					$('.rational-metabox-media').click(function(e) {
						var send_attachment_bkp = wp.media.editor.send.attachment;
						var button = $(this);
						var id = button.attr('id').replace('_button', '');
						_custom_media = true;
							wp.media.editor.send.attachment = function(props, attachment){
							if ( _custom_media ) {
								$("#"+id).val(attachment.url);
							} else {
								return _orig_send_attachment.apply( this, [props, attachment] );
							};
						}
						wp.media.editor.open(button);
						return false;
					});
					$('.add_media').on('click', function(){
						_custom_media = false;
					});
				}

				//add row button
				$('#add-row_<?=$this->idMetaBox?>').on('click', function(e) {
					e.preventDefault();
					var row = $('.empty-row.screen-reader-text.<?=$this->idMetaBox?>').clone(true);
					var id = parseInt(row.attr('data-id'));			
					row.removeClass('empty-row screen-reader-text');
					row.insertBefore('#repeatable-fieldset-one_<?=$this->idMetaBox?> tbody>tr:last');
					//text inputs
					var elements = row.find('input[type=text]');
					elements.each(function(index, el) {
						var idElement =	el.id;
						el.id = idElement+id;
					});
					//buttons
					var elements = row.find('input[type=button]');
					elements.each(function(index, el) {
						var idElement = el.id.replace('_button', '');
						el.id = idElement+id+'_button';
					});
					var newId = id+1;
					$('.empty-row.screen-reader-text.<?=$this->idMetaBox?>').attr('data-id', newId);
					return false;
				});

				//set the fieldset sortable
				$('#repeatable-fieldset-one_<?=$this->idMetaBox?> tbody').sortable({
					opacity: 0.6,
					revert: true,
					cursor: 'move',
					handle: '.sort'
				});

				//remove button
				$('.remove-row_<?=$this->idMetaBox?>').on('click', function() {
					$(this).parents('tr').remove();
					return false;
				});
			});
		</script>
	<?php
	}

	/**
	 * Generamos los campos del meta box en HTML 
	 */
	public function generate_fields( $post ) {
		$datos = get_post_meta($post->ID, $this->idMetaBox, true);
		wp_nonce_field( 'repeatable_meta_box_nonce-'.$this->idMetaBox, 'repeatable_meta_box_nonce-'.$this->idMetaBox );
		?>
		<table id="repeatable-fieldset-one_<?=$this->idMetaBox?>" width="100%">
		<?php
		$i = 0;
		//rellenamos con los datos que tenemos guardados
		if(!empty($datos)){
			foreach($datos as $dato){	
				$output = '<tr>';
				$output .= '<td>
						<span class="sort hndle" style="float:left;">
							<img src="'.get_template_directory_uri().'/img/move.png" />&nbsp;&nbsp;&nbsp;</span>
							<a class="button remove-row_'.$this->idMetaBox.'" href="#">-</a>
					</td>';
				foreach($this->fields as $field){				
					switch ( $field['type'] ) {
						case 'media':
							$output .= sprintf(
								'<td>
									<input class="regular-text" id="%s'.$i.'" name="%s[]" type="text" value="%s">
									<input class="button rational-metabox-media" id="%s'.$i.'_button" name="%s_button" type="button" value="Upload" />
								</td>',
								$field['id'],
								$field['id'],
								$dato[$field['id']],
								$field['id'],
								$field['id']
							);
							break;

						default:
							$output .= sprintf(
								'<td>
									<input %s id="%s'.$i.'" name="%s[]" type="%s" value="%s">
								</td>',
								$field['type'] !== 'color' ? 'class="regular-text"' : '',
								$field['id'],
								$field['id'],
								$field['type'],
								$dato[$field['id']]
							);
							break;
					}				
				}		
				$i++;		
				$output .= '</tr>';			
				echo $output;
			}
			
		//si no tenemos datos mostramos los campos vacios
		}else{
			
			$input = '<tr>';
			$input = '<td>
						<span class="sort hndle" style="float:left;">
							<img src="'.get_template_directory_uri().'/img/move.png" />&nbsp;&nbsp;&nbsp;</span>
							<a class="button remove-row_'.$this->idMetaBox.'" href="#">-</a>
					</td>';
			foreach($this->fields as $field){				
				switch ( $field['type'] ) {
					case 'media':
						$input .= sprintf(
							'<td>
								<input class="regular-text" id="%s'.$i.'" name="%s[]" type="text" value="">
								<input class="button rational-metabox-media" id="%s'.$i.'_button" name="%s_button" type="button" value="Upload" />
							</td>',
							$field['id'],
							$field['id'],
							$field['id'],
							$field['id']
						);
						break;

					default:
						$input .= sprintf(
							'<td>
								<input %s id="%s'.$i.'" name="%s[]" type="%s" value="">
							</td>',
							$field['type'] !== 'color' ? 'class="regular-text"' : '',
							$field['id'],
							$field['id'],
							$field['type']
						);
						break;
				}					
				
			}
			$input .= '</tr>';			
			echo $input;	
			$i++;	
		}
		?>
		<tr class="empty-row screen-reader-text <?=$this->idMetaBox?>" data-id="<?=$i?>">
		<?php
			$input = '<td>
						<span class="sort hndle" style="float:left;">
							<img src="'.get_template_directory_uri().'/img/move.png" />&nbsp;&nbsp;&nbsp;</span>
							<a class="button remove-row_'.$this->idMetaBox.'" href="#">-</a>
					</td>';
			foreach($this->fields as $field){
				switch ( $field['type'] ) {
					case 'media':
						$input .= sprintf(
							'<td>
								<input class="regular-text" id="%s" name="%s[]" type="text" value="">
								<input class="button rational-metabox-media" id="%s_button" name="%s_button" type="button" value="Upload" />
							</td>',
							$field['id'],
							$field['id'],
							$field['id'],
							$field['id']
						);
						break;

					default:
						$input .= sprintf(
							'<td>
								<input %s id="%s" name="%s[]" type="%s" value="">
							</td>',
							$field['type'] !== 'color' ? 'class="regular-text"' : '',
							$field['id'],
							$field['id'],
							$field['type']
						);
						break;
				}		
			}
			echo $input;
		?>
		</tr>
		</table>
		<p><a id="add-row_<?=$this->idMetaBox?>" class="button" href="#">+</a></p>
		<?php
	}

	function save_post($post_id) {
		if ( !isset( $_POST['repeatable_meta_box_nonce-'.$this->idMetaBox] ) ||
			!wp_verify_nonce( $_POST['repeatable_meta_box_nonce-'.$this->idMetaBox], 'repeatable_meta_box_nonce-'.$this->idMetaBox ) )
			return;
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;
		if (!current_user_can('edit_post', $post_id))
			return;
		$old = get_post_meta($post_id, $this->idMetaBox, true);
		$new = array();
		//creamos distintos arrays llamados $field['id'] con los valores obtenidos por POST
		foreach($this->fields as $field){
			$x = $field['id']; //definimos array con nombre $field['id']
			$$x = $_POST[$field['id']]; //asignamos valores obtenidos por POST
		}
		//obtenemos el número de filas que tenemos
		$count = count(${$this->fields[0]['id']});
		//asignamos al array $new todos los datos obtenidos
		for ( $i = 0; $i < $count; $i++ ) {
			$vacio = true;
			foreach($this->fields as $field){
				if(!empty(${$field['id']}[$i]))
					$vacio = false;
				$new[$i][$field['id']] = stripslashes(strip_tags(${$field['id']}[$i]));
			}
			if($vacio)
				unset($new[$i]);
		}
		if ( !empty( $new ) && $new != $old )
			update_post_meta( $post_id, $this->idMetaBox, $new );
		elseif ( empty($new) && $old )
			delete_post_meta( $post_id, $this->idMetaBox, $old );
	}

}
new Colores_Producto_Meta_Box;
?>