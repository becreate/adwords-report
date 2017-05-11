<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 01.05.2017
 * Project: xreporty
 * File: KeywordManager.php
 */


namespace App\Model;


class KeywordManager extends BaseManager
{
	const
		TABLE_NAME                      = 'keywords',
		COLUMN_ID                       = 'id',
		COLUMN_KEYWORD_ID              = 'keyword_id',
		COLUMN_ADGROUP_ID              = 'adgroup_id',
		COLUMN_KEYWORD_NAME            = 'keyword_text',
		COLUMN_KEYWORD_STATUS          = 'status',
		COLUMN_KEYWORD_MAX_CPC          = 'max_cpc',
		COLUMN_KEYWORD_IMPRESSIONS     = 'impressions',
		COLUMN_KEYWORD_CLICKS          = 'clicks',
		COLUMN_KEYWORD_CTR             = 'ctr',
		COLUMN_KEYWORD_AVG_CPC         = 'avg_cpc',
		COLUMN_KEYWORD_COST            = 'cost',
		COLUMN_KEYWORD_AVG_POSITION    = 'avg_position',
		COLUMN_KEYWORD_CONVERSION      = 'conversion',
		COLUMN_KEYWORD_CONV_RATE       = 'conv_rate',
		COLUMN_KEYWORD_CONV_VALUE      = 'total_conv_value'
	;

	public function getKeywordsByAdgroupId($adgroupId)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ADGROUP_ID, $adgroupId)->fetchAll();
	}

	public function getKeywordById($keywordId)
	{
		return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_KEYWORD_ID, $keywordId)->fetch();
	}

	public function getAdgroupName($adgroupId)
	{
		return $this->database->table('adgroups')
			->alias(':keywords', 'kw')
			->select('adgroups.adgroup_name')
			->where('kw.adgroup_id', $adgroupId)
			->fetch();
	}

	public function getCampaignName($campaignId)
	{
		return $this->database->table('campaigns')
			->alias(':adgroups', 'adg')
			->select('campaigns.name')
			->where('adg.campaign_id', $campaignId)
			->fetch();
	}

	public function saveDataKeyword($data)
	{
		if ($data[self::COLUMN_KEYWORD_ID])
		{
			$keyword = $this->getKeywordById($data[self::COLUMN_KEYWORD_ID]);
			if ($keyword)
			{
				$this->database->table(self::TABLE_NAME)->where(self::COLUMN_KEYWORD_ID, $data[self::COLUMN_KEYWORD_ID])->update($data);
			}
			else
			{
				$this->database->table(self::TABLE_NAME)->insert($data);
			}
		}
	}

	public function getKeywords()
	{
		return $this->database->table(self::TABLE_NAME)
			->select(self::COLUMN_KEYWORD_ID)
			->select(self::COLUMN_ADGROUP_ID)
			->select(self::COLUMN_KEYWORD_NAME)
			->select(self::COLUMN_KEYWORD_STATUS)
			->select(self::COLUMN_KEYWORD_IMPRESSIONS)
			->select(self::COLUMN_KEYWORD_CLICKS)
			->select('REPLACE(' . self::COLUMN_KEYWORD_CTR . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_KEYWORD_AVG_CPC . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_KEYWORD_COST . ',".", ",")')
			->select(self::COLUMN_KEYWORD_AVG_POSITION)
			->select('REPLACE(' . self::COLUMN_KEYWORD_CONVERSION . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_KEYWORD_CONV_RATE . ',".", ",")')
			->select('REPLACE(' . self::COLUMN_KEYWORD_CONV_VALUE . ',".", ",")')
			->fetchAll();
	}
}