<?php

class ISC_ADMIN_GOOGLESITEMAP extends ISC_ADMIN_BASE
{

	/**
	 * The routing method that determines what methods should be called based on the GET parameter 'ToDo'
	 *
	 * @param string $Do A short 'action' string, determining what method should be executed
	 *
	 * @return void Doesn't return anything
	 */
	public function HandleToDo($Do)
	{
		if(isc_strtolower($Do) == 'showgooglesitemapinfo') {
			$this->showGoogleSitemapModal();
		}
	}

	/**
	 * This method outputs information about what a Google XML Sitemap is, how to use it and where it is located on the website.
	 * The output of this method is designed to be displayed in a modal window.
	 *
	 * @return void Doesn't return anything
	 */
	private function showGoogleSitemapModal()
	{
		$GLOBALS['SiteMapUrl'] = GetConfig('ShopPathNormal') . '/xmlsitemap.php';
		$this->template->display('googlesitemap.intro.tpl');
	}
}