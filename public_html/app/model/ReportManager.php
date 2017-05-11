<?php
/**
 * User: Frantisek Kasa <frantisekkasa@gmail.com>
 * Date: 16.04.2017
 * Project: xreporty
 * File: ReportManager.php
 */


namespace App\Model;


class ReportManager extends BaseManager
{
	/**
	 * Konstanty pro práci s daty
	 */
	const
		TABLE_NAME                      = 'campaigns',
		COLUMN_ID                       = 'id',
		COLUMN_CAMPAING_ID              = 'campaign_id',
		COLUMN_CUSTOMER_ID              = 'customer_id',
		COLUMN_CAMPAING_NAME            = 'name',
		COLUMN_CAMPAING_AMOUNT          = 'amount',
		COLUMN_CAMPAING_STATUS          = 'status',
		COLUMN_CAMPAING_IMPRESSIONS     = 'impressions',
		COLUMN_CAMPAING_CLICKS          = 'clicks',
		COLUMN_CAMPAING_CTR             = 'ctr',
		COLUMN_CAMPAING_AVG_CPC         = 'avg_cpc',
		COLUMN_CAMPAING_COST            = 'cost',
		COLUMN_CAMPAING_AVG_POSITION    = 'avg_position',
		COLUMN_CAMPAING_CONVERSION      = 'conversion',
		COLUMN_CAMPAING_CONV_RATE       = 'conv_rate',
		COLUMN_CAMPAING_CONV_VALUE      = 'total_conv_value'
	;

}