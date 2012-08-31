<?php
/*
Plugin Name:  Mein Preis
Plugin URI:   http://www.mein-preis.net/sites/mein-preis-wordpress-plugin/
Version:      1.0.2
Description:  Die Mein Preis Erweiterung ermöglicht das Einbinden des Preisverlaufs eines Amazon Artikels. 
Author:       Sascha Nordquist
Author URI:   http://www.sn7.eu/
*/

/*  Copyright 2012  Sascha Nordquist  (email : sascha@sn7.eu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class MeinPreis {
	public function __construct() {
		wp_register_style( 'meinPreisStylesheet', plugins_url( 'css/meinpreis.css', __FILE__ ) );
		wp_enqueue_style( 'meinPreisStylesheet' );
		add_filter('the_content', array(&$this, 'changeContent'), 7); // Posts
	}
	
	public function changeContent($content) {
		preg_match_all("/\[meinpreis[:]([A-Za-z0-9]+)\]/", $content, $result);
		$productInfos = array();
		if (isset($result[1])) {
			foreach ($result[1] as $k=>$asin) {
				if (!isset($productInfos[$asin])) {
					$productInfos[$asin] = $this->fetchProductInfo($asin);
				}
				$content = str_replace($result[0][$k], $this->getProductContent($asin, $productInfos[$asin]), $content);
			}
		}
		return $content;
	}
	
	public function fetchProductInfo($asin) {
		$url = "http://www.mein-preis.net/api/productInfo.php?id=$asin";
		return json_decode(file_get_contents($url));
	}
	
	public function addLink() {
		return '';
	}
	
	public function getProductContent($asin, $data) {
		$buyImageSrc = plugins_url('images/buy.png', __FILE__);
		return '<div class="meinpreis">
<a href="'.$data->url.'">'.htmlspecialchars($data->name).'</a><div><iframe src="http://www.mein-preis.net/Graph/'.$asin.'.html?simple" width="100%" frameborder="0" marginheight="0" marginwidth="0" scrolling="no" style="border: 0px; "></iframe></div><div class="meinpreis_buttons">
<a href="'.$data->buyUrl.'"><img class="meinpreis_button" src="'.$buyImageSrc.'" alt="Jetzt bei Amazon kaufen" /></a></div>
</div>';
	}
}

// Start this plugin once all other plugins are fully loaded
add_action('init', 'MeinPreis', 5);
function MeinPreis() {
	global $meinPreis;
	$meinPreis = new MeinPreis();
}
