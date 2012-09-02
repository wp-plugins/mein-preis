<?php
/*
Plugin Name:  Mein Preis
Plugin URI:   http://www.mein-preis.net/site/wordpress-plugin
Version:      1.0.9
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
		$content = $this->findAndReplace("/\[meinpreis[:]([^\]]+)\]/i", $content, array($this, 'addPriceHistory'));
		$content = $this->findAndReplace("/\[productInfo[:]([^\]]+)\]/i", $content, array($this, 'addProductInfo'));
		return $content;
	}
	
	private function findAndReplace($pattern, $content, $func) {
		preg_match_all($pattern, $content, $result);
		if (isset($result[1])) {
			foreach ($result[1] as $k=>$asin) {
				if (!preg_match('/^[a-z0-9]+$/i', $asin)) {
					$asin = $this->parseAsin($asin);
				}
				if ($asin) {
					$productInfo = $this->getProductInfo($asin);
					$replaceStr = call_user_func_array($func, array($asin, $productInfo));
					$content = str_replace($result[0][$k], $replaceStr, $content);
				}
			}
		}
		return $content;
	}
	
	private function getProductInfo($asin) {
		if (!isset($this->productInfos[$asin])) {
			$this->productInfos[$asin] = $this->fetchProductInfo($asin);
		}
		return $this->productInfos[$asin];
	}
	
	private function addPriceHistory($asin, $productInfo) {
		return '<div class="meinpreis">'.$this->getProductLink($asin, $productInfo).$this->getPriceHistoryFrame($asin).'<div class="meinpreis_buttons">'.$this->getBuyLink($asin, $productInfo).'</div>
</div>';
	}
	
	private function addProductInfo($asin, $productInfo) {
		return '<div class="meinpreis"><h3>'.$this->getProductLink($asin, $productInfo).'</h3><div class="meinpreis_twocolumn">'.
		$this->getProductImage($asin, $productInfo).'</div><div class="meinpreis_twocolumn">Preis: <b>'.$this->getPrice($productInfo).'</b><br><br>'.$this->getBuyLink($asin, $productInfo).'</div><div class="meinpreis_clear"></div>'.$this->getPriceHistoryFrame($asin).'
</div>';
	}
	
	private function getPrice($productInfo) {
		$amount = floatval($productInfo->amazonPrice)/100.0;
		$price = number_format($amount, 2, ',','.');
		return "$price &euro;";
	}
	
	private function getProductImage($asin, $productInfo) {
		return '<a href="'.$productInfo->imageUrl.'"><img class="meinpreis_product" src="'.$productInfo->imageThumbUrl.'" alt="'.htmlspecialchars($productInfo->name).'" /></a>';
	}
	
	private function getBuyLink($asin, $productInfo) {
		$buyImageSrc = plugins_url('images/buy.png', __FILE__);
		return '<a href="'.$productInfo->buyUrl.'"><img class="meinpreis_button" src="'.$buyImageSrc.'" alt="Jetzt bei Amazon kaufen" /></a>';
	}
	
	private function getProductLink($asin, $productInfo) {
		return '<a href="'.$productInfo->url.'">'.htmlspecialchars($productInfo->name).'</a>';
	}
	
	private function getPriceHistoryFrame($asin) {
		return '<div><iframe src="http://www.mein-preis.net/Graph/'.$asin.'.html?simple" width="100%" frameborder="0" marginheight="0" marginwidth="0" scrolling="no" style="border: 0px; "></iframe></div>';
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
	
	private function fetchProductInfo($asin) {
		$url = "http://www.mein-preis.net/api/productInfo.php?id=$asin";
		return json_decode(file_get_contents($url));
	}

}

// Start this plugin once all other plugins are fully loaded
add_action('init', 'MeinPreis', 5);
function MeinPreis() {
	global $meinPreis;
	$meinPreis = new MeinPreis();
}
