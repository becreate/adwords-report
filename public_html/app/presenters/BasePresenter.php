<?php

namespace App\Presenters;

use App\Breadcrumb\Breadcrumb;
use Nette;

use IPub\VisualPaginator\Components as VisualPaginator;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	/** @var \App\AdsMyExtension\OAuth2Authentication @inject */
	public $adsapi;

    /** @var Nette\Object */
    public $adwords_session;

    /** @var int Maximální počet stránke pro stránkování */
    const PAGE_LIMIT = 500;

	const ITEMS_PER_PAGE = 5;
   
    protected function startup()
    {
        parent::startup();

        $this->adsapi->setClientId('123445660965-pv5kcvab25s8gg6ipakpd0adr5o4quj6.apps.googleusercontent.com');
        $this->adsapi->setClientSecret('FihyaOns-Wklnj3CYwhmYt17');
        $this->adsapi->setDeveloperToken('8njuDaMefzAKU98yZURtRQ');
        $this->adsapi->setRefreshToken('1/2hKfkqjvRZBAFIW670sHkDfm_kw99UIdWw1LRjwgxlI');
	    // nastavení ID správce 2214173942
	    $this->adsapi->setClientCustomerId('2214173942');

	    // získání nakonfigurovaných vlastností
	    $this->adwords_session = $this->adsapi->getConstructApiSession();
    }

    protected function createComponentBreadcrumb()
    {
    	$breadcrumb = new Breadcrumb();
	    $breadcrumb->customTemplate($this->context->getParameters()['appDir'].'/presenters/templates/components/breadcrumb.latte');
    	$breadcrumb->addLink('Hlavní stránka', $this->link('Homepage:'), 'icon-home');

    	return $breadcrumb;
    }

    protected function createComponentVisualPaginator()
    {
	    $control = new VisualPaginator\Control();
	    $control->setTemplateFile($this->context->getParameters()['appDir'].'/presenters/templates/components/bootstrap-pagination.latte');
	    return $control;
    }
}
