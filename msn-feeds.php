<?php
/*
Plugin Name: MSN feeds
Description: Creates feeds for MSN network.
Version: 0.3
Author: Domenico Citrangulo
*/


// ########## PRE SETUP
session_start();
if (!defined('MYPLUGIN_PLUGIN_NAME'))
	define('MYPLUGIN_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));
if (!defined('MYPLUGIN_PLUGIN_URL'))
	define('MYPLUGIN_PLUGIN_URL', WP_PLUGIN_URL . '/' . MYPLUGIN_PLUGIN_NAME);


// ########## MAIN CLASS
if (!class_exists('msn_feeds')) {
	class msn_feeds
	{
		

// ########## PLUGIN SETUP AND INSTALLATION
		function msn_feeds()
		{
			$this->version = "0.1";
		}
		function setupActivation()
		{
			//update version number
			if (get_option('msn_feeds_version') != $this->version) {
				update_option('msn_feeds_version', $this->version);
			}
		}

		/*
		Artigos: negocio, lifestyle, carreira, coluna
		Fotos: foto, listas
		Vídeos: videoclip

		define(FEED_ARTIGO,1);
		define(FEED_FOTO,2);
		define(FEED_VIDEO,3);
		*/
		
		const FEED_ARTIGO = 1;
		const FEED_FOTO = 2;
		const FEED_VIDEO = 3;
		
		/**
		 * Irá verificar se o post esta sendo publicado e em caso positivo tentará incluí-lo na lista de RSS's
		 * 
		 * @param int $post_id
		 */
		function save_feed_rss( $post_id ) {
			global $post;
			//echo "save";
			
			//$post_types_accepted = array('feed_rss');
			$post_types_accepted = array('negocio', 'lifestyle', 'carreira', 'coluna', 'foto', 'listas', 'videoclip');
			
			
			//if(isset($post) && $post && in_array($post->post_type, $post_types_accepted)) {
			if(isset($post) && $post) {
				
				//se o feed estiver sendo publicado/removido/atualizado precisamos atualizar o rss desse tipo de feed (Artigo, Foto ou Vídeo)
				$this->update_rss_list($post_id);
			}
		} 
		
		
		/**
		 * Este é o método responsável por realizar a checagem do tipo do conteúdo (post) passado 
		 * e realizar a chamada para (re)gerar a lista de rss
		 * 
		 * @param int
		 */
		function update_rss_list($post_id) {
			global $post;
			//captura o tipo do conteúdo modificado
			//$feed_type = get_field('feed_tipo', $post_id );
			$post_type = $post->post_type;
			$post_categories = wp_get_post_categories($post_id);
		
		//	if (in_array('369',$post_categories) == 1) {
		//		$post_type = "foto";	
		//	} else {
		//		$post_type = "negocio";	
		//	}
		
			if ($post_type == "fotos") { $post_type = "foto"; } else { $post_type = "negocio"; }
			//echo "update";
			$this->create_rss($post_id, $post_type);
		}
		
		
		/**
		 * Este é o método que irá criar ou atualizar a lista de rss de um determinado tipo de post, conforme o parâmetro
		 * 
		 * @param int $post_id
		 * @param int $feed_type
		 */
		function create_rss($post_id, $feed_type) {
			
			//echo "create";
			global $post;
		
			$typeArtigos = array('negocio', 'lifestyle', 'carreira', 'coluna');
			$typeFoto = array('foto', 'listas');
			$typeVideo = array('videoclip');
		
			$groupPostType = array();
		
			if (in_array($feed_type, $typeArtigos)) {
				$feed_type = FEED_ARTIGO;
				$groupPostType = $typeArtigos;
				//echo "art";
			} else if (in_array($feed_type, $typeFoto)) {
				$feed_type = FEED_FOTO;
				$groupPostType = $typeFoto;
				//echo "fot";
			} else {
				$feed_type = FEED_VIDEO;
				$groupPostType = $typeVideo;
				//echo "vid";
			}
			
			//Só atualiza o post se o tipo do post tiver sido informado
			if($feed_type){
				//estes parametros irão fazer com que a busca traga até 100 posts do tipo feed que de uma determinada categoria
				//esta categoria será definida na hora do castro de feed.
				//echo "feedtype";
				$args=array(
						//'post_type' => $groupPostType,
						'post_status' => 'publish',
						'posts_per_page' => 25,
						'orderby'=> 'ID',
						'order'=> 'DESC',
						/*'meta_query' => array(
								array(
										'key' => 'feed_tipo',
										'value' => $feed_type
								)
						),*/
				);
				
				if($feed_type == FEED_ARTIGO){
					$filename = 'articles.xml';
					$feeds_name = 'Articles';
					//echo "art2";
				}else if($feed_type == FEED_FOTO){
					$filename = 'galleries.xml';
					$feeds_name = 'Galleries';
				}else{
					$filename = 'videos.xml';
					$feeds_name = 'Videos';
				}
				
				//
				$posts = get_posts( $args );
				//print_r($posts);
					
				//percorre todos os posts encontrados para criar a estrutura utilizada no RSS
				if($posts) {
					
					//echo "posts";
					
					$itens = '';
					$site_url = get_site_url();
						
					foreach ($posts as $post){
						//captura as informações dos campos personalizados
						//echo "postsloop";
						//$post_thumbnail = get_field('feed_capa',$post->ID);
						//$post_thumbnail = "http://localhost:8888/wp-content/uploads/2014/12/dream.jpg";
						
						//if($post_thumbnail)
						//	$image = $post_thumbnail;
						//else
							$image = "";
						
						if($feed_type == FEED_ARTIGO){
							//echo "art3";
							//echo $post . " - " . $post->post_excerpt . " - " . $image . " - " . $site_url;
							//$bla = $post->post_excerpt;
							$bla = "ionh uighuietgu eht ugrtjeg oiertgib eirt bhrtuphiturh";
							//print_r($bla);
							//$itens .= rss_item_article($post, $post->post_excerpt, $image, null, $site_url);
							//echo "aaa";
							$itens .= $this->rss_item_article($post, $bla, $image, null, $site_url);
							//echo "sss";
						}else if($feed_type == FEED_FOTO){
							//echo "fot3";
							$postGallery = $this->get_post_images_gallery($post);
							$itens .= $this->rss_item_gallery($post, $post->post_excerpt, $postGallery, $site_url);
						}else{
							//echo "vid3";
							$video = get_field('feed_video_link', $post->ID);
							$itens .= $this->rss_item_video($post, $post->post_excerpt, $image, $video, $site_url);
						}
					}
					//echo "mount";
					//após ler todos os feeds e inseri-los na lista de itens
					// é preciso montar todo o xml
					$rss_full = $this->mount_rss_full('Forbes Rss - '.$feeds_name, $site_url, 'Potal de notícias da Forbes Brasil', $itens);
						
					//salva o conteúdo em arquivo
					$this->write_rss_file($filename,$rss_full);
				}
			}
		}
		
		/**
		 * Verifica se um post esta ligado a alguma categoria, caso positivo retorna a primera que encontrar
		 * @param int $post_id
		 * @return string|null
		 */
		function getPostCategorie($post_id){
			$post_categories = wp_get_post_categories( $post_id );
			$cats = array();
				
			if($post_categories){
				foreach($post_categories as $c){
					$cat = get_category( $c );
					
					return  $cat->name;
				}	
			}
			
			return null;
		}
		
		/**
		 * Função que buscará as imagens inseridas na galeria de um post
		 * @param stdClass $post
		 * @return array
		 */
		function get_post_images_gallery($post){
			$gallery = array();
			$post_gallery = get_post_gallery($post->ID, false);
			if(isset($post_gallery['ids']) && $post_gallery['ids']){
				$images_ids = explode(',', $post_gallery['ids']);
				foreach ($images_ids as $imagem_gallery){
					$gallery[] = get_post($imagem_gallery);
				}
			}
			
			return $gallery;
		}
		
		function write_rss_file($name, $content) {
			//abre o arquivo ou cria, caso não exista
			try {
				$fp = fopen(ABSPATH . 'wp-content/uploads/rss-' . $name, "w+");
				
				//escreve o conteúdo no arquivo
				$escreve = fwrite($fp, $content);
				
				//fecha o arquivo
				fclose($fp);
			} catch (Exception $e) {}
		}
		
		
		function mount_rss_full($title, $link, $description, $itens, $language='pt-BR') {
			$rss = '<rss xmlns:dc="http:/purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/" version="2.0">';
			$rss .= "\n\t<channel>";
			$rss .= "\n\t\t<title>$title</title>";
			$rss .= "\n\t\t<language>$language</language>";
			$rss .= "\n\t\t<link>$link</link>";
			$rss .= "\n\t\t<description>$description</description>";
			$rss .= $itens;
			$rss .= "\n\t</channel>";
			$rss .= "\n</rss>";
			
			return $rss;
		}
		
		
		function rss_item_article($post, $abstract, $image, $video, $site_url) {
			//$author = get_field('feed_autor',$post->id);
			$author = "autorzao";
			//echo $author;
			$content_author = ($author) ? $author : get_the_author_meta( 'display_name', $post->post_author);
			$post_category = $this->getPostCategorie($post->ID);
			$complementoUrl = ($post_category) ? '?categoria='.$post_category : '';
			//echo "cat";
			
			$item = "\n\t\t<item>";
			$item .= "\n\t\t\t<guid>".md5($post->id)."</guid>";
			$item .= "\n\t\t\t<title><![CDATA[" . $this->set_max_character($post->post_title) . ']]></title>';
			$item .= "\n\t\t\t<link>" . get_post_permalink($post->ID) . $complementoUrl . '</link>';
			
			//echo "item";
			
			if($image){
				$item .= "\n\t\t\t<media:content url=\"".$image['url']."\">";
				$item .= "\n\t\t\t\t<media:credit><![CDATA[".$image['caption']."]]></media:credit>";
				$item .= "\n\t\t\t\t<media:title><![CDATA[".$image['title']."]]></media:title>";
		// 		if($alt_text)
		// 			$item .= "\n\t\t\t\t<media:description>".$alt_text."</media:description>";
		// 		else
					$item .= "\n\t\t\t\t<media:description><![CDATA[".wpautop($image['description'])."]]></media:description>";
				$item .= "\n\t\t\t</media:content>";
			}
			
			$item .= "\n\t\t\t<description><![CDATA[".wpautop($post->post_content)."]]></description>";
			//echo "abs";
			if($abstract)
				$item .= "\n\t\t\t<dc:abstract><![CDATA[".$this->set_max_character($abstract,650)."]]></dc:abstract>";
			$item .= "\n\t\t\t<author><![CDATA[".$content_author."]]></author>";
			$item .= "\n\t\t\t<lastBuildDate>".date('c',strtotime($post->post_modified))."</lastBuildDate>";
			$item .= "\n\t\t\t<pubDate>".date('c',strtotime($post->post_date))."</pubDate>";
			$item .= "\n\t\t\t<dc:creator><![CDATA[".$content_author."]]></dc:creator>";
			$item .= "\n\t\t\t<guid isPermaLink=\"false\">".md5($post->ID)."</guid>";
			$item .= "\n\t\t</item>";
			//echo "return";
			return $item;
		}
		
		function rss_item_gallery($post, $abstract, $gallery, $site_url) {
			//$author = get_field('feed_autor',$post->id);
			$author = "autorzao";
			$content_author = ($author) ? $author : get_the_author_meta( 'display_name', $post->post_author);
			$post_category = $this->getPostCategorie($post->ID);
			$complementoUrl = ($post_category) ? '?categoria='.$post_category : '';
			
			$item = "\n\t\t<item>";
			$item .= "\n\t\t\t<guid>".md5($post->id)."</guid>";
			$item .= "\n\t\t\t<title><![CDATA[" . set_max_character($post->post_title) . ']]></title>';
			$item .= "\n\t\t\t<description><![CDATA[".$abstract."]]></description>";
			$item .= "\n\t\t\t<link>" . get_post_permalink($post->ID) . $complementoUrl . '</link>';
		
			if($gallery){
				$item .= "\n\t\t\t<media:group>";
				foreach ($gallery as $image){
					$alt_text = get_post_meta($image->ID , '_wp_attachment_image_alt', true);
					if(!$alt_text)
						$alt_text = $image->post_title;
					
					$item .= "\n\t\t\t\t<media:content url=\"".$image->guid."\" type=\"image/jpeg\" description=\"".trataTextoXML($alt_text)."\">";
					$item .= "\n\t\t\t\t\t<media:credit><![CDATA[".$image->post_excerpt."]]></media:credit>";
					$item .= "\n\t\t\t\t\t<media:description><![CDATA[".wpautop($image->post_content)."]]></media:description>";
					$item .= "\n\t\t\t\t\t<media:title><![CDATA[".$image->post_title."]]></media:title>";
					$item .= "\n\t\t\t\t</media:content>";
				}
				
				$item .= "\n\t\t\t</media:group>";
			}
			
			$item .= "\n\t\t\t<author><![CDATA[".$content_author."]]></author>";
			$item .= "\n\t\t\t<lastBuildDate>".date('c',strtotime($post->post_modified))."</lastBuildDate>";
			$item .= "\n\t\t\t<pubDate>".date('c',strtotime($post->post_date))."</pubDate>";
			$item .= "\n\t\t</item>";
			return $item;
		}
		
		function rss_item_video($post, $abstract, $image, $video, $site_url) {
			$author = get_field('feed_autor',$post->id);
			$content_author = ($author) ? $author : get_the_author_meta( 'display_name', $post->post_author);
			
			$item = "\n\t\t<item>";
			$item .= "\n\t\t\t<guid>".md5($post->id)."</guid>";
			$item .= "\n\t\t\t<guid isPermaLink=\"false\">".md5($post->ID)."</guid>";
			$item .= "\n\t\t\t<title><![CDATA[" . set_max_character($post->post_title) . ']]></title>';
			if($post->post_excerpt)
				$item .= "\n\t\t\t<description><![CDATA[".wpautop($post->post_excerpt)."]]></description>";
			else
				$item .= "\n\t\t\t<description><![CDATA[".wpautop($post->post_content)."]]></description>";
			$item .= "\n\t\t\t<author><![CDATA[".$content_author."]]></author>";
		
			if($video){
				
				$video_inf = wp_get_attachment_metadata($video['id']);
				$item .= "\n\t\t\t<media:content bitrate=\"".$video_inf['bitrate']."\"  medium=\"video\" duration=\"".$video_inf['length_formatted']."\" expression=\"full\" fileSize=\"".$video_inf['filesize']."\" type=\"".$video_inf['mime_type']."\" height=\"".$video_inf['height']."\" url=\"".$video['url']."\" width=\"".$video_inf['width']."\">";
				if($image){
					$imagem_info = wp_get_attachment_metadata($image['id']);
					$path_upload = wp_upload_dir('basedir');
					if(is_file($path_upload['basedir'] . '/' . $imagem_info['file'])){
						$file_size = filesize($path_upload['basedir'] . '/' . $imagem_info['file']); 
					}else{
						$file_size = '';
					}
					
					$item .= "\n\t\t\t\t<media:thumbnail expression=\"full\" fileSize=\"".$file_size."\" type=\"".$image['mime_type']."\" height=\"".$image['height']."\" isDefault=\"true\" url=\"".$image['url']."\" width=\"".$image['width']."\" copyright=\"".trataTextoXML($image['caption'])."\"></media:thumbnail>";
				}
				$item .= "\n\t\t\t\t<media:copyright><![CDATA[".$video['caption']."]]></media:copyright>";
				
				$item .= "\n\t\t\t</media:content>";
			}
		
			$item .= "\n\t\t\t<pubDate>".date('c',strtotime($post->post_date))."</pubDate>";
			$item .= "\n\t\t</item>";
		
			return $item;
		}
		
		function set_max_character($texto,$max=150) {
			$qtde = strlen(html_entity_decode($texto));
			$n = 0;
			$i = $max;
		
			if ($qtde > $max)
			{
				$palavras = explode(' ', $texto);
				foreach ($palavras as $Palavra)
				{
					$tamanho = strlen(html_entity_decode($Palavra));
					$n = $n + $tamanho + 1;
					if($n >= $max)
					{
						$i = strpos($texto, $Palavra, ($n - $tamanho - 1));
						break;
					}
				}
				$texto = substr($texto, 0, $i).'...';
			}
			return $texto;
		}
		
		function trataTextoXML($texto){
			$formatado = str_replace('&', '&amp;', $texto);
			$formatado = str_replace('<', '&lt;', $formatado);
			$formatado = str_replace('>', '&gt;', $formatado);
			$formatado = str_replace('"', '&quot;', $formatado);
			$formatado = str_replace('\'', '&apos;', $formatado);
			
			return $formatado;
		}

	}
}


// ########## INITIALIZE
$msn_feeds = new msn_feeds();


// ########## HOOKS
//add_filter('save_post', 'save_feed_rss');
add_filter('save_post', array(
	&$msn_feeds,
	'save_feed_rss'
));

		


?>