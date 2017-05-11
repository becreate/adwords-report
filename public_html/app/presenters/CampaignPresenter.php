<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 25.04.2017
 * Project: xreporty
 * File: CampaignPresenter.php
 */


namespace App\Presenters;

use App\Controls\SortFormControl;
use App\Model\CampaignManager;
use Composer\Console\Application;
use NasExt\Controls\FilterFormControl;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Configurator;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;

use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\Reporting\v201702\ReportDownloader;
use Google\AdsApi\AdWords\Reporting\v201702\DownloadFormat;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Tomaj\Form\Renderer\BootstrapRenderer;


class CampaignPresenter extends BasePresenter
{
	/** @var  CampaignManager @inject */
	public $campaignManager;

	/** @persistent */
	public $customerId;

	public $ajaxData = array();

	protected function startup()
	{
		parent::startup();
		if (!$this->user->isLoggedIn())
		{
			$this->redirect('Sign:in');
		}

		$this->adsapi->setClientCustomerId($this->customerId);
		$this->adwords_session = $this->adsapi->getConstructApiSession();
	}

	public function actionDefault($customerId)
	{
		parent::loadState($this->getParameters());
		$this['breadcrumb']->addLink('Účty', $this->link('Account:'));
		$this['breadcrumb']->addLink('Kampaně');
	}

	public function actionShow($campaignId)
	{
		//TODO: bude sloužit pro detail a její úpravu
	}

	public function actionUpdate()
	{
		$this->processCampaignReports($this->adwords_session, $this->customerId);
		$this->flashMessage('Kampaně byly aktualizovány.', 'success');
		$this->redirect('Campaign:show');
	}

	public function handleSort(array $sortData)
	{
		// TODO: seřadit data a vypsat je v šabloně
		//$this->template->campaigns = $this->campaignManager->getCampaignsByCustomerId($this->customerId, $sortData);
		// TODO: prázdná data
		$this->ajaxData[] = $this->getHttpRequest()->getRawBody();
		$this->ajaxData[] = $sortData;
		/*if ($this->isAjax())
		{
			// překreslení objektu
			$this->redrawControl('campaignList');
		}*/
	}

	public function renderDefault($customerId, $sortData = array())
	{
		/** @var FilterFormControl $filter */
		$filter = $this['filter'];
		$filterData = $filter->getData();

		/** @var SortFormControl $sort */
		$sortedData= $this['sort'];

		/*if (!is_null($filterData['clicks']) && (!is_null($sortedData['name']) && !is_null($sortedData['cost'])))
		{
			$campaigns = $this->campaignManager->getCampaignsByFilterData($customerId, $filterData, $sortedData);
		}
		elseif (is_null($filterData['clicks']) && (!is_null($sortedData['name']) && !is_null($sortedData['cost'])))
		{
			$campaigns = $this->campaignManager->getCampaignsByCustomerId($customerId, $sortedData);
		}
		else
		{*/
			$campaigns = $this->campaignManager->getCampaignsByCustomerId($customerId);
		//}

		// získání vp komponenty
		$visualPaginator = $this['visualPaginator'];
		// získání stránkovacího formuláře z vp
		$paginator = $visualPaginator->getPaginator();
		// počet položek na stránku
		$paginator->itemsPerPage = self::ITEMS_PER_PAGE;
		// celkový počet položek
		$paginator->itemCount = $campaigns->count();
		// aplikace limitu
		$campaigns->limit($paginator->itemsPerPage, $paginator->offset);

		if (!empty($campaigns))
		{
			$this->template->campaigns = $campaigns;
		}
	}

	public function renderShow()
	{

	}

	protected function createComponentDownloadForm()
	{
		$form = new Form();
		$form->setRenderer(new BootstrapRenderer());
		$downloadType = ['current' => 'Aktivní kampaně', 'all' => 'Všechny kampaně'];

		$form->addSelect('type', 'Stažení:', $downloadType)
			->setPrompt('Vyberte...');
		$form->addSubmit('send', 'Download')
			->getControlPrototype()
				->setName('button')
				->setHtml('<i class="icon-download"></i>');

		$form->onSuccess[] = [$this, 'processDownloadForm'];

		return $form;
	}

	public function processDownloadForm(Form $form, $values)
	{
		$this->downloadCampaignReports($values->type);
	}

	protected function createComponentFilter($name)
	{
		$control = new FilterFormControl($this, $name);

		/** @var Form $form */
		$form = $control['form'];

		$costValues = ['<50' => 'Do 50', '<100' => 'Do 100', '<1000' => 'Do 1 000', '<10000' => 'Do 10 000', '<100000' => 'Do 100 000', '>100000' => 'Nad 100 000'];
		$form->addSelect('clicks', 'Prokliky:', $costValues)
			->setPrompt('Vyberte...');
		$stavValues = ['enabled' => 'Aktivní', 'paused' => 'Pozastaveno', 'removed' => 'Odstraněno'];
		$form->addSelect('status', 'Stav:', $stavValues)
			->setPrompt('Vyberte...');

		return $control;
	}

	protected function createComponentSort()
	{
		$form = new Form();
		$form->setRenderer(new BootstrapRenderer());
		$form->getElementPrototype()->class = 'ajax';

		$sorting = ['ASC' => 'Vzestupně', 'DESC' => 'Sestupně'];

		$form->addSelect('name', 'Název', $sorting)
			->setAttribute('class', 'ajax');
		$form->addSelect('cost', 'Cena', $sorting);

		return $form;
	}

	/**
	 * Získání všech kampaní podle vybraného účtu, postup podle Google AdWords API
	 *
	 * @param AdWordsServices $adWordsServices
	 * @param AdWordsSession $adWordsSession
	 */
	private function getCampaigns(AdWordsServices $adWordsServices, AdWordsSession $adWordsSession)
	{
		$campaignService = $adWordsServices->get($adWordsSession, CampaignService::class);

		// Create selector.
		$selector = new Selector();
		$selector->setFields(['Id', 'Name', 'Status', 'Amount']);
		$selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
		$selector->setPaging(new Paging(0, self::PAGE_LIMIT));

		$totalNumEntries = 0;
		$entries = array();
		do {
			// Make the get request.
			$page = $campaignService->get($selector);

			// Display results.
			if ($page->getEntries() !== null) {
				$totalNumEntries = $page->getTotalNumEntries();
				foreach ($page->getEntries() as $campaign) {
					$entries[] = $campaign;
				}
			}

			// Advance the paging index.
			$selector->getPaging()->setStartIndex(
				$selector->getPaging()->getStartIndex() + self::PAGE_LIMIT);
		} while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

		return $entries;
	}

	/**
	 * Výpis jedné kampaně podle id, postup podle Google AdWords API
	 *
	 * @param AdWordsServices $adWordsServices
	 * @param AdWordsSession  $adWordsSession
	 * @param                 $campaignId
	 *
	 * @return array
	 */
	private function getCampaign(AdWordsServices $adWordsServices, AdWordsSession $adWordsSession, $campaignId)
	{
		$campaignService = $adWordsServices->get($adWordsSession, CampaignService::class);

		$selector = new Selector();
		$selector->setFields(['Id', 'Name', 'Status']);
		$selector->setPredicates([
			new Predicate('CampaignId', PredicateOperator::IN, [$campaignId])]);

		$selector->setPaging(new Paging(0, self::PAGE_LIMIT));

		$totalNumEntries = 0;
		$entries = array();
		do {
			$page = $campaignService->get($selector);

			if ($page->getEntries() !== null) {
				$totalNumEntries = $page->getTotalNumEntries();
				foreach ($page->getEntries() as $campaign) {
					$entries[] = $campaign;
				}
			}

			$selector->getPaging()->setStartIndex(
				$selector->getPaging()->getStartIndex() + self::PAGE_LIMIT);
		} while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

		return $entries;
	}

	// TODO: otestovat
	private function downloadCampaignReports($type)
	{
		$accountName = $this->campaignManager->getAccountName($this->customerId);
		$date = new DateTime();
		$filePath = Strings::webalize($this->name);
		$fileName = $date->format('dmY_').Strings::webalize($accountName->name);
		$reportPath = $this->context->getParameters()['reportDir'];
		$fileFormat = '.csv';
		$this->adsapi->setReportSettings(true,true, false, true);
		$this->adwords_session = $this->adsapi->getConstructApiSession();
		switch ($type)
		{
			case 'current':
				$reportQuery = 'SELECT AccountDescriptiveName, CampaignName, CampaignStatus, Impressions, Clicks, Ctr, AverageCpc, Cost, AveragePosition, Conversions, ConversionRate, ConversionValue FROM CAMPAIGN_PERFORMANCE_REPORT WHERE ExternalCustomerId = '. $this->customerId .' AND CampaignStatus IN [ENABLED, PAUSED] DURING TODAY';

				$reportDownloader = new ReportDownloader($this->adwords_session);
				$reportDownloadResult = $reportDownloader->downloadReportWithAwql($reportQuery, DownloadFormat::CSV);
				if (Finder::findDirectories($filePath)->from($reportPath)->count() > 0)
				{
					$reportDownloadResult->saveToFile($reportPath. DIRECTORY_SEPARATOR . $filePath . DIRECTORY_SEPARATOR . $fileName.$fileFormat);
				}
				else
				{
					FileSystem::createDir($reportPath. DIRECTORY_SEPARATOR . $filePath);
					$reportDownloadResult->saveToFile($reportPath. DIRECTORY_SEPARATOR . $filePath . DIRECTORY_SEPARATOR . $fileName.$fileFormat);
				}
				break;
			case 'all':
				$all_campaigns = $this->campaignManager->getCampaigns();
				dump($all_campaigns);
				break;
			default:
				$this->flashMessage('Musíte vybrat jednu z možností, pro stažení kampaní do souboru.', 'warning');
				break;
		}
	}

	// TODO: otestovat
	private function getCampaignReportsFromFile($filePath, $fileName)
	{
		$csv_array = [];
		if ( ($handle = fopen($fileName, 'r')) !== false )
		{
			$line_number = 0;
			while ( ($data = fgetcsv($handle, 1000, ',')) !== false )
			{
				$count_keys = count($data);

				for ($index = 0; $index < $count_keys; $index++)
				{
					$csv_array[$line_number][$index] = $data[$index];
				}

				$line_number++;
			}
			fclose($handle);
		}

		return $csv_array;
	}

	/**
	 * Provede načtení všech kampaní z Google AdWords a následně je uloží do databáze
	 *
	 * @param AdWordsSession $adWordsSession
	 * @return void
	 */
	private function processCampaignReports(AdWordsSession $adWordsSession, $customerId)
	{
		// TODO: amount - upravit formát, přidat do metody parametr pro vkládání pole - vybraná políčka (sloupce)
		$reportQuery = 'SELECT CampaignId, ExternalCustomerId, CampaignName, CampaignStatus, Impressions, Clicks, Ctr, AverageCpc, Cost, AveragePosition, Conversions, ConversionRate, ConversionValue FROM CAMPAIGN_PERFORMANCE_REPORT WHERE ExternalCustomerId = '. $customerId .' DURING TODAY';

		$reportDownloader = new ReportDownloader($adWordsSession);
		$reportDownloadResult = $reportDownloader->downloadReportWithAwql($reportQuery, DownloadFormat::CSV);
		$result = $reportDownloadResult->getAsString();

		if (!empty($result))
		{
			$reports = explode("\n", trim($result));

			$report_data = array();
			$report_column_name = array('campaign_id', 'customer_id', 'name', 'status', 'impressions', 'clicks', 'ctr', 'avg_cpc', 'cost', 'avg_position', 'conversion', 'conv_rate', 'total_conv_value');

			if (isset($reports))
			{
				foreach ($reports as $parent => $report)
				{
					$report_data[] = explode(',',trim($report));
					$report_data[$parent] = array_combine($report_column_name, $report_data[$parent]);
					foreach ($report_data[$parent] as $child => $data)
					{
						if (strpos($data, '%') !== false && strpos($data, ".") !== false)
						{
							$data = str_replace('%', '', (float) trim($data));
							$data = number_format((float) trim($data), 2, ',', '');
							$report_data[$parent][$child] = (float) $data;
						}
						elseif (strpos($data, ".") !== false)
						{
							$data = number_format((float) trim($data), 2, ',', '');
							$report_data[$parent][$child] = (float) $data;
						}
						else
						{
							$report_data[$parent][$child] = $data;
						}

					}

					// přidání kampaně do databáze
					if (isset($report_data))
					{
						if ($report_data[$parent]['impressions'] && $report_data[$parent]['clicks'])
						{
							$report_data[$parent]['impressions']    = intval($report_data[$parent]['impressions']);
							$report_data[$parent]['clicks']         = intval($report_data[$parent]['clicks']);
						}
						$this->campaignManager->saveDataCampaign($report_data[$parent]);
					}
				}
			}
		}
	}
}