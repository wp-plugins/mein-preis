<?php
/*
Plugin Name:  Mein Preis
Plugin URI:   http://www.mein-preis.net/site/wordpress-plugin
Version:      1.0.6
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
	private $productInfos;
	
	public function __construct() {
		wp_register_style( 'meinPreisStylesheet', plugins_url( 'css/meinpreis.css', __FILE__ ) );
		wp_enqueue_style( 'meinPreisStylesheet' );
		add_filter('the_content', array(&$this, 'changeContent'), 7); // Posts
	}
	
	public function changeContent($content) {
		$content = $this->findAndReplace("/\[meinpreis[:]([a-z0-9]+)\]/i", $content);
		$content = $this->findAndReplace("/\[meinpreis[:]([^\]]+)\]/i", $content);
		return $content;
	}
	
	private function findAndReplace($pattern, $content) {
		preg_match_all($pattern, $content, $result);
		if (isset($result[1])) {
			foreach ($result[1] as $k=>$asin) {
				if (!preg_match('/^[a-z0-9]+$/i', $asin)) {
					$asin = $this->parseAsin($asin);
				}
				if ($asin) {
					$content = $this->addPriceHistory($content, $result[0][$k], $asin);
				}
			}
		}
		return $content;
	}
	
	private function parseAsin($uri) {
		$patterns = array(
			"/dp\/([a-z0-9]+)/i",
			"/product\/([0-9a-z]+)/i",
			"/gp\/aw\/d\/([0-9a-z]+)/i" // mobile url
		);
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $uri, $result)) {
				return $result[1];
			}
		}
		return false;
	}
	
	private function addPriceHistory($content, $key, $asin) {
		if (!isset($this->productInfos[$asin])) {
			$this->productInfos[$asin] = $this->fetchProductInfo($asin);
		}
		return str_replace($key, $this->getProductContent($asin), $content);
	}
	
	private function fetchProductInfo($asin) {
		$url = "http://www.mein-preis.net/api/productInfo.php?id=$asin";
		return json_decode(file_get_contents($url));
	}

	public function getProductContent($asin) {
		$data = $this->productInfos[$asin];
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
