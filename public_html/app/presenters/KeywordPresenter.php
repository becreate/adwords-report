<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 01.05.2017
 * Project: xreporty
 * File: KeywordPresenter.php
 */


namespace App\Presenters;

use App\Model\KeywordManager;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\Reporting\v201702\ReportDownloader;
use Google\AdsApi\AdWords\Reporting\v201702\DownloadFormat;

class KeywordPresenter extends BasePresenter
{
	/** @persistent */
	public $adgroupId;
	/** @persistent */
	public $campaignId;
	/** @persistent */
	public $customerId;
	/** @var  KeywordManager @inject */
	public $keywordManager;

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

	public function actionShow($adgroupId, $customerId, $campaignId)
	{
		$this['breadcrumb']->addLink('Účty', $this->link('Account:'));
		$this['breadcrumb']->addLink('Kampaně', $this->link('Campaign:show', $this->customerId));
		$this['breadcrumb']->addLink('Reklamní sestavy', $this->link('Adgroup:show', $this->campaignId, $this->customerId));
		$this['breadcrumb']->addLink('Klíčová slova');
	}

	public function actionUpdate()
	{
		$this->processKeywordReports($this->adwords_session, $this->adgroupId);
		$this->flashMessage('Klíčová slova byla aktualizována.', 'success');
		$this->redirect('Keyword:show');
	}

	public function renderShow()
	{
		$keywords = $this->keywordManager->getKeywordsByAdgroupId($this->adgroupId);
		$adgroupName = $this->keywordManager->getAdgroupName($this->adgroupId);
		$campaignName = $this->keywordManager->getCampaignName($this->campaignId);
		if ($adgroupName)
		{
			$this->template->adgroupName = $adgroupName->adgroup_name;
		}
		if ($campaignName)
		{
			$this->template->campaignName = $campaignName->name;
		}
		if (!empty($keywords))
		{
			$this->template->keywords = $keywords;
		}

	}

	/**
	 * Provede načtení všech klíčových slov z Google AdWords a následně je uloží do databáze
	 *
	 * @param AdWordsSession $adWordsSession
	 * @return void
	 */
	private function processKeywordReports(AdWordsSession $adWordsSession, $adgroupId)
	{
		// TODO: amount - upravit formát, přidat do metody parametr pro vkládání pole - vybraná políčka (sloupce)
		$reportQuery = 'SELECT Id, AdGroupId, Criteria, ApprovalStatus, CpcBid, Clicks, Impressions, Ctr, AverageCpc, Cost, AveragePosition, Conversions, ConversionRate, ConversionValue FROM KEYWORDS_PERFORMANCE_REPORT WHERE AdGroupId = '. $adgroupId .' DURING TODAY';

		$reportDownloader = new ReportDownloader($adWordsSession);
		$reportDownloadResult = $reportDownloader->downloadReportWithAwql($reportQuery, DownloadFormat::CSV);
		$result = $reportDownloadResult->getAsString();

		if (!empty($result))
		{
			$reports = explode("\n", trim($result));

			$report_data = array();
			$report_column_name = array('keyword_id', 'adgroup_id', 'keyword_text', 'status', 'max_cpc', 'clicks', 'impressions', 'ctr', 'avg_cpc', 'cost', 'avg_position', 'conversion', 'conv_rate', 'total_conv_value');

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

					// přidání klíčového slova do databáze
					if (isset($report_data))
					{
						$this->keywordManager->saveDataKeyword($report_data[$key]);
					}
				}
			}
		}
	}
}