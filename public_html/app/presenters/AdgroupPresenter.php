<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 26.03.2017
 * Project: xreporty
 * File: AdgroupPresenter.php
 */


namespace App\Presenters;


use App\Model\AdgroupManager;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\v201702\cm\AdGroupService;
use Google\AdsApi\AdWords\v201702\cm\Selector;
use Google\AdsApi\AdWords\v201702\cm\OrderBy;
use Google\AdsApi\AdWords\v201702\cm\SortOrder;
use Google\AdsApi\AdWords\v201702\cm\Predicate;
use Google\AdsApi\AdWords\v201702\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201702\cm\Paging;

use Google\AdsApi\AdWords\Reporting\v201702\ReportDownloader;
use Google\AdsApi\AdWords\Reporting\v201702\DownloadFormat;


class AdgroupPresenter extends BasePresenter
{
	/** @persistent */
	public $campaignId;
	/** @persistent */
	public $customerId;

	/** @var  AdgroupManager @inject */
	public $adgroupManager;

	public function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn())
		{
			$this->redirect('Sign:in');
		}

		$this->adsapi->setClientCustomerId($this->customerId);
		$this->adwords_session = $this->adsapi->getConstructApiSession();
	}

	public function actionShow($campaignId, $customerId)
	{
		$this['breadcrumb']->addLink('Účty', $this->link('Account:'));
		$this['breadcrumb']->addLink('Kampaně', $this->link('Campaign:show', $this->customerId));
		$this['breadcrumb']->addLink('Reklamní sestavy');
	}

	public function actionUpdate()
	{
		$this->processAdGroupReports($this->adwords_session, $this->campaignId);
		$this->flashMessage('Reklamní sestavy byly aktualizovány.', 'success');
		$this->redirect('Adgroup:show');
	}

	public function renderShow($campaignId, $customerId)
	{
		$adgroups = $this->adgroupManager->getAdgroupsByCampaignId($campaignId);
		$campaignName = $this->adgroupManager->getCampaignName($campaignId);
		if ($campaignName)
		{
			$this->template->campaignName = $campaignName->name;
		}
		if (!empty($adgroups))
		{
			$this->template->adgroups = $adgroups;
		}

		$this->template->customerId = $this->customerId;
	}

	/**
	 * Získání všech reklamních kamapní, postup podle Google AdWords API
	 *
	 * @param AdWordsServices $adWordsServices
	 * @param AdWordsSession  $adWordsSession
	 * @param                 $campaignId
	 *
	 * @return array
	 */
	private function getAdGroups(AdWordsServices $adWordsServices, AdWordsSession $adWordsSession, $campaignId)
	{
		$adGroupService = $adWordsServices->get($adWordsSession, AdGroupService::class);

		// Create a selector to select all ad groups for the specified campaign.
		$selector = new Selector();
		$selector->setFields(['Id', 'Name']);
		$selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
		$selector->setPredicates(
			[new Predicate('CampaignId', PredicateOperator::IN, [$campaignId])]);

		$selector->setPaging(new Paging(0, self::PAGE_LIMIT));

		$totalNumEntries = 0;
		$entries = array();
		do {
			// Retrieve ad groups one page at a time, continuing to request pages
			// until all ad groups have been retrieved.
			$page = $adGroupService->get($selector);

			// Print out some information for each ad group.
			if ($page->getEntries() !== null) {
				$totalNumEntries = $page->getTotalNumEntries();
				foreach ($page->getEntries() as $adGroup) {
					$entries[] = $adGroup;
				}
			}

			$selector->getPaging()->setStartIndex(
				$selector->getPaging()->getStartIndex() + self::PAGE_LIMIT);
		} while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

		return $entries;
	}

	/**
	 * Provede načtení všech reklamních sestav z Google AdWords a následně je uloží do databáze
	 *
	 * @param AdWordsSession $adWordsSession
	 * @return void
	 */
	private function processAdGroupReports(AdWordsSession $adWordsSession, $campaignId)
	{
		// TODO: amount - upravit formát, přidat do metody parametr pro vkládání pole - vybraná políčka (sloupce)
		$reportQuery = 'SELECT CampaignId, AdGroupId, AdGroupName, AdGroupStatus, Impressions, Clicks, Ctr, AverageCpc, Cost, AveragePosition, Conversions, ConversionRate, ConversionValue FROM ADGROUP_PERFORMANCE_REPORT WHERE CampaignId = '. $campaignId .' DURING TODAY';

		$reportDownloader = new ReportDownloader($adWordsSession);
		$reportDownloadResult = $reportDownloader->downloadReportWithAwql($reportQuery, DownloadFormat::CSV);
		$result = $reportDownloadResult->getAsString();

		if (isset($result))
		{
			$reports = explode("\n", trim($result));

			$report_data = array();
			$report_column_name = array('campaign_id', 'adgroup_id' ,'adgroup_name', 'status', 'impressions', 'clicks', 'ctr', 'avg_cpc', 'cost', 'avg_position', 'conversion', 'conv_rate', 'total_conv_value');

			if (isset($reports))
			{
				foreach ($reports as $key => $report)
				{
					$report_data[] = explode(',',trim($report));
					$report_data[$key] = array_combine($report_column_name, $report_data[$key]);

					foreach ($report_data[$key] as $key2 => $data)
					{
						if (strpos($data, '%'))
						{
							$report_data[$key][$key2] = str_replace('%', '', trim($data));
						}
						else
						{
							$report_data[$key][$key2] = $data;
						}
					}

					// přidání kampaně do databáze
					if (isset($report_data[$key]))
					{
						$this->adgroupManager->saveAdgroupData($report_data[$key]);
					}
				}
			}
		}
	}
}